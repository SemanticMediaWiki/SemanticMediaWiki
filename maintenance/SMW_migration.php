<?php

/**
 * This Command line Script will setup Store3 and migrate Semantics from Store2 to Store3(version 1.8)
 * As of now it is only recommended for users migrating from Store2 (version 1.7)
 * If you are making a fresh installation please follow the installation
 * guidelines at http://www.semantic-mediawiki.org/wiki/Installation (you don't need this)
 *
 * @author Nischay Nahata
 * @file
 * @ingroup SMWMaintenance
 */

/**
 * @defgroup SMWMaintenance SMWMaintenance
 * This group contains all parts of SMW that are maintenance scripts.
 * @ingroup SMW
 */

die( "\nThis script is not functional. Please run SMW_refreshData.php instead.\nYou can also do this to initialise SQLStore3 while still using SQLStore2:\n* first run \"php SMW_refreshData.php -b SMWSQLStore3 -fp\" to recreate tables and initialise property pages,\n* then run \"php SMW_refreshData.php -b SMWSQLStore3\" again to refresh all normal pages.\n\n" );

require_once ( getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: dirname( __FILE__ ) . '/../../../maintenance/Maintenance.php' );

class SMWMigrate extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Sets up the SMW storage backend for Store3 (Version 1.8) This does not depend on your default backend.';

		$this->addOption( 'setup', 'Setup all tables needed for Store3.' );
		$this->addOption( 'migrate', 'Migrate Semantics of pages in specified id limits to Store3. This will still leave Store2 data intact so don\'t worry' );
		$this->addArg( 'limit', 'Migrate data for these many properties starting from offset', false );
		$this->addArg( 'offset', 'Migrate data from this property offset.', false );
	}

	public function execute() {
		global $wgDBtype;
		if ( $this->hasOption( 'setup' ) ) {
			$store = new SMWSQLStore3;

			// Lets do a drop to ensure the user doesn't has any Store3 tables already (happens when running this script twice)
			$tables = array( 'smw_stats' );
			foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
				$tables[] = $proptable->name;
			}

			$dbw = wfGetDB( DB_MASTER );
			foreach ( $tables as $table ) {
				$name = $dbw->tableName( $table );
				$dbw->query( 'DROP TABLE ' . ( $wgDBtype == 'postgres' ? '' : 'IF EXISTS ' ) . $name, 'SMWMigrate::drop' );
			}
			$store->setup();
			//enter user defined properties into smw_stats (internal ones are handled by setup already )

			$query = 'Replace into ' . $dbw->tableName('smw_stats') . ' (pid,usage_count) Select smw_id,0 from ' . $dbw->tableName('smw_ids') . ' where smw_namespace = '. SMW_NS_PROPERTY .' and smw_iw = "" ';
			$dbw->query( $query, 'SMWMigrate:commandLine' );

		} elseif ( $this->hasOption( 'migrate' ) ) {

			$options = array();
			if ( $this->hasArg( 0 ) ) {
				if ( $this->hasArg( 1 ) ) {
					$options['LIMIT'] = $this->getArg( 0 );
					$options['OFFSET'] = $this->getArg( 1 );
				}
			}
			$dbw = wfGetDB( DB_MASTER );
			$oldStore = new SMWSQLStore2();
			$newStore = new SMWSQLStore3();
			$proptables = SMWSQLStore3::getPropertyTables();
			//get properties
			$res = $dbw->select(
				'smw_ids',
				array(
					'smw_id',
					'smw_title',
					'smw_namespace'
				),
				array(
					'smw_namespace' => SMW_NS_PROPERTY
				),
				__METHOD__,
				$options
			);
			foreach ( $res as $row ) {
				$property = new SMWDIProperty( $row->smw_title );
				echo 'Now migrating data for Property '.$property->getLabel()." into Store3 \n";
				//get the table
				$tableId = SMWSQLStore3::findPropertyTableID( $property );
				$proptable = $proptables[$tableId];
				//get the DIHandler
				$dataItemId = SMWDataValueFactory::getDataItemId( $property->findPropertyTypeId() );
				$diHandler = $newStore->getDataItemHandlerForDIType( $dataItemId );

				$subjects = $oldStore->getPropertySubjects( $property, null );
				$insertions = array();
				foreach ( $subjects as $subject) {
					$sid = $newStore->makeSMWPageID( $subject->getDBkey(), $subject->getNamespace(),
						$subject->getInterwiki(), $subject->getSubobjectName(), true,
						str_replace( '_', ' ', $subject->getDBkey() ) . $subject->getSubobjectName() );

					//now prepare udpates
					$propvals = $oldStore->getPropertyValues( $subject, $property );
					$uvals = $proptable->idsubject ? array( 's_id' => $sid ) :
							 array( 's_title' => $subject->getDBkey(), 's_namespace' => $subject->getNamespace() );
					if ( $proptable->fixedproperty == false ) {
						$uvals['p_id'] = $newStore->makeSMWPropertyID( $property );
					}
					foreach( $propvals as $propval ) {
						$uvals = array_merge( $uvals, $diHandler->getInsertValues( $propval ) );
						$insertions[] = $uvals;
					}
				}
				// now write to the DB for all subjects (is this too much?)
				$dbw->insert( $proptable->name, $insertions, "SMW::migrate$proptable->name" );
			}
			$dbw->freeResult( $res );
		} else {
			echo "Sorry I refuse to work without any options currently";
		}
	}
}

$maintClass = 'SMWMigrate';
require_once( RUN_MAINTENANCE_IF_MAIN );
