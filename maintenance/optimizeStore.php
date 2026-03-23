<?php

namespace SMW\Maintenance;

use MediaWiki\Maintenance\Maintenance;
use Onoi\MessageReporter\CallbackMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Setup;
use SMW\SQLStore\Installer\TableOptimizer;
use SMW\StoreFactory;
use SMW\Utils\CliMsgFormatter;

/**
 * Load the required class
 */
// @codeCoverageIgnoreStart
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}
// @codeCoverageIgnoreEnd

/**
 * Optimizes the SMW storage backend tables without running the full setup
 * routine. This is equivalent to running `php setupStore.php --skip-import`
 * but without the overhead of the complete installation/update process.
 *
 * This script is intended to be run on a regular basis via cron by system
 * administrators who want to keep the SMW tables optimized.
 *
 * Note:
 * If SMW is not installed in its standard path under ./extensions
 * then the MW_INSTALL_PATH environment variable must be set.
 * See README in the maintenance directory.
 *
 * Usage:
 * php optimizeStore.php [options...]
 *
 * --backend             The backend to use, e.g. SMW\SQLStore\SQLStore.
 *
 * --with-maintenance-log  Add a log entry to `Special:Log` about the
 *                         maintenance run, including memory and time used.
 *
 * @license GPL-2.0-or-later
 * @since 4.2
 *
 * @author mwjames
 */
class optimizeStore extends Maintenance {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 4.2
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Optimizes the SMW storage backend tables without running the full setup routine.' );

		$this->addOption(
			'backend',
			'Execute the operation for the storage backend of the given name.',
			false,
			true,
			'b'
		);

		$this->addOption(
			'with-maintenance-log',
			'Add log entry to `Special:Log` about the maintenance run.',
			false
		);
	}

	/**
	 * @since 4.2
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 4.2
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
		if ( $this->canExecute() !== true ) {
			exit;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		if ( $this->messageReporter === null ) {
			$this->messageReporter = new CallbackMessageReporter( [ $this, 'reportMessage' ] );
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"This script optimizes the SMW storage backend tables. Unlike running",
			"setupStore.php --skip-import, it skips the full database setup routine",
			"and only performs the table optimization step.",
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		global $smwgDefaultStore;
		$storeClass = $this->getOption( 'backend', $smwgDefaultStore );

		if ( !class_exists( $storeClass ) ) {
			$this->messageReporter->reportMessage(
				"\nError: There is no backend class \"$storeClass\". Aborting.\n"
			);
			return false;
		}

		$store = StoreFactory::getStore( $storeClass );

		$connectionManager = $applicationFactory->getConnectionManager();
		$store->setConnectionManager( $connectionManager );

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Table optimization' ) . "\n"
		);

		$tableSchemaManager = $store->getInstaller()->getTableSchemaManager();
		$tableBuilder = $store->getInstaller()->getTableBuilder();

		$tableOptimizer = new TableOptimizer( $tableBuilder );
		$tableOptimizer->setMessageReporter( $this->messageReporter );

		$tableOptimizer->runForTables(
			$tableSchemaManager->getTables()
		);

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'OptimizeStoreLogger' );
			$runtimeValues = $maintenanceHelper->getRuntimeValues();

			$log = [
				'Memory used' => $runtimeValues['memory-used'],
				'Time used'   => $runtimeValues['humanreadable-time'],
			];

			$maintenanceLogger->logFromArray( $log );
		}

		return true;
	}

	private function canExecute() {
		if ( !Setup::isEnabled() ) {
			return $this->reportMessage(
				"\nYou need to have SMW enabled in order to run the maintenance script!\n"
			);
		}

		if ( !Setup::isValid( true ) ) {
			return $this->reportMessage(
				"\nYou need to run `update.php` or `setupStore.php` first before continuing\n" .
				"with this maintenance task!\n"
			);
		}

		return true;
	}

}

// @codeCoverageIgnoreStart
$maintClass = optimizeStore::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
