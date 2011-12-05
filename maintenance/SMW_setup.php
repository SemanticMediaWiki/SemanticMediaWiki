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
 * Note: For people still using MediaWiki 1.16.x, there is the SMW_setup_1.16.php script.
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

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Sets up the SMW storage backend currently selected in LocalSettings.php.';

		$this->addArg( 'backend', 'Execute the operation for the storage backend of the given name.', false );
		
		$this->addOption( 'delete', 'Delete all SMW data, uninstall the selected storage backend.' );
	}

	public function execute() {
		global $smwgDefaultStore;

		$alternativestore = $this->getArg( 'backend', false );
		$alternativestore = $alternativestore !== false && $alternativestore !== $smwgDefaultStore;
		
		if ( $alternativestore !== false ) {
			$smwgDefaultStore = $this->getArg( 'backend', false );
			print "\nSelected storage " . $smwgDefaultStore . " for update!\n\n";
		}
		
		global $smwgIP;
		if ( !isset( $smwgIP ) ) {
			$smwgIP = dirname( __FILE__ ) . '/../';
		}
		
		require_once( $smwgIP . 'includes/SMW_GlobalFunctions.php' );
		
		if ( $this->hasOption( 'delete' ) ) {
			print "\n  Deleting all stored data for $smwgDefaultStore completely!\n  \n\n";
			if ( $alternativestore ) {
				print "  This store is currently not used by SMW. Deleting it\n  should not cause problems in the wiki.\n\n";
				$delay = 5;
			} else {
				print "  WARNING: This store is currently used by SMW! Deleting it\n           will cause problems in the wiki if SMW is enabled.\n\n";
				$delay = 20;
			}
		
			print "Abort with CTRL-C in the next $delay seconds ...  ";
		
			// TODO
			// Remove the following section and replace it with a simple
			// wfCountDown as soon as we switch to MediaWiki 1.16.
			// Currently, wfCountDown is only supported from
			// revision 51650 (Jun 9 2009) onward.
			if ( function_exists( 'wfCountDown' ) ) {
				wfCountDown( $delay );
			} else {
				for ( $i = $delay; $i >= 0; $i-- ) {
					if ( $i != $delay ) {
						echo str_repeat( "\x08", strlen( $i + 1 ) );
					}
					echo $i;
					flush();
					if ( $i ) {
						sleep( 1 );
					}
				}
				echo "\n";
			}
			// Remove up to here and just uncomment the following line:
			// wfCountDown( $delay );
		
			smwfGetStore()->drop( true );
			wfRunHooks( 'smwDropTables' );
			print "\n";
			while ( ob_get_level() > 0 ) { // be sure to have some buffer, otherwise some PHPs complain
				ob_end_flush();
			}
			echo "\n  All storage structures for $smwgDefaultStore have been deleted.\n  You can recreate them with this script, and then use\n  SMW_refreshData.php to rebuild their contents.";
		} else {
			smwfGetStore()->setup();
			wfRunHooks( 'smwInitializeTables' );
		}
	}

}

$maintClass = 'SMWSetupScript';
require_once( RUN_MAINTENANCE_IF_MAIN );
