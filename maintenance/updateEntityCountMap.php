<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use Onoi\MessageReporter\MessageReporter;
use SMW\SQLStore\SQLStore;
use SMW\Utils\HmacSerializer;
use SMW\DIWikiPage;
use SMW\Maintenance\MaintenanceCheck;
use SMW\Utils\CliMsgFormatter;

/**
 * Load the required class
 */
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class updateEntityCountMap extends \Maintenance {

	/**
	 * Incomplete task message
	 */
	const COUNTMAP_INCOMPLETE = 'smw-updateentitycountmap-incomplete';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var CliMsgFormatter
	 */
	private $cliMsgFormatter;

	/**
	 * @var int
	 */
	private $last = 0;

	/**
	 * @since 3.2
	 */
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Updates the smw_`countmap` field';
		$this->addOption( 's', 'ID starting point', false, true );
	}

	/**
	 * @since 3.2
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( ( $maintenanceCheck = new MaintenanceCheck() )->canExecute() === false ) {
			exit ( $maintenanceCheck->getMessage() );
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$this->store = $applicationFactory->getStore(
			SQLStore::class
		);

		if ( $this->messageReporter === null ) {
			$this->messageReporter = $maintenanceFactory->newMessageReporter(
				[ $this, 'reportMessage' ]
			);
		}

		$this->cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->head()
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'About' )
		);

		$text = [
			"This script runs an update on the `smw_countmap` field."
		];

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->section( 'Field update' ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->oneCol( "Fetching fields ..." )
		);

		$count = $this->getCount();
		$setupFile = $applicationFactory->singleton( 'SetupFile' );

		if ( $count === false ) {
			$setupFile->removeIncompleteTask( self::COUNTMAP_INCOMPLETE );

			return $this->messageReporter->reportMessage(
				$this->cliMsgFormatter->oneCol( "... nothing selected!", 3 )
			);
		}

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoCols( "... estimating required updates ...", "{$count} (rows)", 3 )
		);

		$this->runUpdate();

		$setupFile->removeIncompleteTask( self::COUNTMAP_INCOMPLETE );
	}

	private function getCount() {

		$connection = $this->store->getConnection( 'mw.db' );

		$this->last = (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'MAX(smw_id)',
			'',
			__METHOD__
		);

		$row = $connection->selectRow(
			SQLStore::ID_TABLE,
			[
				'COUNT( smw_id ) AS count',
			],
			[
				'smw_proptable_hash IS NOT NULL',
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWINTDEFIW ),
			],
			__METHOD__
		);

		if ( $row === false ) {
			return false;
		}

		return $row->count;
	}

	private function runUpdate() {

		$connection = $this->store->getConnection( 'mw.db' );

		for ( $i = 0; $i <= $this->last ; $i++ ) {

			$row = $connection->selectRow(
				SQLStore::ID_TABLE,
				[
					'smw_id',
					'smw_title',
					'smw_namespace',
					'smw_iw',
					'smw_subobject',
					'smw_rev'
				],
				[
					'smw_id' => $i,
					'smw_proptable_hash IS NOT NULL',
					'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
					'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWINTDEFIW ),
				],
				__METHOD__
			);

			if ( $row === false ) {
				continue;
			}

			$subject = new DIWikiPage(
				$row->smw_title,
				$row->smw_namespace,
				$row->smw_iw,
				$row->smw_subobject
			);

			$semanticData = $this->store->getSemanticData( $subject );
			$countMap = [];

			foreach ( $semanticData->getProperties() as $property ) {

				$key = $property->getKey();
				$pv = $semanticData->getPropertyValues(
					$property
				);

				if ( $key === '_INST' ) {
					$countMap[$key] = $countMap[$key] ?? [];

					foreach ( $pv as $dataItem ) {
						$countMap[$key] += [ $dataItem->getDBKey() => 1 ];
					}

				} else {
					$countMap[$key] = count( $pv );
				}
			}

			$countMap = $connection->escape_bytea(
				HmacSerializer::compress( $countMap )
			);

			$rows = [
				'smw_id' => $row->smw_id,
				'smw_countmap' => $countMap
			];

			$connection->upsert(
				SQLStore::ID_AUXILIARY_TABLE,
				$rows,
				[
					'smw_id'
				],
				$rows,
				__METHOD__
			);

			$propgress = $this->cliMsgFormatter->progressCompact( $i, $this->last );

			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->twoColsOverride( "... updating (current/last) ...", $propgress, 3 )
			);
		}

		$propgress = $this->cliMsgFormatter->progressCompact( $this->last, $this->last );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoColsOverride( "... updating (current/last) ...", $propgress, 3 )
		);

		$this->messageReporter->reportMessage(
			"\n" . $this->cliMsgFormatter->oneCol( "... done.", 3 )
		);
	}

}

$maintClass = updateEntityCountMap::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
