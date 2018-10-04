<?php

namespace SMW\Maintenance;

use SMW\Store;
use SMW\StoreFactory;
use Onoi\MessageReporter\MessageReporterFactory;
use Onoi\MessageReporter\MessageReporter;
use SMW\ApplicationFactory;
use SMW\SQLStore\Installer;
use SMW\Setup;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Sets up the storage backend currently selected in LocalSettings.php
 * (or the default MySQL store if no other store was selected). This
 * is equivalent to clicking the respective button on the special page
 * Special:SMWAdmin. However, the latter may timeout if the setup involves
 * migrating a lot of existing data.
 *
 * Note: If SMW is not installed in its standard path under ./extensions
 *       then the MW_INSTALL_PATH environment variable must be set.
 *       See README in the maintenance directory.
 *
 * Usage:
 * php setupStore.php [options...]
 *
 * -password Password for user account
 * NOTE: specifying user credentials in a command line call will usually store them
 * within the shell history file. For security, provide credentials in Adminssetings.php
 * instead and ensure that your text editor does not create world-readable backup copies
 * when modifying this file.
 *
 * --delete   Delete all SMW data, uninstall the selected storage backend. This is useful
 *            when moving to a new storage engine, and in the rare case of unsinstalling
 *            SMW. Deleted data can be recreated using this script (setup) followed by the
 *            use of the rebuildhData.php script which may take some time.
 *
 * --backend  The backend to use, e.g. SMWSQLStore3.
 *
 * --skip-optimize Skips the table optimization process.
 *
 * --skip-import Skips the import process.
 *
 * --nochecks When specified, no prompts are provided. Deletion will thus happen
 *            without the need to provide any confirmation.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SetupStore extends \Maintenance {

	/**
	 * Name of the store class configured in LocalSettings.php. Stored to
	 * be able to tell if the selected store is the currecnt default or not.
	 *
	 * @var string
	 */
	protected $originalStore;

	/**
	 * @var MessageReporter
	 */
	protected $messageReporter;

	/**
	 * @since 2.0
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * @see Maintenance::addDefaultParams
	 *
	 * @since 2.0
	 */
	protected function addDefaultParams() {
		parent::addDefaultParams();

		$this->mDescription = 'Sets up the SMW storage backend currently selected in LocalSettings.php.';

		$this->addOption( 'backend', 'Execute the operation for the storage backend of the given name.', false, true, 'b' );

		$this->addOption( 'delete', 'Delete all SMW data, uninstall the selected storage backend.' );
		$this->addOption( 'skip-optimize', 'Skipping the table optimization process (not recommended).', false );
		$this->addOption( 'skip-import', 'Skipping the import process.', false );

		$this->addOption(
			'nochecks',
			'Run the script without providing prompts. Deletion will thus happen without the need to provide any confirmation.'
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @see Maintenance::getDbType
	 *
	 * @since 3.0
	 */
	public function getDbType() {
		return \Maintenance::DB_ADMIN;
	}

	/**
	 * @since 3.0
	 */
	public function getConnection() {
		return $this->getDB( DB_MASTER );
	}

	/**
	 * @see Maintenance::execute
	 *
	 * @since 2.0
	 */
	public function execute() {

		if ( !Setup::isEnabled() ) {
			$this->reportMessage( "\nYou need to have SMW enabled in order to run the maintenance script!\n" );
			exit;
		}

		StoreFactory::clear();

		$this->loadGlobalFunctions();
		$store = $this->getStore();

		$connectionManager = ApplicationFactory::getInstance()->getConnectionManager();

		// #2963 Use the Maintenance DB connection instead and the DB_ADMIN request
		// to allow to use the admin user/pass, if set
		$connectionManager->registerCallbackConnection( DB_MASTER, [ $this, 'getConnection' ] );

		$store->setConnectionManager(
			$connectionManager
		);

		$store->setMessageReporter(
			$this->getMessageReporter()
		);

		$store->setOption( Installer::OPT_TABLE_OPTIMIZE, !$this->hasOption( 'skip-optimize' ) );
		$store->setOption( Installer::OPT_IMPORT, !$this->hasOption( 'skip-import' ) );
		$store->setOption( Installer::OPT_SUPPLEMENT_JOBS, true );

		if ( $this->hasOption( 'delete' ) ) {
			$this->dropStore( $store );
		} else {
			$store->setup();
		}

		// Avoid holding a reference
		StoreFactory::clear();
	}

	protected function getMessageReporter() {

		$messageReporterFactory = MessageReporterFactory::getInstance();

		if ( $this->messageReporter === null && $this->getOption( 'quiet' ) ) {
			$this->messageReporter = $messageReporterFactory->newNullMessageReporter();
		} elseif( $this->messageReporter === null ) {
			$this->messageReporter = $messageReporterFactory->newObservableMessageReporter();
			$this->messageReporter->registerReporterCallback( [ $this, 'reportMessage' ] );
		}

		return $this->messageReporter;
	}

	protected function loadGlobalFunctions() {
		global $smwgIP;

		if ( !isset( $smwgIP ) ) {
			$smwgIP = dirname( __FILE__ ) . '/../';
		}

		require_once ( $smwgIP . 'src/GlobalFunctions.php' );
	}

	protected function getStore() {
		global $smwgDefaultStore;

		$storeClass = $this->getOption( 'backend', $smwgDefaultStore );
		$this->originalStore = $smwgDefaultStore;

		if ( class_exists( $storeClass ) ) {
			$smwgDefaultStore = $storeClass;
		} else {
			$this->error( "\nError: There is no backend class \"$storeClass\". Aborting.", 1 );
		}

		return StoreFactory::getStore( $storeClass );
	}

	protected function dropStore( Store $store ) {
		$storeName = get_class( $store );

		$verification = $this->promptDeletionVerification( $storeName );

		if ( !$verification ) {
			return;
		}

		$store->drop( !$this->isQuiet() );

		// be sure to have some buffer, otherwise some PHPs complain
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}

		$this->output( "\nYou can recreate them with this script followed by the use\n");
		$this->output( "of the rebuildData.php script to rebuild their contents.\n");
	}

	/**
	 * @param string $storeName
	 *
	 * @return boolean
	 */
	protected function promptDeletionVerification( $storeName ) {
		$this->output( "You are about to delete all data stored in the SMW backend $storeName.\n" );

		if ( $storeName === $this->originalStore ) {
			$this->output( "This backend is CURRENTLY IN USE. Deleting it is likely to BREAK YOUR WIKI.\n" );
		} else {
			$this->output( "This backend is not currently in use. Deleting it should not cause any problems.\n" );
		}
		$this->output( "To undo this operation later on, a complete refresh of the data will be needed.\n" );

		if ( !$this->hasOption( 'nochecks' ) ) {
			print ( "If you are sure you want to proceed, type DELETE.\n" );

			if ( $this->readconsole() !== 'DELETE' ) {
				print ( "Aborting.\n\n" );
				return false;
			}
		}
		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

}

$maintClass = 'SMW\Maintenance\SetupStore';
require_once ( RUN_MAINTENANCE_IF_MAIN );
