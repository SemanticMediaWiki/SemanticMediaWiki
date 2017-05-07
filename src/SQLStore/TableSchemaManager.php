<?php

namespace SMW\SQLStore;

use SMW\SQLStore\TableBuilder\Table;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDataItem as DataItem;

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
	private $tables = array();

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

		$hash = array();

		foreach ( $this->getTables() as $table ) {
			$hash[$table->getName()] = $table->getHash();
		}

		// Avoid by-chance sorting with an eventual differing hash
		sort( $hash );

		return md5( json_encode( $hash ) );
	}

	/**
	 * @since 2.5
	 *
	 * @return Table[]
	 */
	public function getTables() {

		if ( $this->tables !== array() ) {
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

		// ID_TABLE
		$table = new Table( SQLStore::ID_TABLE );

		$table->addColumn( 'smw_id', FieldType::FIELD_ID_PRIMARY );
		$table->addColumn( 'smw_namespace', array( FieldType::FIELD_NAMESPACE, 'NOT NULL' ) );
		$table->addColumn( 'smw_title', array( FieldType::FIELD_TITLE, 'NOT NULL' ) );
		$table->addColumn( 'smw_iw', array( FieldType::FIELD_INTERWIKI, 'NOT NULL' ) );
		$table->addColumn( 'smw_subobject', array( FieldType::FIELD_TITLE, 'NOT NULL' ) );
		$table->addColumn( 'smw_sortkey', array( FieldType::FIELD_TITLE, 'NOT NULL' ) );
		$table->addColumn( 'smw_sort', array( FieldType::FIELD_TITLE ) );
		$table->addColumn( 'smw_proptable_hash', FieldType::TYPE_BLOB );

		$table->addIndex( 'smw_id' );
		$table->addIndex( 'smw_id,smw_sortkey' );
		// IW match lookup
		$table->addIndex( 'smw_iw' );
		// ID lookup
		$table->addIndex( 'smw_title,smw_namespace,smw_iw,smw_subobject' );
		// Select by sortkey (range queries)
		$table->addIndex( 'smw_sortkey' );

		// Sort related indices
		$table->addIndex( 'smw_sort' );
		$table->addIndex( 'smw_id,smw_sort' );
		$table->addIndex( 'smw_sort,smw_id' );

		return $table;
	}

	private function newConceptCacheTable() {

		// CONCEPT_CACHE_TABLE (member elements (s)->concepts (o) )
		$table = new Table( SQLStore::CONCEPT_CACHE_TABLE );

		$table->addColumn( 's_id', array( FieldType::FIELD_ID, 'NOT NULL' ) );
		$table->addColumn( 'o_id', array( FieldType::FIELD_ID, 'NOT NULL' ) );

		$table->addIndex( 'o_id' );

		return $table;
	}

	private function newQueryLinksTable() {

		// QUERY_LINKS_TABLE
		$table = new Table( SQLStore::QUERY_LINKS_TABLE );

		$table->addColumn( 's_id', array( FieldType::FIELD_ID, 'NOT NULL' ) );
		$table->addColumn( 'o_id', array( FieldType::FIELD_ID, 'NOT NULL' ) );

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

		$table->addColumn( 's_id', array( FieldType::FIELD_ID, 'NOT NULL' ) );
		$table->addColumn( 'p_id', array( FieldType::FIELD_ID, 'NOT NULL' ) );
		$table->addColumn( 'o_text', FieldType::TYPE_TEXT );
		$table->addColumn( 'o_sort', FieldType::FIELD_TITLE );

		$table->addIndex( 's_id' );
		$table->addIndex( 'p_id' );
		$table->addIndex( 'o_sort' );
		$table->addIndex( array( 'o_text', 'FULLTEXT' ) );

		$table->addOption(
			'fulltextSearchTableOptions',
			$GLOBALS['smwgFulltextSearchTableOptions']
		);

		return $table;
	}

	private function newPropertyStatisticsTable() {

		// PROPERTY_STATISTICS_TABLE
		$table = new Table( SQLStore::PROPERTY_STATISTICS_TABLE );

		$table->addColumn( 'p_id', FieldType::FIELD_ID );
		$table->addColumn( 'usage_count', FieldType::FIELD_USAGE_COUNT );

		$table->addIndex( array( 'p_id', 'UNIQUE INDEX' ) );
		$table->addIndex( 'usage_count' );

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
		$indexes = array();
		if ( $propertyTable->usesIdSubject() ) {
			$fieldarray = array(
				's_id' => array( FieldType::FIELD_ID, 'NOT NULL' )
			);

			$indexes['sp'] = 's_id';
		} else {
			$fieldarray = array(
				's_title' => array( FieldType::FIELD_TITLE, 'NOT NULL' ),
				's_namespace' => array( FieldType::FIELD_NAMESPACE, 'NOT NULL' )
			);

			$indexes['sp'] = 's_title,s_namespace';
		}

		$indexes['po'] = $diHandler->getIndexField();

		if ( !$propertyTable->isFixedPropertyTable() ) {
			$fieldarray['p_id'] = array( FieldType::FIELD_ID, 'NOT NULL' );
			$indexes['po'] = 'p_id,' . $indexes['po'];
			$indexes['sp'] = $indexes['sp'] . ',p_id';
		}

		// TODO Special handling; concepts should be handled differently
		// in the future. See comments in SMW_DIHandler_Concept.php.
		if ( $propertyTable->getDiType() === DataItem::TYPE_CONCEPT ) {
			unset( $indexes['po'] );
		}

		foreach ( $diHandler->getTableIndexes() as $value ) {

			if ( strpos( $value, 'p_id' ) !== false && $propertyTable->isFixedPropertyTable() ) {
				continue;
			}

			if ( strpos( $value, 'o_id' ) !== false && !$propertyTable->usesIdSubject() ) {
				continue;
			}

			$indexes = array_merge( $indexes, array( $value ) );
		}

		$indexes = array_unique( $indexes );

		foreach ( $diHandler->getTableFields() as $fieldname => $fieldType ) {
			$fieldarray[$fieldname] = $fieldType;
		}

		$table = new Table( $propertyTable->getName() );

		foreach ( $fieldarray as $fieldName => $fieldType ) {
			$table->addColumn( $fieldName, $fieldType );
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
