<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\Installer;
use SMW\SetupFile;
use SMW\Setup;
use SMW\Store;
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
		parent::__construct();
		$this->addDescription( "Populate the 'smw_hash' field for all entities that have a missing entry." );
		$this->addOption( 'force-update', 'Forces a recomputation of the hash field', false );
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $complete
	 */
	public function setComplete( $complete ) {

		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... updating the setup file key ...", 3 )
		);

		$setupFile = new SetupFile();

		if ( $complete ) {
			$setupFile->removeIncompleteTask( 'smw-populatehashfield-incomplete' );
		} else {
			$setupFile->addIncompleteTask( 'smw-populatehashfield-incomplete' );
		}

		// Remove legacy by 3.3
		$setupFile->remove( \SMW\SQLStore\Installer::POPULATE_HASH_FIELD_COMPLETE );

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
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

		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'Table update' )
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

		$conditions = [
			'smw_hash' => null,
			'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW )
		];

		if ( $this->hasOption( 'force-update' ) ) {
			unset( $conditions['smw_hash' ] );
		}

		return $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			],
			$conditions,
			__METHOD__,
			[ 'ORDER BY' => 'smw_id' ]
		);
	}

	/**
	 * @since 3.2
	 *
	 * @return integer
	 */
	public function getLastMissing() {

		$connection = $this->store->getConnection( 'mw.db' );

		$conditions = [
			'smw_hash' => null,
			'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW )
		];

		if ( $this->hasOption( 'force-update' ) ) {
			unset( $conditions['smw_hash' ] );
		}

		return (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'MAX(smw_id)',
			$conditions,
			__METHOD__
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param Iterator $rows
	 */
	public function populate( \Iterator $rows = null ) {

		$cliMsgFormatter = new CliMsgFormatter();
		$cliMsgFormatter->setStartTime( microtime( true ) );

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
			$this->reportMessage(
				$cliMsgFormatter->twoCols( "... all rows populated ...", CliMsgFormatter::OK, 3 )
			);
		} else {

			if ( $this->hasOption( 'force-update' ) ) {
				$msg = $cliMsgFormatter->twoCols( "... matching rows ...", "(rows) $count", 3 );
			} else {
				$msg = $cliMsgFormatter->twoCols( "... missing rows ...", "(rows) $count", 3 );
			}

			$this->reportMessage( $msg );
			$last_id = $this->getLastMissing();

			foreach ( $rows as $row ) {

				$i++;
				$hash = $idTable->computeSha1(
					[
						$row->smw_title,
						(int)$row->smw_namespace,
						$row->smw_iw,
						$row->smw_subobject
					]
				);

				$progress = $cliMsgFormatter->progressCompact( $i, $count, $row->smw_id, $last_id );

				$this->reportMessage(
					$cliMsgFormatter->twoColsOverride( "... updating the `smw_hash` field ...", "$progress", 3 )
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

			$this->reportMessage( "\n"  );
		}

		$this->setComplete( true );
	}

}

$maintClass = PopulateHashField::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
