<?php

namespace SMW\SQLStore;

use Onoi\MessageReporter\NullMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterAware;
use SMW\SQLStore\TableBuilder\Table;
use SMWDataItem as DataItem;

/**
 * @private
 *
 * Database type agnostic schema installer
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableSchemaManager implements MessageReporterAware {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var TableBuilder
	 */
	private $tableBuilder;

	/**
	 * @var TableIntegrityChecker
	 */
	private $tableIntegrityChecker;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var Table[]
	 */
	private $tables = array();

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 * @param TableBuilder $tableBuilder
	 * @param TableIntegrityChecker $tableIntegrityChecker
	 */
	public function __construct( SQLStore $store, TableBuilder $tableBuilder, TableIntegrityChecker $tableIntegrityChecker ) {
		$this->store = $store;
		$this->tableBuilder = $tableBuilder;
		$this->tableIntegrityChecker = $tableIntegrityChecker;
		$this->messageReporter = new NullMessageReporter();
	}

	/**
	 * @see MessageReporterAware::setMessageReporter
	 *
	 * @since 2.5
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 2.5
	 */
	public function create() {

		$this->tableBuilder->setMessageReporter(
			$this->messageReporter
		);

		$this->tableIntegrityChecker->setMessageReporter(
			$this->messageReporter
		);

		foreach ( $this->getTables() as $table ) {
			$this->tableBuilder->create( $table );
		}

		$this->tableIntegrityChecker->checkOnPostCreation( $this->tableBuilder );
	}

	/**
	 * @since 2.5
	 */
	public function drop() {

		$this->tableBuilder->setMessageReporter(
			$this->messageReporter
		);

		foreach ( $this->getTables() as $table ) {
			$this->tableBuilder->drop( $table );
		}

		\Hooks::run( 'SMW::SQLStore::AfterDropTablesComplete', array( $this->store, $this->tableBuilder ) );
	}

	private function getTables() {

		$this->addTable( $this->newEntityIdTable() );
		$this->addTable( $this->newConceptCacheTable() );
		$this->addTable( $this->newQueryLinksTable() );
		$this->addTable( $this->newFulltextSearchTable() );
		$this->addTable( $this->newPropertyStatisticsTable() );

		// TODO Replace listed types with something like FieldType::BOOLEAN ...
		$dbTypes = array(
			'b' => $this->tableBuilder->getStandardFieldType( 'boolean' ),
			't' => $this->tableBuilder->getStandardFieldType( 'title' ),
			's' => $this->tableBuilder->getStandardFieldType( 'sort' ),
			'l' => $this->tableBuilder->getStandardFieldType( 'blob' ),
			'f' => $this->tableBuilder->getStandardFieldType( 'double' ),
			'i' => $this->tableBuilder->getStandardFieldType( 'integer' ),
			'j' => $this->tableBuilder->getStandardFieldType( 'integer unsigned' ),
			'u' => $this->tableBuilder->getStandardFieldType( 'usage count' ),
			'p' => $this->tableBuilder->getStandardFieldType( 'id' ),
			'n' => $this->tableBuilder->getStandardFieldType( 'namespace' ),
			'w' => $this->tableBuilder->getStandardFieldType( 'iw' )
		);

		foreach ( $this->store->getPropertyTables() as $propertyTable ) {

			// Only extensions that aren't setup correctly can force an exception
			// and to avoid a failure during setup, ensure that standard tables
			// are correctly initialized otherwise SMW can't recover
			try {
				$diHandler = $this->store->getDataItemHandlerForDIType( $propertyTable->getDiType() );
			} catch ( \Exception $e ) {
				continue;
			}

			$this->addTable( $this->newPropertyTable( $propertyTable, $diHandler, $dbTypes ) );
		}

		return $this->tables;
	}

	private function newEntityIdTable() {

		// ID_TABLE
		$table = new Table( SQLStore::ID_TABLE );

		$table->addColumn( 'smw_id', $this->tableBuilder->getStandardFieldType( 'id primary' ) );
		$table->addColumn( 'smw_namespace', $this->tableBuilder->getStandardFieldType( 'namespace' ) . ' NOT NULL' );
		$table->addColumn( 'smw_title', $this->tableBuilder->getStandardFieldType( 'title' ) . ' NOT NULL' );
		$table->addColumn( 'smw_iw', $this->tableBuilder->getStandardFieldType( 'iw' ) . ' NOT NULL' );
		$table->addColumn( 'smw_subobject', $this->tableBuilder->getStandardFieldType( 'title' ) . ' NOT NULL' );
		$table->addColumn( 'smw_sortkey', $this->tableBuilder->getStandardFieldType( 'title' ) . ' NOT NULL' );
		$table->addColumn( 'smw_proptable_hash', $this->tableBuilder->getStandardFieldType( 'blob' ) );

		$table->addIndex( 'smw_id' );
		$table->addIndex( 'smw_id,smw_sortkey' );
		$table->addIndex( 'smw_iw' ); // iw match lookup
		$table->addIndex( 'smw_title,smw_namespace,smw_iw,smw_subobject' ); // id lookup
		$table->addIndex( 'smw_sortkey' ); // select by sortkey (range queries)

		return $table;
	}

	private function newConceptCacheTable() {

		// CONCEPT_CACHE_TABLE (member elements (s)->concepts (o) )
		$table = new Table( SQLStore::CONCEPT_CACHE_TABLE );

		$table->addColumn( 's_id', $this->tableBuilder->getStandardFieldType( 'id' ) . ' NOT NULL' );
		$table->addColumn( 'o_id', $this->tableBuilder->getStandardFieldType( 'id' ) . ' NOT NULL' );

		$table->addIndex( 'o_id' );

		return $table;
	}

	private function newQueryLinksTable() {

		// QUERY_LINKS_TABLE
		$table = new Table( SQLStore::QUERY_LINKS_TABLE );

		$table->addColumn( 's_id', $this->tableBuilder->getStandardFieldType( 'id' ) . ' NOT NULL' );
		$table->addColumn( 'o_id', $this->tableBuilder->getStandardFieldType( 'id' ) . ' NOT NULL' );

		$table->addIndex( 's_id' );
		$table->addIndex( 'o_id' );
		$table->addIndex( 's_id,o_id' );

		return $table;
	}

	private function newFulltextSearchTable() {

		// FT_SEARCH_TABLE
		// TEXT and BLOB is stored off the table with the table just having a pointer
		// VARCHAR is stored inline with the table
		$table = new Table( SQLStore::FT_SEARCH_TABLE );

		$table->addColumn( 's_id', $this->tableBuilder->getStandardFieldType( 'id' ) . ' NOT NULL' );
		$table->addColumn( 'p_id', $this->tableBuilder->getStandardFieldType( 'id' ) . ' NOT NULL' );
		$table->addColumn( 'o_text', 'TEXT' );
		$table->addColumn( 'o_sort', $this->tableBuilder->getStandardFieldType( 'title' ) );

		$table->addIndex( 's_id' );
		$table->addIndex( 'p_id' );
		$table->addIndex( 'o_sort' );
		$table->addIndex( array( 'o_text', 'FULLTEXT' ) );

		$table->addOption(
			'ftSearchOptions',
			$GLOBALS['smwgFulltextSearchTableOptions']
		);

		return $table;
	}

	private function newPropertyStatisticsTable() {

		// PROPERTY_STATISTICS_TABLE
		$table = new Table( SQLStore::PROPERTY_STATISTICS_TABLE );

		$table->addColumn( 'p_id', $this->tableBuilder->getStandardFieldType( 'id' ) );
		$table->addColumn( 'usage_count', $this->tableBuilder->getStandardFieldType( 'usage count' ) );

		$table->addIndex( array( 'p_id', 'UNIQUE INDEX' ) );
		$table->addIndex( 'usage_count' );

		return $table;
	}

	private function newPropertyTable( $propertyTable, $diHandler, $dbTypes ) {
		$addedCustomTypeSignatures = false;

		// Prepare indexes. By default, property-value tables
		// have the following indexes:
		//
		// sp: getPropertyValues(), getSemanticData(), getProperties()
		// po: ask, getPropertySubjects()
		//
		// The "p" component is omitted for tables with fixed property.
		$indexes = array();
		if ( $propertyTable->usesIdSubject() ) {
			$fieldarray = array(
				's_id' => $this->tableBuilder->getStandardFieldType( 'id' ) . ' NOT NULL'
			);

			$indexes['sp'] = 's_id';
		} else {
			$fieldarray = array(
				's_title' => $this->tableBuilder->getStandardFieldType( 'title' ) . ' NOT NULL',
				's_namespace' => $this->tableBuilder->getStandardFieldType( 'namespace' ) . ' NOT NULL'
			);

			$indexes['sp'] = 's_title,s_namespace';
		}

		$indexes['po'] = $diHandler->getIndexField();

		if ( !$propertyTable->isFixedPropertyTable() ) {
			$fieldarray['p_id'] = $this->tableBuilder->getStandardFieldType( 'id' ) . ' NOT NULL';
			$indexes['po'] = 'p_id,' . $indexes['po'];
			$indexes['sp'] = $indexes['sp'] . ',p_id';
		}

		// TODO Special handling; concepts should be handled differently
		// in the future. See comments in SMW_DIHandler_Concept.php.
		if ( $propertyTable->getDiType() === DataItem::TYPE_CONCEPT ) {
			unset( $indexes['po'] );
		}

		$indexes = array_merge( $indexes, $diHandler->getTableIndexes() );
		$indexes = array_unique( $indexes );

		foreach ( $diHandler->getTableFields() as $fieldname => $typeid ) {
			// If the type signature is not recognized and the custom signatures have not been added, add them.
			if ( !$addedCustomTypeSignatures && !array_key_exists( $typeid, $dbTypes ) ) {

				// @Depreceated since 2.5
				\Hooks::run( 'SMWCustomSQLStoreFieldType', array( &$dbTypes ) );

				\Hooks::run( 'SMW::SQLStore::AddCustomDatabaseFieldType', array( &$dbTypes ) );
				$addedCustomTypeSignatures = true;
			}

			// Only add the type when the signature was recognized, otherwise ignore it silently.
			if ( array_key_exists( $typeid, $dbTypes ) ) {
				$fieldarray[$fieldname] = $dbTypes[$typeid];
			}
		}

		$table = new Table( $propertyTable->getName() );

		foreach ( $fieldarray as $key => $value ) {
			$table->addColumn( $key, $value );
		}

		foreach ( $indexes as $key => $index ) {
			$table->addIndexWithKey( $key, $index );
		}

		return $table;
	}

	private function addTable( Table $table ) {
		$this->tables[] = $table;
	}

}
