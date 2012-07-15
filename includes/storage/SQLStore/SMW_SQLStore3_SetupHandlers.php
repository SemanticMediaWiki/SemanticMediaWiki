<?php

/**
 * Class Handling all the setup methods for SMWSQLStore3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author Nischay Nahata
 *
 * @since SMW.storerewrite
 * @file
 * @ingroup SMWStore
 */

Class SMWSQLStore3SetupHandlers {

	/**
	 * The store used by this setupHandler
	 *
	 * @since SMW.storerewrite
	 * @var SMWSQLStore3
	 */
	protected $store;


	public function __construct( &$parentstore ) {
		$this->store = $parentstore;
	}

	public function setup( $verbose = true ) {
		$this->reportProgress( "Setting up standard database configuration for SMW ...\n\n", $verbose );
		$this->reportProgress( "Selected storage engine is \"SMWSQLStore\" (or an extension thereof)\n\n", $verbose );

		$db = wfGetDB( DB_MASTER );

		$this->setupTables( $verbose, $db );
		$this->setupPredefinedProperties( $verbose, $db );

		return true;
	}

	/**
	 * Create required SQL tables. This function also performs upgrades of
	 * table contents when required.
	 *
	 * Documentation for the table smw_ids: This table is normally used to
	 * store references to wiki pages (possibly with some external interwiki
	 * prefix). There are, however, some special objects that are also
	 * stored therein. These are marked by special interwiki prefixes (iw)
	 * that cannot occcur in real life:
	 *
	 * - Rows with iw SMW_SQL2_SMWREDIIW are similar to normal entries for
	 * (internal) wiki pages, but the iw indicates that the page is a
	 * redirect, the target of which should be sought using the smw_redi2
	 * table.
	 *
	 * - The (unique) row with iw SMW_SQL2_SMWBORDERIW just marks the
	 * border between predefined ids (rows that are reserved for hardcoded
	 * ids built into SMW) and normal entries. It is no object, but makes
	 * sure that SQL's auto increment counter is high enough to not add any
	 * objects before that marked "border".
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
			'smw_ids',
			array(
				'smw_id' => $dbtypes['p'] . ' NOT NULL' . ( $wgDBtype == 'postgres' ? ' PRIMARY KEY' : ' KEY AUTO_INCREMENT' ),
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

		SMWSQLHelpers::setupIndex( 'smw_ids', array( 'smw_id', 'smw_title,smw_namespace,smw_iw', 'smw_title,smw_namespace,smw_iw,smw_subobject', 'smw_sortkey' ), $db );

		// Set up concept cache: member elements (s)->concepts (o)
		SMWSQLHelpers::setupTable(
			'smw_conccache',
			array(
				's_id' => $dbtypes['p'] . ' NOT NULL',
				'o_id' => $dbtypes['p'] . ' NOT NULL'
			),
			$db,
			$reportTo
		);

		SMWSQLHelpers::setupIndex( 'smw_conccache', array( 'o_id' ), $db );

		// Set up all property tables as defined:
		$this->setupPropertyTables( $dbtypes, $db, $reportTo );

		$this->reportProgress( "Database initialised successfully.\n\n", $verbose );
	}

	/**
	 * Sets up the property tables.
	 *
	 * @param array $dbtypes
	 * @param $db
	 * @param $reportTo SMWSQLStore3 or null
	 */
	protected function setupPropertyTables( array $dbtypes, $db, $reportTo ) {
		$addedCustomTypeSignatures = false;

		foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
			if ( $proptable->idsubject ) {
				$fieldarray = array( 's_id' => $dbtypes['p'] . ' NOT NULL' );
				$indexes = array( 's_id' );
			} else {
				$fieldarray = array( 's_title' => $dbtypes['t'] . ' NOT NULL', 's_namespace' => $dbtypes['n'] . ' NOT NULL' );
				$indexes = array( 's_title,s_namespace' );
			}

			if ( !$proptable->fixedproperty ) {
				$fieldarray['p_id'] = $dbtypes['p'] . ' NOT NULL';
				$indexes[] = 'p_id';
			}

			foreach ( $proptable->objectfields as $fieldname => $typeid ) {
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

			$indexes = array_merge( $indexes, $proptable->indexes );

			SMWSQLHelpers::setupTable( $proptable->name, $fieldarray, $db, $reportTo );
			SMWSQLHelpers::setupIndex( $proptable->name, $indexes, $db );
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
		$borderiw = $db->selectField( 'smw_ids', 'smw_iw', 'smw_id=' . $db->addQuotes( 50 ) );

		if ( $borderiw != SMW_SQL2_SMWBORDERIW ) {
			$this->reportProgress( "   ... allocating space for internal properties ...\n", $verbose );
			$this->store->moveSMWPageID( 50 ); // make sure position 50 is empty

			$db->insert( 'smw_ids', array(
					'smw_id' => 50,
					'smw_title' => '',
					'smw_namespace' => 0,
					'smw_iw' => SMW_SQL2_SMWBORDERIW,
					'smw_subobject' => '',
					'smw_sortkey' => ''
				), 'SMW::setup'
			); // put dummy "border element" on index 50

			$this->reportProgress( '   ', $verbose );

			for ( $i = 0; $i < 50; $i++ ) { // make way for built-in ids
				$this->store->moveSMWPageID( $i );
				$this->reportProgress( '.', $verbose );
			}

			$this->reportProgress( "   done.\n", $verbose );
		} else {
			$this->reportProgress( "   ... space for internal properties already allocated.\n", $verbose );
		}

		// now write actual properties; do that each time, it is cheap enough and we can update sortkeys by current language
		$this->reportProgress( "   ... writing entries for internal properties ...", $verbose );

		foreach ( SMWSQLStore3::$special_ids as $prop => $id ) {
			$p = new SMWDIProperty( $prop );
			$db->replace( 'smw_ids',	array( 'smw_id' ), array(
					'smw_id' => $id,
					'smw_title' => $p->getKey(),
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_iw' => $this->store->getPropertyInterwiki( $p ),
					'smw_subobject' => '',
					'smw_sortkey' => $p->getKey()
				), 'SMW::setup'
			);
		}

		$this->reportProgress( " done.\n", $verbose );

		if ( $wgDBtype == 'postgres' ) {
			$this->reportProgress( " ... updating smw_ids_smw_id_seq sequence accordingly.\n", $verbose );

			$max = $db->selectField( 'smw_ids', 'max(smw_id)', array(), __METHOD__ );
			$max += 1;

			$db->query( "ALTER SEQUENCE smw_ids_smw_id_seq RESTART WITH {$max}", __METHOD__ );
		}

		$this->reportProgress( "Internal properties initialised successfully.\n", $verbose );
	}

	public function drop( $verbose = true ) {
		global $wgDBtype;

		$this->reportProgress( "Deleting all database content and tables generated by SMW ...\n\n", $verbose );
		$dbw = wfGetDB( DB_MASTER );
		$tables = array( 'smw_ids', 'smw_conccache' );

		foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
			$tables[] = $proptable->name;
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
			'smw_ids',
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

			if ( $row->smw_subobject !== '' ) {
				// leave subobjects alone; they ought to be changed with their pages
			} elseif ( $row->smw_iw === '' || $row->smw_iw == SMW_SQL2_SMWREDIIW ) { // objects representing pages
				// TODO: special treament of redirects needed, since the store will
				// not act on redirects that did not change according to its records
				$title = Title::makeTitleSafe( $row->smw_namespace, $row->smw_title );

				if ( $title !== null && !$title->exists() ) {
					$updatejobs[] = new SMWUpdateJob( $title );
				}
			} elseif ( $row->smw_iw == SMW_SQL2_SMWIW_OUTDATED ) { // remove outdated internal object references
				foreach ( SMWSQLStore3::getPropertyTables() as $proptable ) {
					if ( $proptable->idsubject ) {
						$dbr->delete( $proptable->name, array( 's_id' => $row->smw_id ), __METHOD__ );
					}
				}

				$dbr->delete( 'smw_ids',	array( 'smw_id' => $row->smw_id ), __METHOD__ );
			} else { // "normal" interwiki pages or outdated internal objects
				$diWikiPage = new SMWDIWikiPage( $row->smw_title, $row->smw_namespace, $row->smw_iw );
				$this->store->deleteSemanticData( $diWikiPage );
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
			$next2 = $dbr->selectField( 'smw_ids', 'smw_id', "smw_id >= $nextpos", __METHOD__, array( 'ORDER BY' => "smw_id ASC" ) );
			$nextpos = $next2 != 0 && $next2 < $next1 ? $next2 : $next1;
		}

		$max1 = $dbr->selectField( 'page', 'MAX(page_id)', '', __METHOD__ );
		$max2 = $dbr->selectField( 'smw_ids', 'MAX(smw_id)', '', __METHOD__ );
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
