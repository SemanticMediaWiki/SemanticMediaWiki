<?php

namespace SMW\SQLStore;

use SMW\SQLStore\TableBuilder\FieldType;
use SMW\SQLStore\TableBuilder\Table;
use SMWDataItem as DataItem;
use RuntimeException;
use Hooks;

/**
 * @private
 *
 * Database type agnostic table/schema definition manager
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableSchemaManager {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var Table[]
	 */
	private $tables = [];

	/**
	 * @var []
	 */
	private $auxiliaryIndices = [];

	/**
	 * @var []
	 */
	private $options = [];

	/**
	 * @var integer
	 */
	private $featureFlags = false;

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getHash() {

		$hash = [];

		foreach ( $this->getTables() as $table ) {
			$hash[$table->getName()] = $table->getHash();
		}

		// Avoid by-chance sorting with an eventual differing hash
		sort( $hash );

		return md5( json_encode( $hash ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		$this->options = $options;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = false ) {

		if ( isset( $this->options[$key] ) ) {
			return $this->options[$key];
		}

		return $default;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $featureFlags
	 */
	public function setFeatureFlags( $featureFlags ) {
		$this->featureFlags = $featureFlags;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $feature
	 *
	 * @return boolean
	 */
	public function hasFeatureFlag( $feature ) {
		return ( (int)$this->featureFlags & $feature ) != 0;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $tableName
	 *
	 * @return Table|null
	 */
	public function findTable( $tableName ) {

		foreach ( $this->getTables() as $table ) {
			if ( $table->getName() === $tableName ) {
				return $table;
			}
		}

		return null;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $auxiliaryIndices
	 */
	public function addAuxiliaryIndices( array $auxiliaryIndices ) {
		$this->auxiliaryIndices = $auxiliaryIndices;
	}

	/**
	 * @since 2.5
	 *
	 * @return Table[]
	 */
	public function getTables() {

		if ( $this->tables !== [] ) {
			return $this->tables;
		}

		$this->addTable( $this->newEntityIdTable() );
		$this->addTable( $this->newConceptCacheTable() );
		$this->addTable( $this->newQueryLinksTable() );
		$this->addTable( $this->newFulltextSearchTable() );

		$this->addTable( $this->newPropertyStatisticsTable() );

		foreach ( $this->store->getPropertyTables() as $propertyTable ) {

			// Only extensions that aren't setup correctly can force an exception
			// and to avoid a failure during setup, ensure that standard tables
			// are correctly initialized otherwise SMW can't recover
			try {
				$diHandler = $this->store->getDataItemHandlerForDIType( $propertyTable->getDiType() );
			} catch ( \Exception $e ) {
				continue;
			}

			$this->addTable( $this->newPropertyTable( $propertyTable, $diHandler ) );
		}

		return $this->tables;
	}

	private function newEntityIdTable() {

		$connection = $this->store->getConnection( DB_MASTER );

		// ID_TABLE
		$table = new Table( SQLStore::ID_TABLE );

		$table->addColumn( 'smw_id', FieldType::FIELD_ID_PRIMARY );
		$table->addColumn( 'smw_namespace', [ FieldType::FIELD_NAMESPACE, 'NOT NULL' ] );
		$table->addColumn( 'smw_title', [ FieldType::FIELD_TITLE, 'NOT NULL' ] );
		$table->addColumn( 'smw_iw', [ FieldType::FIELD_INTERWIKI, 'NOT NULL' ] );
		$table->addColumn( 'smw_subobject', [ FieldType::FIELD_TITLE, 'NOT NULL' ] );

		$table->addColumn( 'smw_sortkey', [
			$this->hasFeatureFlag( SMW_FIELDT_CHAR_NOCASE ) ? FieldType::TYPE_CHAR_NOCASE : FieldType::FIELD_TITLE,
			'NOT NULL'
		] );

		$table->addColumn( 'smw_sort', [ FieldType::FIELD_TITLE ] );
		$table->addColumn( 'smw_proptable_hash', FieldType::TYPE_BLOB );
		$table->addColumn( 'smw_hash', FieldType::FIELD_HASH );
		$table->addColumn( 'smw_rev', FieldType::FIELD_ID_UNSIGNED );

		$table->addColumn( 'smw_touched', FieldType::TYPE_TIMESTAMP );
		$table->addDefault( 'smw_touched', $connection->timestamp( '1970-01-01 00:00:00' ) );

		$table->addIndex( 'smw_id' );
		$table->addIndex( 'smw_id,smw_sortkey' );
		$table->addIndex( 'smw_hash,smw_id' );

		// IW match lookup
		$table->addIndex( 'smw_iw' );
		$table->addIndex( 'smw_iw,smw_id' );

		// ID lookup
		$table->addIndex( 'smw_title,smw_namespace,smw_iw,smw_subobject' );

		// InProperty lookup
		// $table->addIndex( 'smw_iw,smw_id,smw_title,smw_sortkey,smw_sort' );

		// Select by sortkey (range queries)
		$table->addIndex( 'smw_sortkey' );

		// Sort related indices, Store::getPropertySubjects (GROUP BY)
		// $table->addIndex( 'smw_sort' );
		$table->addIndex( 'smw_sort,smw_id' );

		// API smwbrowse primary lookup
		// SMW\MediaWiki\Api\Browse\ListLookup::fetchFromTable
		$table->addIndex( 'smw_namespace,smw_sortkey' );

		// Interfered with the API lookup index, couldn't find a use case
		// that would require the this index
		// $table->addIndex( 'smw_sort,smw_id,smw_iw' );

		$table->addIndex( 'smw_rev,smw_id' );
		$table->addIndex( 'smw_touched,smw_id' );

		return $table;
	}

	private function newConceptCacheTable() {

		// CONCEPT_CACHE_TABLE (member elements (s)->concepts (o) )
		$table = new Table( SQLStore::CONCEPT_CACHE_TABLE );

		$table->addColumn( 's_id', [ FieldType::FIELD_ID, 'NOT NULL' ] );
		$table->addColumn( 'o_id', [ FieldType::FIELD_ID, 'NOT NULL' ] );

		$table->addIndex( 'o_id' );

		return $table;
	}

	private function newQueryLinksTable() {

		// QUERY_LINKS_TABLE
		$table = new Table( SQLStore::QUERY_LINKS_TABLE );

		$table->addColumn( 's_id', [ FieldType::FIELD_ID, 'NOT NULL' ] );
		$table->addColumn( 'o_id', [ FieldType::FIELD_ID, 'NOT NULL' ] );

		$table->addIndex( 's_id' );
		$table->addIndex( 'o_id' );
		$table->addIndex( 's_id,o_id' );

		return $table;
	}

	private function newFulltextSearchTable() {

		// Avoid the creation unless it is enabled hereby avoids issues in
		// regards to the default `MyISAM` storage engine (especially when mixed with
		// InnoDB, transactional mode).Those who enable the full-text need to
		// ensure that `smwgFulltextSearchTableOptions` matches their environment.
		if ( !$this->getOption( 'smwgEnabledFulltextSearch', false ) ) {
			return null;
		}

		// FT_SEARCH_TABLE
		// TEXT and BLOB is stored off the table with the table just having a pointer
		// VARCHAR is stored inline with the table
		$table = new Table( SQLStore::FT_SEARCH_TABLE );

		$table->addColumn( 's_id', [ FieldType::FIELD_ID, 'NOT NULL' ] );
		$table->addColumn( 'p_id', [ FieldType::FIELD_ID, 'NOT NULL' ] );
		$table->addColumn( 'o_text', FieldType::TYPE_TEXT );
		$table->addColumn( 'o_sort', FieldType::FIELD_TITLE );

		$table->addIndex( 's_id' );
		$table->addIndex( 'p_id' );
		$table->addIndex( 'o_sort' );
		$table->addIndex( [ 'o_text', 'FULLTEXT' ] );

		$table->addOption(
			'fulltextSearchTableOptions',
			 $this->getOption( 'smwgFulltextSearchTableOptions', [] )
		);

		return $table;
	}

	private function newPropertyStatisticsTable() {

		// PROPERTY_STATISTICS_TABLE
		$table = new Table( SQLStore::PROPERTY_STATISTICS_TABLE );

		$table->addColumn( 'p_id', FieldType::FIELD_ID );
		$table->addColumn( 'usage_count', FieldType::FIELD_USAGE_COUNT );
		$table->addColumn( 'null_count', FieldType::FIELD_USAGE_COUNT );

		$table->addDefault( 'usage_count', 0 );
		$table->addDefault( 'null_count', 0 );

		$table->addIndex( [ 'p_id', 'UNIQUE INDEX' ] );
		$table->addIndex( 'usage_count' );
		$table->addIndex( 'null_count' );

		return $table;
	}

	private function newPropertyTable( $propertyTable, $diHandler ) {

		// Prepare indexes. By default, property-value tables
		// have the following indexes:
		//
		// sp: getPropertyValues(), getSemanticData(), getProperties()
		// po: ask, getPropertySubjects()
		//
		// The "p" component is omitted for tables with fixed property.
		$indexes = [];
		if ( $propertyTable->usesIdSubject() ) {
			$fieldarray = [
				's_id' => [ FieldType::FIELD_ID, 'NOT NULL' ]
			];

			$indexes['sp'] = 's_id';
		} else {
			$fieldarray = [
				's_title' => [ FieldType::FIELD_TITLE, 'NOT NULL' ],
				's_namespace' => [ FieldType::FIELD_NAMESPACE, 'NOT NULL' ]
			];

			$indexes['sp'] = 's_title,s_namespace';
		}

		$indexes['po'] = $diHandler->getIndexField();

		if ( !$propertyTable->isFixedPropertyTable() ) {
			$fieldarray['p_id'] = [ FieldType::FIELD_ID, 'NOT NULL' ];
			$indexes['sp'] = $indexes['sp'] . ',p_id';
		}

		// TODO Special handling; concepts should be handled differently
		// in the future. See comments in SMW_DIHandler_Concept.php.
		if ( $propertyTable->getDiType() === DataItem::TYPE_CONCEPT ) {
			unset( $indexes['po'] );
		}

		foreach ( $diHandler->getTableIndexes() as $value ) {

			// For an array, the first defines the column, the second the type
			// (e.g. `[ 'p_id,s_id,o_hash', 'PRIMARY KEY' ]`)
			$val = is_array( $value ) ? $value[0] : $value;

			// Unique?
			if ( array_search( $val, $indexes ) !== false ) {
				continue;
			}

			// Test that a selected index can actually be created given it field
			// constraints
			if ( strpos( $val, 'p_id' ) !== false && $propertyTable->isFixedPropertyTable() ) {
				continue;
			}

			if ( strpos( $val, 'o_id' ) !== false && !$propertyTable->usesIdSubject() ) {
				continue;
			}

			if ( strpos( $val, 's_id' ) !== false && !$propertyTable->usesIdSubject() ) {
				continue;
			}

			$indexes = array_merge( $indexes, [ $value ] );
		}

		foreach ( $diHandler->getTableFields() as $fieldname => $fieldType ) {
			$fieldarray[$fieldname] = $fieldType;
		}

		$table = new Table( $propertyTable->getName() );

		foreach ( $fieldarray as $fieldName => $fieldType ) {
			$table->addColumn( $fieldName, $fieldType );
		}

		foreach ( $indexes as $key => $index ) {
			$table->addIndex( $index, $key );
		}

		return $table;
	}

	private function addTable( Table $table = null ) {

		if ( $table === null ) {
			return;
		}

		$name = $table->getName();

		if ( isset( $this->auxiliaryIndices[$name] ) ) {
			$table->addIndex( $this->auxiliaryIndices[$name] );
		}

		$this->tables[] = $table;
	}

}
