<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\Installer;
use SMW\SetupFile;
use SMW\Setup;
use SMW\Store;
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
 * @since 3.1
 *
 * @author mwjames
 */
class populateHashField extends \Maintenance {

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
	 * @var CliMsgFormatter
	 */
	private $cliMsgFormatter;

	/**
	 * @var int
	 */
	private $last = 0;

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

		$this->cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			$this->cliMsgFormatter->firstCol( "... updating the setup file key ...", 3 )
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
			$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
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

		if ( ( $maintenanceCheck = new MaintenanceCheck() )->canExecute() === false ) {
			exit ( $maintenanceCheck->getMessage() );
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$this->store = $applicationFactory->getStore(
			SQLStore::class
		);

		$localMessageProvider = $maintenanceFactory->newLocalMessageProvider(
			'/local/maintenance.i18n.json'
		);

		$localMessageProvider->loadMessages();

		$this->cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			"\n" . $this->cliMsgFormatter->head()
		);

		$text = $localMessageProvider->msg( 'smw-maintenance-populatehashfield-table-update' );

		$this->reportMessage(
			$this->cliMsgFormatter->section( $text )
		);

		$text = $localMessageProvider->msg( 'smw-maintenance-populatehashfield-checking-hash-field' );

		$this->reportMessage("\n$text...\n" );

		$this->populate();

		$text = $localMessageProvider->msg( 'smw-maintenance-done' );

		$this->reportMessage(
			$this->cliMsgFormatter->oneCol( "... $text", 3 )
		);

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

		$this->last = (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'MAX(smw_id)',
			$conditions,
			__METHOD__
		);

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
	 * @since 3.1
	 *
	 * @param Iterator $rows
	 */
	public function populate( \Iterator $rows = null ) {

		$this->cliMsgFormatter = new CliMsgFormatter();
		$this->cliMsgFormatter->setStartTime( microtime( true ) );

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
				$this->cliMsgFormatter->twoCols( "... all rows populated ...", CliMsgFormatter::OK, 3 )
			);

			return $this->setComplete( true );
		}

		$msg = $this->hasOption( 'force-update' ) ? 'matching rows' : 'missing rows';

		$this->reportMessage(
			$this->cliMsgFormatter->twoCols( "... $msg ...", "$count (rows)", 3 )
		);

		foreach ( $rows as $row ) {

			$hash = $idTable->computeSha1(
				[
					$row->smw_title,
					(int)$row->smw_namespace,
					$row->smw_iw,
					$row->smw_subobject
				]
			);

			$progress = $this->cliMsgFormatter->progressCompact( ++$i, $count, $row->smw_id, $this->last );

			$this->reportMessage(
				$this->cliMsgFormatter->twoColsOverride( "... updating the `smw_hash` field ...", "$progress", 3 )
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
		$this->setComplete( true );
	}

}

$maintClass = populateHashField::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
