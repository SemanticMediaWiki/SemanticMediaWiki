<?php

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
 * php SMW_refreshData.php [options...]
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
 * --nochecks When specied, no promts are provided. Deletion will thus happen
 *            without the need to provide any confomration.
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * 
 * @file
 * @ingroup SMWMaintenance
 */

/**
 * @defgroup SMWMaintenance SMWMaintenance
 * This group contains all parts of SMW that are maintenance scripts.
 * @ingroup SMW
 */

require_once ( getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: dirname( __FILE__ ) . '/../../../maintenance/Maintenance.php' );

class SMWSetupScript extends Maintenance {

	protected $originalStore;

	public function __construct() {
		parent::__construct();

		$this->mDescription = 'Sets up the SMW storage backend currently selected in LocalSettings.php.';

		$this->addOption( 'backend', 'Execute the operation for the storage backend of the given name.' );

		$this->addOption( 'delete', 'Delete all SMW data, uninstall the selected storage backend.' );

		$this->addOption(
			'nochecks',
			'Run the script without providing promts.
				Deletion will thus happen without the need to provide any confomration.'
		);
	}

	public function execute() {
		if ( !defined( 'SMW_VERSION' ) ) {
			echo "You need to have SMW enabled in order to use this maintenance script!\n\n";
			exit;
		}

		$this->loadGlobalFunctions();

		$store = $this->getStore();

		if ( $this->hasOption( 'delete' ) ) {
			$this->dropStore( $store );
		} else {
			SMWStore::setupStore();
		}
	}

	/**
	 * @since 1.8
	 */
	protected function loadGlobalFunctions() {
		global $smwgIP;

		if ( !isset( $smwgIP ) ) {
			$smwgIP = dirname( __FILE__ ) . '/../';
		}

		require_once( $smwgIP . 'includes/SMW_GlobalFunctions.php' );
	}

	/**
	 * @since 1.8
	 *
	 * @return SMWStore
	 */
	protected function getStore() {
		global $smwgDefaultStore;

		$storeClass = $this->getOption( 'backend', $smwgDefaultStore );

		print "\nSelected storage " . $storeClass . " for update!\n\n";

		$this->originalStore = $smwgDefaultStore;

		$smwgDefaultStore = $storeClass;

		return smwfGetStore();
	}

	/**
	 * @since 1.8
	 *
	 * @param SMWStore $store
	 */
	protected function dropStore( SMWStore $store ) {
		$storeName = get_class( $store );

		$verification = $this->promtDeletionVerification( $storeName );

		if ( !$verification ) {
			return;
		}

		$store->drop( true );

		// TODO: this hook should be run on all calls to SMWSTore::drop
		wfRunHooks( 'smwDropTables' );

		// be sure to have some buffer, otherwise some PHPs complain
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}

		echo <<<EOT

All storage structures for $storeName have been deleted.
You can recreate them with this script, and then use SMW_refreshData.php to rebuild their contents.


EOT;
	}

	/**
	 * @since 1.8
	 *
	 * @param string $storeName
	 *
	 * @return boolean
	 */
	protected function promtDeletionVerification( $storeName ) {
		echo "You are about to delete all data stored in the SMW backend $storeName.\n";

		if ( $storeName === $this->originalStore ) {
			echo "This backend is CURRENTLY IN USE. Deleting it is likely to BREAK YOUR WIKI.\n";
		} else {
			echo "This backend is not currently in use. Deleting it should not cause any problems.\n";
		}

		if ( !$this->hasOption( 'nochecks' ) ) {
			echo "This operation cannot be undone directly. If you are sure you want to proceed, type DELETE\n";

			if ( $this->readconsole() !== 'DELETE' ) {
				echo "Aborting.\n\n";
				return false;
			}
		}
		return true;
	}

}

$maintClass = 'SMWSetupScript';
require_once( RUN_MAINTENANCE_IF_MAIN );
