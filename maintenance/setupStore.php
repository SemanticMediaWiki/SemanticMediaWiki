<?php

namespace SMW\Maintenance;

use SMW\Store;
use SMW\StoreFactory;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Sets up the storage backend currently selected in LocalSettings.php
 * (or the default MySQL store if no other store was selected). This
 * is equivalent to clicking the respective button on the page
 * Special:SMWAdmin. However, the latter may timeout if the setup involves
 * migrating a lot of existing data.
 *
 * Note: if SMW is not installed in its standard path under ./extensions
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
 *            SMW. Deleted data can be recreated using this script (setup) and
 *            SMW_refreshData.php but this may take some time.
 *
 * --backend  The backend to use. For instance SMWSQLStore3 or SMWSQLStore2.
 *
 * --nochecks When specied, no prompts are provided. Deletion will thus happen
 *            without the need to provide any confomration.
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
	 * @since 2.0
	 */
	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Sets up the SMW storage backend currently selected in LocalSettings.php.';

		$this->addOption( 'backend', 'Execute the operation for the storage backend of the given name.', false, true, 'b' );

		$this->addOption( 'delete', 'Delete all SMW data, uninstall the selected storage backend.' );

		$this->addOption(
			'nochecks',
			'Run the script without providing prompts. Deletion will thus happen without the need to provide any confirmation.'
		);
	}

	/**
	 * @since 2.0
	 */
	public function execute() {
		// TODO It would be good if this script would work with SMW not being enable (yet).
		// Then one could setup the store without first enabling SMW (which will break the wiki until the store is setup).
		if ( !defined( 'SMW_VERSION' ) ) {
			$this->output( "You need to have SMW enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$this->loadGlobalFunctions();

		$store = $this->getStore();

		if ( $this->hasOption( 'delete' ) ) {
			$this->dropStore( $store );
		} else {
			Store::setupStore( !$this->isQuiet() );
		}
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

		$this->output( "\nSelected storage \"$smwgDefaultStore\" for update!\n\n" );

		return StoreFactory::getStore();
	}

	protected function dropStore( Store $store ) {
		$storeName = get_class( $store );

		$verification = $this->promptDeletionVerification( $storeName );

		if ( !$verification ) {
			return;
		}

		$store->drop( !$this->isQuiet() );

		// TODO: this hook should be run on all calls to SMWStore::drop
		wfRunHooks( 'smwDropTables' );

		// be sure to have some buffer, otherwise some PHPs complain
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}

		$this->output( "\nAll storage structures for $storeName have been deleted." );
		$this->output( "You can recreate them with this script, and then use SMW_refreshData.php to rebuild their contents.\n\n");
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

}

$maintClass = 'SMW\Maintenance\SetupStore';
require_once ( RUN_MAINTENANCE_IF_MAIN );
