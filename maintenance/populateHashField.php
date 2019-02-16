<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\Installer;
use SMW\SetupFile;
use SMW\Setup;
use SMW\Store;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv('MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PopulateHashField extends \Maintenance {

	/**
	 * Threshold as the when the `populateHashField.php` should be used by an
	 * administrator instead.
	 *
	 * This postpones the execution to after `setupStore.php`/`update.php` in
	 * order to help minimize the time required for the initial setup/upgrade.
	 */
	const COUNT_SCRIPT_EXECUTION_THRESHOLD = 200000;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 3.1
	 */
	public function __construct() {
		$this->mDescription = "Populate the 'smw_hash' field for all entities that have a missing entry.";
		parent::__construct();
	}

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function setComplete( $incomplete ) {

		$this->reportMessage(
			"   ... writing the status to the setup information file ... \n"
		);

		$setupFile = new SetupFile();

		$setupFile->write(
			$GLOBALS,
			[
				Installer::POPULATE_HASH_FIELD_COMPLETE => $incomplete
			]
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {

		if ( $this->messageReporter !== null ) {
			return $this->messageReporter->reportMessage( $message );
		}

		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( !Setup::isEnabled() ) {
			$this->reportMessage( "\nYou need to have Semantic MediaWiki enabled in order to run the maintenance script!\n" );
			exit;
		}

		$this->store = ApplicationFactory::getInstance()->getStore(
			'SMW\SQLStore\SQLStore'
		);

		$this->reportMessage( "\nChecking 'smw_hash' field consistency ...\n" );
		$this->populate();

		$this->reportMessage( "   ... done.\n" );

		return true;
	}

	/**
	 * @since 3.1
	 *
	 * @return Iterator
	 */
	public function fetchRows() {

		$connection = $this->store->getConnection( 'mw.db' );

		return $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			],
			[
				'smw_hash' => null,
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW )
			],
			__METHOD__
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param Iterator $rows
	 */
	public function populate( \Iterator $rows = null ) {

		if ( $rows === null ) {
			$rows = $this->fetchRows();
		}

		$connection = $this->store->getConnection( 'mw.db' );
		$idTable = $this->store->getObjectIds();

		$count = 0;
		$i = 0;

		if ( $rows !== null ) {
			$count = $rows->numRows();
		}

		if ( $count == 0 ) {
			$this->reportMessage( "   ... all rows populated ...\n"  );
		} else {
			$this->reportMessage( "   ... missing $count rows ...\n"  );

			foreach ( $rows as $row ) {

				$hash = $idTable->computeSha1(
					[
						$row->smw_title,
						(int)$row->smw_namespace,
						$row->smw_iw,
						$row->smw_subobject
					]
				);

				$this->reportMessage(
					$this->progress( $row->smw_id, $i++, $count )
				);

				$connection->update(
					SQLStore::ID_TABLE,
					[
						'smw_hash' => $hash
					],
					[
						'smw_id' => $row->smw_id
					],
					__METHOD__
				);
			}
		}

		$this->reportMessage( "\n"  );
		$this->setComplete( true );
	}

	/**
	 * @see Maintenance::addDefaultParams
	 */
	protected function addDefaultParams() {
		parent::addDefaultParams();
	}

	private function progress( $id, $i, $count ) {
		return "\r". sprintf( "%-35s%s", "   ... updating document no.", sprintf( "%s (%1.0f%%)", $id, round( ( $i / $count ) * 100 ) ) );
	}

}

$maintClass = 'SMW\Maintenance\PopulateHashField';
require_once( RUN_MAINTENANCE_IF_MAIN );
