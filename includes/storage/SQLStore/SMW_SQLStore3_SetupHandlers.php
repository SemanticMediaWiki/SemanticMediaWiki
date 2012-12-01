<?php
/**
 * @file
 * @ingroup SMWStore
 * @since 1.8
 */

/**
 * Class Handling all the setup methods for SMWSQLStore3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since 1.8
 * @ingroup SMWStore
 */
class SMWSQLStore3SetupHandlers {

	/**
	 * The store used by this setupHandler
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	protected $store;

	public function __construct( SMWSQLStore3 $parentstore ) {
		$this->store = $parentstore;
	}

	public function setup( $verbose = true ) {
		$this->reportProgress( "Setting up standard database configuration for SMW ...\n\n", $verbose );
		$this->reportProgress( "Selected storage engine is \"SMWSQLStore\" (or an extension thereof)\n\n", $verbose );

		$db = wfGetDB( DB_MASTER );

		$this->setupTables( $verbose, $db );
		$this->setupPredefinedProperties( $verbose, $db );
		$this->refreshPropertyStatistics( $verbose, $db );

		return true;
	}

	/**
	 * Create required SQL tables. This function also performs upgrades of
	 * table contents when required.
	 *
	 * @see SMWSql3SmwIds for documentation of the SMW IDs table.
	 *
	 * @since 1.8
	 * @param boolean $verbose
	 * @param DatabaseBase $db used for writing
	 */
	protected function setupTables( $verbose, $db ) {
		global $wgDBtype;

		$reportTo = $verbose ? $this : null; // Use $this to report back from static SMWSQLHelpers.

		// Repeatedly used DB field types defined here for convenience.
		$dbtypes = array(
			'b' => ( $wgDBtype == 'postgres' ? 'BOOLEAN' : 'TINYINT(1)' ),
			't' => SMWSQLHelpers::getStandardDBType( 'title' ),
			'l' => SMWSQLHelpers::getStandardDBType( 'blob' ),
			'f' => ( $wgDBtype == 'postgres' ? 'DOUBLE PRECISION' : 'DOUBLE' ),
			'i' => ( $wgDBtype == 'postgres' ? 'INTEGER' : 'INT(8)' ),
			'j' => ( $wgDBtype == 'postgres' ? 'INTEGER' : 'INT(8) UNSIGNED' ),
			'p' => SMWSQLHelpers::getStandardDBType( 'id' ),
			'n' => SMWSQLHelpers::getStandardDBType( 'namespace' ),
			'w' => SMWSQLHelpers::getStandardDBType( 'iw' )
		);

		// Set up table for internal IDs used in this store:
		SMWSQLHelpers::setupTable(
			SMWSql3SmwIds::tableName,
			array(
				'smw_id' => $dbtypes['p'] .
					( $wgDBtype == 'sqlite' ? '' : ' NOT NULL' ) .
					( $wgDBtype == 'postgres' ? ' PRIMARY KEY' : ' KEY AUTO_INCREMENT' ) .
					( $wgDBtype == 'sqlite' ? ' NOT NULL' : '' ),
				'smw_namespace' => $dbtypes['n'] . ' NOT NULL',
				'smw_title' => $dbtypes['t'] . ' NOT NULL',
				'smw_iw' => $dbtypes['w'] . ' NOT NULL',
				'smw_subobject' => $dbtypes['t'] . ' NOT NULL',
				'smw_sortkey' => $dbtypes['t']  . ' NOT NULL',
				'smw_proptable_hash' => $dbtypes['l']
			),
			$db,
			$reportTo
		);

		SMWSQLHelpers::setupIndex(
			SMWSql3SmwIds::tableName,
			array(
				'smw_id',
				'smw_id,smw_sortkey',
				'smw_title,smw_namespace,smw_iw,smw_subobject', // id lookup
				'smw_sortkey' // select by sortkey (range queries)
			),
			$db
		);

		// Set up concept cache: member elements (s)->concepts (o)
		SMWSQLHelpers::setupTable(
			SMWSQLStore3::CONCEPT_CACHE_TABLE,
			array(
				's_id' => $dbtypes['p'] . ' NOT NULL',
				'o_id' => $dbtypes['p'] . ' NOT NULL'
			),
			$db,
			$reportTo
		);

		SMWSQLHelpers::setupIndex( SMWSQLStore3::CONCEPT_CACHE_TABLE, array( 'o_id' ), $db );

		// Set up table for stats on Properties (only counts for now)
		SMWSQLHelpers::setupTable(
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE,
			array(
				'p_id' => $dbtypes['p'],
				'usage_count' => $dbtypes['j']
			),
			$db,
			$reportTo
		);

		SMWSQLHelpers::setupIndex( SMWSQLStore3::PROPERTY_STATISTICS_TABLE, array( array( 'p_id', 'UNIQUE' ), 'usage_count' ), $db );

		// Set up all property tables as defined:
		$this->setupPropertyTables( $dbtypes, $db, $reportTo );

		$this->reportProgress( "Database initialised successfully.\n\n", $verbose );
	}

	/**
	 * Sets up the property tables.
	 *
	 * @since 1.8
	 * @param array $dbtypes
	 * @param DatabaseBase|Database $db
	 * @param SMWSQLStore3SetupHandlers|null $reportTo
	 */
	protected function setupPropertyTables( array $dbtypes, $db, SMWSQLStore3SetupHandlers $reportTo = null ) {
		$addedCustomTypeSignatures = false;

		foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
			$diHandler = $this->store->getDataItemHandlerForDIType( $proptable->getDiType() );

			// Prepare indexes. By default, property-value tables
			// have the following indexes:
			//
			// sp: getPropertyValues(), getSemanticData(), getProperties()
			// po: ask, getPropertySubjects()
			//
			// The "p" component is omitted for tables with fixed property.
			$indexes = array();
			if ( $proptable->usesIdSubject() ) {
				$fieldarray = array( 's_id' => $dbtypes['p'] . ' NOT NULL' );
				$indexes['sp'] = 's_id';
			} else {
				$fieldarray = array( 's_title' => $dbtypes['t'] . ' NOT NULL', 's_namespace' => $dbtypes['n'] . ' NOT NULL' );
				$indexes['sp'] = 's_title,s_namespace';
			}

			$indexes['po'] = $diHandler->getIndexField();

			if ( !$proptable->isFixedPropertyTable() ) {
				$fieldarray['p_id'] = $dbtypes['p'] . ' NOT NULL';
				$indexes['po'] = 'p_id,' . $indexes['po'];
				$indexes['sp'] = $indexes['sp'] . ',p_id';
			}

			// TODO Special handling; concepts should be handled differently
			// in the future. See comments in SMW_DIHandler_Concept.php.
			if ( $proptable->getDiType() == SMWDataItem::TYPE_CONCEPT ) {
				unset( $indexes['po'] );
			}

			$indexes = array_merge( $indexes, $diHandler->getTableIndexes() );
			$indexes = array_unique( $indexes );

			foreach ( $diHandler->getTableFields() as $fieldname => $typeid ) {
				// If the type signature is not recognized and the custom signatures have not been added, add them.
				if ( !$addedCustomTypeSignatures && !array_key_exists( $typeid, $dbtypes ) ) {
					wfRunHooks( 'SMWCustomSQLStoreFieldType', array( &$dbtypes ) );
					$addedCustomTypeSignatures = true;
				}

				// Only add the type when the signature was recognized, otherwise ignore it silently.
				if ( array_key_exists( $typeid, $dbtypes ) ) {
					$fieldarray[$fieldname] = $dbtypes[$typeid];
				}
			}

			SMWSQLHelpers::setupTable( $proptable->getName(), $fieldarray, $db, $reportTo );

			SMWSQLHelpers::setupIndex( $proptable->getName(), $indexes, $db, $reportTo );
		}
	}

	/**
	 * Create some initial DB entries for important built-in properties. Having the DB contents predefined
	 * allows us to safe DB calls when certain data is needed. At the same time, the entries in the DB
	 * make sure that DB-based functions work as with all other properties.
	 */
	protected function setupPredefinedProperties( $verbose, $db ) {
		global $wgDBtype;

		$this->reportProgress( "Setting up internal property indices ...\n", $verbose );

		// Check if we already have this structure
		$borderiw = $db->selectField( SMWSql3SmwIds::tableName, 'smw_iw', 'smw_id=' . $db->addQuotes( 50 ) );

		if ( $borderiw != SMW_SQL3_SMWBORDERIW ) {
			$this->reportProgress( "   ... allocating space for internal properties ...\n", $verbose );
			$this->store->smwIds->moveSMWPageID( 50 ); // make sure position 50 is empty

			$db->insert( SMWSql3SmwIds::tableName, array(
					'smw_id' => 50,
					'smw_title' => '',
					'smw_namespace' => 0,
					'smw_iw' => SMW_SQL3_SMWBORDERIW,
					'smw_subobject' => '',
					'smw_sortkey' => ''
				), 'SMW::setup'
			); // put dummy "border element" on index 50

			$this->reportProgress( '   ', $verbose );

			for ( $i = 0; $i < 50; $i++ ) { // make way for built-in ids
				$this->store->smwIds->moveSMWPageID( $i );
				$this->reportProgress( '.', $verbose );
			}

			$this->reportProgress( "   done.\n", $verbose );
		} else {
			$this->reportProgress( "   ... space for internal properties already allocated.\n", $verbose );
		}

		// now write actual properties; do that each time, it is cheap enough and we can update sortkeys by current language
		$this->reportProgress( "   ... writing entries for internal properties ...", $verbose );

		foreach ( SMWSql3SmwIds::$special_ids as $prop => $id ) {
			$p = new SMWDIProperty( $prop );
			$db->replace( SMWSql3SmwIds::tableName, array( 'smw_id' ), array(
					'smw_id' => $id,
					'smw_title' => $p->getKey(),
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_iw' => $this->store->smwIds->getPropertyInterwiki( $p ),
					'smw_subobject' => '',
					'smw_sortkey' => $p->getLabel()
				), 'SMW::setup'
			);
		}

		$this->reportProgress( " done.\n", $verbose );

		if ( $wgDBtype == 'postgres' ) {
			$this->reportProgress( " ... updating smw_ids_smw_id_seq sequence accordingly.\n", $verbose );

			$max = $db->selectField( SMWSql3SmwIds::tableName, 'max(smw_id)', array(), __METHOD__ );
			$max += 1;

			$db->query( "ALTER SEQUENCE smw_ids_smw_id_seq RESTART WITH {$max}", __METHOD__ );
		}

		$this->reportProgress( "Internal properties initialised successfully.\n", $verbose );
	}

	/**
	 * Update the usage count in the property statistics table for all
	 * properties. This function also initialises the required entry for
	 * all properties that have IDs in the SMW IDs table.
	 *
	 * @since 1.8
	 * @param boolean $verbose
	 * @param DatabaseBase $dbw used for writing
	 */
	protected function refreshPropertyStatistics( $verbose, $dbw ) {
		$this->reportProgress( "Updating property statistics. This may take a while.\n", $verbose );

		$res = $dbw->select(
				SMWSql3SmwIds::tableName,
				array( 'smw_id', 'smw_title' ),
				array( 'smw_namespace' => SMW_NS_PROPERTY  ),
				__METHOD__
		);

		$propertyTables = SMWSQLStore3::getPropertyTables();

		foreach ( $res as $row ) {
			$this->reportProgress( '.', $verbose );

			$usageCount = 0;
			foreach ( $propertyTables as $propertyTable ) {

				if ( ( $propertyTable->isFixedPropertyTable() ) &&
					( $propertyTable->getFixedProperty() != $row->smw_title ) ) {
					// This table cannot store values for this property
					continue;
				}

				$propRow = $dbw->selectRow(
						$propertyTable->getName(),
						'Count(*) as count',
						$propertyTable->isFixedPropertyTable() ? array() : array('p_id' => $row->smw_id ),
						__METHOD__
				);
				$usageCount += $propRow->count;
			}

			$dbw->replace(
				SMWSQLStore3::PROPERTY_STATISTICS_TABLE,
				'p_id',
				array(
					'p_id' => $row->smw_id,
					'usage_count' => $usageCount
				),
				__METHOD__
			);
		}

		$this->reportProgress( "\nUpdated statistics for {$res->numRows()} Properties.\n", $verbose );
		$dbw->freeResult( $res );
	}

	public function drop( $verbose = true ) {
		global $wgDBtype;

		$this->reportProgress( "Deleting all database content and tables generated by SMW ...\n\n", $verbose );
		$dbw = wfGetDB( DB_MASTER );
		$tables = array( SMWSql3SmwIds::tableName, SMWSQLStore3::CONCEPT_CACHE_TABLE, SMWSQLStore3::PROPERTY_STATISTICS_TABLE );

		foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
			$tables[] = $proptable->getName();
		}

		foreach ( $tables as $table ) {
			$name = $dbw->tableName( $table );
			$dbw->query( 'DROP TABLE ' . ( $wgDBtype == 'postgres' ? '' : 'IF EXISTS ' ) . $name, 'SMWSQLStore3::drop' );
			$this->reportProgress( " ... dropped table $name.\n", $verbose );
		}

		$this->reportProgress( "All data removed successfully.\n", $verbose );

		return true;
	}

	/**
	 * @see SMWStore::refreshData
	 *
	 * @todo This method will be overhauled in SMW 1.9 to become cleaner
	 * and more robust.
	 *
	 * @param integer $index
	 * @param integer $count
	 * @param mixed $namespaces Array or false
	 * @param boolean $usejobs
	 *
	 * @return decimal between 0 and 1 to indicate the overall progress of the refreshing
	 */
	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		$updatejobs = array();
		$emptyrange = true; // was nothing done in this run?

		// Update by MediaWiki page id --> make sure we get all pages.
		$tids = array();

		// Array of ids
		for ( $i = $index; $i < $index + $count; $i++ ) {
			$tids[] = $i;
		}

		$titles = Title::newFromIDs( $tids );

		foreach ( $titles as $title ) {
			if ( ( $namespaces == false ) || ( in_array( $title->getNamespace(), $namespaces ) ) ) {
				$updatejobs[] = new SMWUpdateJob( $title );
				$emptyrange = false;
			}
		}

		// update by internal SMW id --> make sure we get all objects in SMW
		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			SMWSql3SmwIds::tableName,
			array( 'smw_id', 'smw_title', 'smw_namespace', 'smw_iw', 'smw_subobject' ),
			array(
				"smw_id >= $index ",
				" smw_id < " . $dbr->addQuotes( $index + $count )
			),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$emptyrange = false; // note this even if no jobs were created

			if ( $namespaces && !in_array( $row->smw_namespace, $namespaces ) ) continue;

			// Find page to refresh, even for special properties:
			if ( $row->smw_title != '' && $row->smw_title{0} != '_' ) {
				$titleKey = $row->smw_title;
			} elseif ( $row->smw_namespace == SMW_NS_PROPERTY && $row->smw_iw == '' && $row->smw_subobject == '' ) {
				$titleKey = str_replace( ' ', '_', SMWDIProperty::findPropertyLabel( $row->smw_title ) );
			} else {
				$titleKey = '';
			}

			if ( $row->smw_subobject !== '' ) {
				// leave subobjects alone; they ought to be changed with their pages
			} elseif ( ( $row->smw_iw === '' || $row->smw_iw == SMW_SQL3_SMWREDIIW ) &&
				$titleKey != '' ){
				// objects representing pages
				// TODO: special treament of redirects needed, since the store will
				// not act on redirects that did not change according to its records
				$title = Title::makeTitleSafe( $row->smw_namespace, $titleKey );

				if ( $title !== null && !$title->exists() ) {
					$updatejobs[] = new SMWUpdateJob( $title );
				}
			} elseif ( $row->smw_iw == SMW_SQL3_SMWIW_OUTDATED ) { // remove outdated internal object references
				$dbw = wfGetDB( DB_MASTER );
				foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
					if ( $proptable->usesIdSubject() ) {
						$dbw->delete( $proptable->getName(), array( 's_id' => $row->smw_id ), __METHOD__ );
					}
				}

				$dbw->delete( SMWSql3SmwIds::tableName, array( 'smw_id' => $row->smw_id ), __METHOD__ );
			} elseif ( $titleKey != '' ) { // "normal" interwiki pages or outdated internal objects -- delete
				$diWikiPage = new SMWDIWikiPage( $titleKey, $row->smw_namespace, $row->smw_iw );
				$emptySemanticData = new SMWSemanticData( $diWikiPage );
				$this->store->doDataUpdate( $emptySemanticData );
			}
		}
		$dbr->freeResult( $res );

		wfRunHooks('smwRefreshDataJobs', array(&$updatejobs));

		if ( $usejobs ) {
			Job::batchInsert( $updatejobs );
		} else {
			foreach ( $updatejobs as $job ) {
				$job->run();
			}
		}

		$nextpos = $index + $count;

		if ( $emptyrange ) { // nothing found, check if there will be more pages later on
			$next1 = $dbr->selectField( 'page', 'page_id', "page_id >= $nextpos", __METHOD__, array( 'ORDER BY' => "page_id ASC" ) );
			$next2 = $dbr->selectField( SMWSql3SmwIds::tableName, 'smw_id', "smw_id >= $nextpos", __METHOD__, array( 'ORDER BY' => "smw_id ASC" ) );
			$nextpos = $next2 != 0 && $next2 < $next1 ? $next2 : $next1;
		}

		$max1 = $dbr->selectField( 'page', 'MAX(page_id)', '', __METHOD__ );
		$max2 = $dbr->selectField( SMWSql3SmwIds::tableName, 'MAX(smw_id)', '', __METHOD__ );
		$index = $nextpos ? $nextpos : -1;

		return $index > 0 ? $index / max( $max1, $max2 ) : 1;
	}

	/**
	 * Print some output to indicate progress. The output message is given by
	 * $msg, while $verbose indicates whether or not output is desired at all.
	 */
	public function reportProgress( $msg, $verbose = true ) {
		if ( $verbose ) {
			if ( ob_get_level() == 0 ) { // be sure to have some buffer, otherwise some PHPs complain
				ob_start();
			}

			print $msg;
			ob_flush();
			flush();
		}
	}
}
