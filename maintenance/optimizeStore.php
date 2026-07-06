<?php

namespace SMW\Maintenance;

use MediaWiki\Maintenance\Maintenance;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Setup;
use SMW\SPARQLStore\SPARQLStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\StoreFactory;
use SMW\Utils\CliMsgFormatter;
use Wikimedia\Rdbms\IMaintainableDatabase;

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
 * Optimizes the SMW storage backend tables independently of the setup routine.
 *
 * @license GPL-2.0-or-later
 * @since 7.1.0
 */
class optimizeStore extends Maintenance {

	protected ?MessageReporter $messageReporter = null;

	private ?Store $store = null;

	/**
	 * @since 7.1.0
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Optimizes the SMW storage backend tables (for example ANALYZE/OPTIMIZE) independently of the setup process.' );

		$this->addOption( 'backend', 'Execute the operation for the storage backend of the given name.', false, true, 'b' );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );
	}

	/**
	 * @see Maintenance::getDbType
	 *
	 * @since 7.1.0
	 */
	public function getDbType() {
		return Maintenance::DB_ADMIN;
	}

	/**
	 * @since 7.1.0
	 */
	public function getConnection(): IMaintainableDatabase {
		return $this->getDB( DB_PRIMARY );
	}

	/**
	 * @since 7.1.0
	 */
	public function setStore( Store $store ): void {
		$this->store = $store;
	}

	/**
	 * @since 7.1.0
	 */
	public function setMessageReporter( MessageReporter $messageReporter ): void {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 7.1.0
	 */
	public function reportMessage( string $message ): void {
		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 *
	 * @since 7.1.0
	 */
	public function execute() {
		if ( !$this->canExecute() ) {
			exit;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		StoreFactory::clear();

		$store = $this->store ?? $this->getStore();
		$sqlStore = $this->resolveSqlStore( $store );

		$this->initMessageReporter();

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		if ( $sqlStore === null ) {
			$this->messageReporter->reportMessage(
				"\nTable optimization is only available for the SQLStore backend; skipping.\n"
			);

			StoreFactory::clear();

			return true;
		}

		$connectionManager = $applicationFactory->getConnectionManager();

		// #2963 Use the Maintenance DB connection and the DB_ADMIN request so the
		// admin user/pass (if set) is used for the optimization DDL.
		$connectionManager->registerCallbackConnection( DB_PRIMARY, [ $this, 'getConnection' ] );

		$sqlStore->setConnectionManager(
			$connectionManager
		);

		$sqlStore->setMessageReporter(
			$this->messageReporter
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Table optimization' ) . "\n"
		);

		$sqlStore->optimize();

		// Avoid holding a reference
		StoreFactory::clear();

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'OptimizeStoreLogger' );
			$runtimeValues = $maintenanceHelper->getRuntimeValues();

			$log = [
				'Memory used' => $runtimeValues['memory-used'],
				'Time used' => $runtimeValues['humanreadable-time']
			];

			$maintenanceLogger->logFromArray( $log );
		}

		return true;
	}

	private function canExecute(): bool {
		if ( !Setup::isEnabled() ) {
			$this->reportMessage(
				"\nYou need to have SMW enabled in order to run the maintenance script!\n"
			);

			return false;
		}

		if ( !Setup::isValid( true ) ) {
			$this->reportMessage(
				"\nYou need to run `update.php` or `setupStore.php` first before continuing\n" .
				"with this maintenance task!\n"
			);

			return false;
		}

		return true;
	}

	protected function initMessageReporter() {
		$messageReporterFactory = MessageReporterFactory::getInstance();

		if ( $this->messageReporter === null && $this->getOption( 'quiet' ) ) {
			$this->messageReporter = $messageReporterFactory->newNullMessageReporter();
		} elseif ( $this->messageReporter === null ) {
			$this->messageReporter = $messageReporterFactory->newObservableMessageReporter();
			$this->messageReporter->registerReporterCallback( [ $this, 'reportMessage' ] );
		}

		return $this->messageReporter;
	}

	protected function loadGlobalFunctions(): void {
		global $smwgIP;

		// @phan-suppress-next-line MediaWikiNoIssetIfDefined
		if ( !isset( $smwgIP ) ) {
			$smwgIP = __DIR__ . '/../';
		}

		require_once $smwgIP . 'src/GlobalFunctions.php';
	}

	protected function getStore(): Store {
		global $smwgDefaultStore;

		$this->loadGlobalFunctions();

		$storeClass = $this->getOption( 'backend', $smwgDefaultStore );

		if ( class_exists( $storeClass ) ) {
			$smwgDefaultStore = $storeClass;
		} else {
			$this->fatalError( "\nError: There is no backend class \"$storeClass\". Aborting." );
		}

		return StoreFactory::getStore( $storeClass );
	}

	private function resolveSqlStore( Store $store ): ?SQLStore {
		if ( $store instanceof SQLStore ) {
			return $store;
		}

		if ( $store instanceof SPARQLStore && $store->baseStore instanceof SQLStore ) {
			return $store->baseStore;
		}

		return null;
	}

}

// @codeCoverageIgnoreStart
$maintClass = optimizeStore::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
