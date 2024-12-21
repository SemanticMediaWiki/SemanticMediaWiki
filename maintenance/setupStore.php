<?php

namespace SMW\Maintenance;

use SMW\Store;
use SMW\StoreFactory;
use Onoi\MessageReporter\MessageReporterFactory;
use Onoi\MessageReporter\MessageReporter;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\Installer;
use SMW\Setup;
use SMW\Options;
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
 * Sets up the storage backend currently selected in the "LocalSettings.php"
 * file (or the default MySQL store if no other store was selected). This
 * is equivalent to clicking the respective button on the special page
 * Special:SemanticMediaWiki. However, the latter may timeout if the setup
 * involves migrating a lot of existing data.
 *
 * Note:
 * If SMW is not installed in its standard path under ./extensions
 * then the MW_INSTALL_PATH environment variable must be set.
 * See README in the maintenance directory.
 *
 * Usage:
 * php setupStore.php [options...]
 *
 * --delete          Delete all SMW data, uninstall the selected storage backend.
 *                   This is useful when moving to a new storage engine, and in
 *                   the rare case of unsinstalling SMW. Deleted data can be
 *                   recreated using this script (setup) followed by the use
 *                   of the rebuildhData.php script which may take some time.
 *
 * --backend         The backend to use, e.g. SMWSQLStore3.
 *
 * --skip-optimize   Skips the table optimization process.
 *
 * --skip-import     Skips the import process.
 *
 * --nochecks        When specified, no prompts are provided. Deletion will thus happen
 *                   without the need to provide any confirmation.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author James Hong Kong
 */
class setupStore extends \Maintenance {

	/**
	 * Name of the store class configured in the "LocalSettings.php" file. Stored to
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
		$this->addDescription( 'Sets up the SMW storage backend currently selected in LocalSettings.php.' );

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
		return $this->getDB( DB_PRIMARY );
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
		$connectionManager->registerCallbackConnection( DB_PRIMARY, [ $this, 'getConnection' ] );

		$store->setConnectionManager(
			$connectionManager
		);

		$this->initMessageReporter();

		$store->setMessageReporter(
			$this->messageReporter
		);

		$options = new Options(
			[
				Installer::OPT_TABLE_OPTIMIZE => !$this->hasOption( 'skip-optimize' ),
				Installer::RUN_IMPORT => !$this->hasOption( 'skip-import' ),
				Installer::OPT_SUPPLEMENT_JOBS => true,
				'verbose' => $this->getOption( 'quiet', true )
			]
		);

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		if ( $this->hasOption( 'delete' ) ) {
			$this->dropStore( $store );
		} else {
			$store->setup( $options );
		}

		// Avoid holding a reference
		StoreFactory::clear();
	}

	protected function initMessageReporter() {
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

		require_once ( $smwgIP . 'includes/GlobalFunctions.php' );
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
		$cliMsgFormatter = new CliMsgFormatter();

		if ( !$this->hasDeletionVerification() ) {
			return;
		}

		$store->drop( !$this->isQuiet() );

		$text = [
			"You can recreate them with this script followed by the use",
			"of the rebuildData.php script to rebuild their contents."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);
	}

	/**
	 * @param string $storeName
	 *
	 * @return boolean
	 */
	protected function hasDeletionVerification() {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Notice' )
		);

		$text = [
			"You are about to delete all data stored in the SMW backend. To undo",
			"this operation later on, a complete rebuild of the data will be needed."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n\n"
		);

		if ( !$this->hasOption( 'nochecks' ) ) {
			print ( "If you are sure you want to proceed, type DELETE.\n" );

			if ( $this->readconsole() !== 'DELETE' ) {
				print ( "Aborting.\n" );
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

$maintClass = setupStore::class;
require_once ( RUN_MAINTENANCE_IF_MAIN );
