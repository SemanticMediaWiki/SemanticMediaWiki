<?php

use Onoi\MessageReporter\MessageReporter;
use SMW\CompatibilityMode;
use SMW\SQLStore\SQLStoreFactory;
use Onoi\MessageReporter\MessageReporterFactory;

/**
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
class SMWSQLStore3SetupHandlers implements MessageReporter {

	/**
	 * The store used by this setupHandler
	 *
	 * @since 1.8
	 * @var SMWSQLStore3
	 */
	protected $store;

	private $factory;

	public function __construct( SMWSQLStore3 $parentstore ) {
		$this->store = $parentstore;
		$this->factory = new SQLStoreFactory( $parentstore );
	}

	public function setup( $verbose = true ) {

		// If for some reason the enableSemantics was not yet enabled
		// still allow to run the tables create in order for the
		// setup to be completed
		if ( CompatibilityMode::extensionNotEnabled() ) {
			CompatibilityMode::enableTemporaryCliUpdateMode();
		}

		$this->reportProgress( "Setting up standard database configuration for SMW ...\n\n", $verbose );
		$this->reportProgress( "Selected storage engine is \"SMWSQLStore3\" (or an extension thereof)\n\n", $verbose );

		$db = $this->store->getConnection( DB_MASTER );

		$this->setupTables( $verbose, $db );
		$this->setupPredefinedProperties( $verbose, $db );

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

		$tableBuilder = $this->factory->newTableBuilder();

		if ( $verbose ) {
			$messageReporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
			$messageReporter->registerReporterCallback( array( $this , 'reportProgress' ) );

			$tableBuilder->setMessageReporter( $messageReporter );
		}

		// Repeatedly used DB field types defined here for convenience.
		$dbtypes = array(
			'b' => $tableBuilder->getStandardFieldType( 'boolean' ),
			't' => $tableBuilder->getStandardFieldType( 'title' ),
			's' => $tableBuilder->getStandardFieldType( 'sort' ),
			'l' => $tableBuilder->getStandardFieldType( 'blob' ),
			'f' => $tableBuilder->getStandardFieldType( 'double' ),
			'i' => $tableBuilder->getStandardFieldType( 'integer' ),
			'j' => $tableBuilder->getStandardFieldType( 'integer unsigned' ),
			'u' => $tableBuilder->getStandardFieldType( 'usage count' ),
			'p' => $tableBuilder->getStandardFieldType( 'id' ),
			'n' => $tableBuilder->getStandardFieldType( 'namespace' ),
			'w' => $tableBuilder->getStandardFieldType( 'iw' )
		);

		// Set up table for internal IDs used in this store:
		$tableBuilder->createTable(
			SMWSQLStore3::ID_TABLE,
			array(
				'fields' => array(
					'smw_id' => $tableBuilder->getStandardFieldType( 'id primary' ),
					'smw_namespace' => $dbtypes['n'] . ' NOT NULL',
					'smw_title' => $dbtypes['t'] . ' NOT NULL',
					'smw_iw' => $dbtypes['w'] . ' NOT NULL',
					'smw_subobject' => $dbtypes['t'] . ' NOT NULL',
					'smw_sortkey' => $dbtypes['t']  . ' NOT NULL',
					'smw_proptable_hash' => $dbtypes['l']
				)
			)
		);

		$tableBuilder->createIndex(
			SMWSQLStore3::ID_TABLE,
			array(
				'indicies' => array(
					'smw_id',
					'smw_id,smw_sortkey',
					'smw_iw', // iw match lookup
					'smw_title,smw_namespace,smw_iw,smw_subobject', // id lookup
					'smw_sortkey', // select by sortkey (range queries)
				)
			)
		);

		// Set up concept cache: member elements (s)->concepts (o)
		$tableBuilder->createTable(
			SMWSQLStore3::CONCEPT_CACHE_TABLE,
			array(
				'fields' => array(
					's_id' => $dbtypes['p'] . ' NOT NULL',
					'o_id' => $dbtypes['p'] . ' NOT NULL'
				)
			)
		);

		$tableBuilder->createIndex(
			SMWSQLStore3::CONCEPT_CACHE_TABLE,
			array(
				'indicies' => array(
					'o_id'
				)
			)
		);

		// Set up ...
		$tableBuilder->createTable(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			array(
				'fields' => array(
					's_id' => $dbtypes['p'] . ' NOT NULL',
					'o_id' => $dbtypes['p'] . ' NOT NULL'
				)
			)
		);

		$tableBuilder->createIndex(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			array(
				'indicies' => array(
					's_id',
					'o_id',
					's_id,o_id'
				)
			)
		);

		// TEXT and BLOB is stored off the table with the table just having a pointer
		// VARCHAR is stored inline with the table
		$tableBuilder->createTable(
			SMWSQLStore3::FT_SEARCH_TABLE,
			array(
				'fields' => array(
					's_id' => $dbtypes['p'] . ' NOT NULL',
					'p_id' => $dbtypes['p'] . ' NOT NULL',
					'o_text' => 'TEXT',
					'o_sort' => $dbtypes['t'],
				),
				'ftSearchOptions'  => $GLOBALS['smwgFulltextSearchTableOptions']
			)
		);

		$tableBuilder->createIndex(
			SMWSQLStore3::FT_SEARCH_TABLE,
			array(
				'indicies' => array(
					's_id',
					'p_id',
					'o_sort',
					array( 'o_text', 'FULLTEXT' )
				),
				'ftSearchOptions' => $GLOBALS['smwgFulltextSearchTableOptions']
			)
		);

		// Set up table for stats on Properties (only counts for now)
		$tableBuilder->createTable(
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE,
			array(
				'fields' => array(
					'p_id' => $dbtypes['p'],
					'usage_count' => $dbtypes['u']
				)
			)
		);

		$tableBuilder->createIndex(
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE,
			array(
				'indicies' => array(
					array( 'p_id', 'UNIQUE INDEX' ),
					'usage_count'
				)
			)
		);

		// Set up all property tables as defined:
		$this->setupPropertyTables( $tableBuilder, $dbtypes );

		$this->reportProgress( "Database initialized successfully.\n\n", $verbose );
	}

	/**
	 * Sets up the property tables.
	 *
	 * @since 1.8
	 * @param array $dbtypes
	 */
	protected function setupPropertyTables( $tableBuilder, array $dbtypes ) {
		$addedCustomTypeSignatures = false;

		foreach ( $this->store->getPropertyTables() as $proptable ) {

			// Only extensions that aren't setup correctly can force an exception
			// and to avoid a failure during setup, ensure that standard tables
			// are correctly initialized otherwise SMW can't recover
			try {
				$diHandler = $this->store->getDataItemHandlerForDIType( $proptable->getDiType() );
			} catch ( \Exception $e ) {
				continue;
			}

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

					// @Depreceated since 2.5
					\Hooks::run( 'SMWCustomSQLStoreFieldType', array( &$dbtypes ) );

					\Hooks::run( 'SMW::SQLStore::AddCustomDatabaseFieldType', array( &$dbtypes ) );
					$addedCustomTypeSignatures = true;
				}

				// Only add the type when the signature was recognized, otherwise ignore it silently.
				if ( array_key_exists( $typeid, $dbtypes ) ) {
					$fieldarray[$fieldname] = $dbtypes[$typeid];
				}
			}

			$tableBuilder->createTable(
				$proptable->getName(),
				array(
					'fields' => $fieldarray
				)
			);

			$tableBuilder->createIndex(
				$proptable->getName(),
				array(
					'indicies' => $indexes
				)
			);
		}
	}

	/**
	 * Create some initial DB entries for important built-in properties. Having the DB contents predefined
	 * allows us to safe DB calls when certain data is needed. At the same time, the entries in the DB
	 * make sure that DB-based functions work as with all other properties.
	 */
	protected function setupPredefinedProperties( $verbose, DatabaseBase $db ) {
		global $wgDBtype;

		$this->checkPredefinedPropertyBorder( $verbose, $db );

		// now write actual properties; do that each time, it is cheap enough and we can update sortkeys by current language
		$this->reportProgress( "   ... writing entries for internal properties ...\n", $verbose );

		foreach ( SMWSql3SmwIds::$special_ids as $prop => $id ) {
			$p = new SMW\DIProperty( $prop );
			$db->replace( SMWSQLStore3::ID_TABLE, array( 'smw_id' ), array(
					'smw_id' => $id,
					'smw_title' => $p->getKey(),
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_iw' => $this->store->smwIds->getPropertyInterwiki( $p ),
					'smw_subobject' => '',
					'smw_sortkey' => $p->getLabel()
				), 'SMW::setup'
			);
		}

		$this->reportProgress( "   ... done.\n", $verbose );

		if ( $wgDBtype == 'postgres' ) {
			$sequenceIndex = SMWSQLStore3::ID_TABLE . '_smw_id_seq';

			$this->reportProgress( " ... updating {$sequenceIndex} sequence accordingly.\n", $verbose );

			$max = $db->selectField( SMWSQLStore3::ID_TABLE, 'max(smw_id)', array(), __METHOD__ );
			$max += 1;

			$db->query( "ALTER SEQUENCE {$sequenceIndex} RESTART WITH {$max}", __METHOD__ );
		}

		$this->reportProgress( "Internal properties initialized successfully.\n", $verbose );
	}

	public function drop( $verbose = true ) {

		$this->reportProgress( "Deleting all database content and tables generated by SMW ...\n\n", $verbose );

		$tableBuilder = $this->factory->newtableBuilder();

		if ( $verbose ) {
			$messageReporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
			$messageReporter->registerReporterCallback( array( $this , 'reportProgress' ) );

			$tableBuilder->setMessageReporter( $messageReporter );
		}

		$tables = array(
			SMWSQLStore3::ID_TABLE,
			SMWSQLStore3::CONCEPT_CACHE_TABLE,
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE,
			SMWSQLStore3::QUERY_LINKS_TABLE,
			SMWSQLStore3::FT_SEARCH_TABLE
		);

		foreach ( $this->store->getPropertyTables() as $proptable ) {
			$tables[] = $proptable->getName();
		}

		foreach ( $tables as $tableName ) {
			$tableBuilder->dropTable( $tableName );
		}

		$this->reportProgress( "All data removed successfully.\n", $verbose );

		\Hooks::run( 'SMW::SQLStore::AfterDropTablesComplete', array( $this->store, $tableBuilder ) );

		return true;
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

	/**
	 * @see MessageReporter::reportMessage
	 *
	 * @since 1.9
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->reportProgress( $message );
	}

	private function checkPredefinedPropertyBorder( $verbose, DatabaseBase $db ) {

		$this->reportProgress( "Setting up internal property indices ...\n", $verbose );

		// Check if we already have this structure
		$expectedID = SMWSQLStore3::FIXED_PROPERTY_ID_UPPERBOUND;

		$currentID = $db->selectRow(
			SMWSQLStore3::ID_TABLE,
			'smw_id',
			'smw_iw=' . $db->addQuotes( SMW_SQL3_SMWBORDERIW )
		);

		if ( $currentID !== false && $currentID->smw_id == $expectedID ) {
			return $this->reportProgress( "   ... space for internal properties already allocated.\n", $verbose );
		}

		// Legacy bound
		$currentID = $currentID === false ? 50 : $currentID->smw_id;

		$this->reportProgress( "   ... allocating space for internal properties ...\n", $verbose );
		$this->store->smwIds->moveSMWPageID( $expectedID );

		$db->insert(
			SMWSQLStore3::ID_TABLE,
			array(
				'smw_id' => $expectedID,
				'smw_title' => '',
				'smw_namespace' => 0,
				'smw_iw' => SMW_SQL3_SMWBORDERIW,
				'smw_subobject' => '',
				'smw_sortkey' => ''
			),
			__METHOD__
		);

		$this->reportProgress( "   ... moving from $currentID to $expectedID ", $verbose );

		// make way for built-in ids
		for ( $i = $currentID; $i < $expectedID; $i++ ) {
			$this->store->smwIds->moveSMWPageID( $i );
			$this->reportProgress( '.', $verbose );
		}

		$this->reportProgress( "\n   ... done.\n", $verbose );
	}

}
