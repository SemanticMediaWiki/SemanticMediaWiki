<?php

/**
 * This Command line Script will setup Store3 and migrate Semantics from Store2 to Store3(version 1.8)
 * As of now it is only recommended for users migrating from Store2 (version 1.7)
 * If you are making a fresh installation please follow the installation
 * guidelines at http://www.semantic-mediawiki.org/wiki/Installation (you don't need this)
 *
 * @file
 * @ingroup SMWMaintenance
 * @author Nischay Nahata
 * @author mwjames
 */

/**
 * @defgroup SMWMaintenance SMWMaintenance
 * This group contains all parts of SMW that are maintenance scripts.
 * @ingroup SMW
 */

require_once ( getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: dirname( __FILE__ ) . '/../../../maintenance/Maintenance.php' );

class SMWMigrate extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Sets up the SMW storage backend for Store3 (Version 1.8) This does not depend on your default backend.';
		$this->addOption( 'setup', 'Setup all tables needed for Store3.' );
		$this->addOption( 'migrate', 'Migrate Semantics of pages to Store3. This will still leave Store2 data intact so don\'t worry' );
		$this->addOption( 'b', 'Batch size', false, true );
		$this->addOption( 'i', 'Sleep interval between batch inserts', false, true );
	}

	public function execute() {
		if ( $this->hasOption( 'setup' ) ) {
			$store = new SMWSQLStore3;
			$store->setup();
		} elseif ( $this->hasOption( 'migrate' ) ) {
			$batchsize = $this->getOption( 'b', 500 );
			$interval  = $this->getOption( 'i', 0 );

			// We do all the hard work in batch
			Job::factory(
				'SMWMigrationJob',
				Title::newFromText( 'MigrationPackingJob' ),
				array( 'pos' => 0, 'b' => $batchsize, 'i' => $interval )
			)->insert();
		}
	}
}

$maintClass = 'SMWMigrate';
require_once( RUN_MAINTENANCE_IF_MAIN );