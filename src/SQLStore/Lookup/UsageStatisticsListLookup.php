<?php

namespace SMW\SQLStore\Lookup;

use RuntimeException;
use SMW\DIProperty;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class UsageStatisticsListLookup implements ListLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PropertyStatisticsStore
	 */
	private $propertyStatisticsStore;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param PropertyStatisticsStore $propertyStatisticsStore
	 */
	public function __construct( Store $store, PropertyStatisticsStore $propertyStatisticsStore ) {
		$this->store = $store;
		$this->propertyStatisticsStore = $propertyStatisticsStore;
	}

	/**
	 * Returns a list with statistical information where keys are matched to:
	 *
	 * - 'PROPUSES': Number of property instances (value assignments) in the connection
	 * - 'USEDPROPS': Number of properties that are used with at least one value
	 * - 'DECLPROPS': Number of properties that have been declared (i.e. assigned a type)
	 * - 'OWNPAGE': Number of properties with their own page
	 * - 'QUERY': Number of inline queries
	 * - 'QUERYSIZE': Represents collective query size
	 * - 'CONCEPTS': Number of declared concepts
	 * - 'SUBOBJECTS': Number of declared subobjects
	 * - 'QUERYFORMATS': Array of used formats and its usage count
	 * - 'TOTALPROPS': Total number of registered properties
	 * - 'ERRORUSES': Number of annotations with an error
	 * - 'DELETECOUNT': Number of "marked for deletion"
	 *
	 * @since 2.2
	 *
	 * @return array
	 */
	public function fetchList() {
		return [
			'OWNPAGE' => $this->getPropertyPageCount(),
			'QUERY' => $this->getQueryCount(),
			'QUERYSIZE' => $this->getQuerySize(),
			'QUERYFORMATS' => $this->getQueryFormatsCount(),
			'CONCEPTS' => $this->getConceptCount(),
			'SUBOBJECTS' => $this->getSubobjectCount(),
			'DECLPROPS' => $this->getDeclaredPropertiesCount(),
			'PROPUSES' => $this->getPropertyUsageCount(),
			'USEDPROPS' => $this->getUsedPropertiesCount(),
			'TOTALPROPS' => $this->getTotalPropertiesCount(),
			'ERRORUSES' => $this->getImproperValueForCount(),
			'DELETECOUNT' => $this->getDeleteCount(),
			'TOTALENTITIES' => $this->getTotalEntitiesCount()
		];
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function isFromCache() {
		return false;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getTimestamp() {
		return wfTimestamp( TS_UNIX );
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getHash() {
		return 'statistics-lookup';
	}

	/**
	 * @since 2.2
	 *
	 * @return number
	 */
	public function getImproperValueForCount() {
		return $this->propertyStatisticsStore->getUsageCount(
			$this->store->getObjectIds()->getSMWPropertyID( new DIProperty( '_ERRP' ) )
		);
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getQueryCount() {
		return $this->count( '_ASK' );
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getQuerySize() {
		return $this->count( '_ASKSI' );
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getConceptCount() {
		return $this->count( '_CONC' );
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getSubobjectCount() {
		return $this->count( DIProperty::TYPE_SUBOBJECT );
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getDeclaredPropertiesCount() {
		return $this->count( DIProperty::TYPE_HAS_TYPE );
	}

	/**
	 * @since 1.9
	 *
	 * @return int[]
	 */
	public function getQueryFormatsCount() {
		$count = [];

		$res = $this->store->getConnection()->select(
			$this->findPropertyTableByType( '_ASKFO' )->getName(),
			'o_hash, COUNT(s_id) AS count',
			[],
			__METHOD__,
			[
				'ORDER BY' => 'count DESC',
				'GROUP BY' => 'o_hash'
			]
		);

		foreach ( $res as $row ) {
			$count[$row->o_hash] = (int)$row->count;
		}

		return $count;
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getPropertyPageCount() {

		$options = [];

		// Only match entities that have a NOT null smw_proptable_hash entry
		// which indicates that it is not a object but a subject value (has
		// annotations such as `has type` == page was created with ... etc.)
		$conditions = [
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => '',
			'smw_subobject'  => '',
			'smw_proptable_hash IS NOT NULL'
		];

		$db = $this->store->getConnection( 'mw.db' );

		// Select object ID's against known property ID's that match the conditions
		$res = $db->select(
			[ $db->tableName( SQLStore::ID_TABLE ), $db->tableName( SQLStore::PROPERTY_STATISTICS_TABLE ) ],
			'smw_id',
			$conditions,
			__METHOD__,
			$options,
			[ $db->tableName( SQLStore::ID_TABLE ) => [ 'INNER JOIN', [ 'smw_id=p_id' ] ] ]
		);

		return $res->numRows();
	}

	/**
	 * Count property uses by summing up property statistics table
	 *
	 * @note subproperties that are part of container values are counted
	 * individually and it does not seem to be important to filter them by
	 * adding more conditions.
	 *
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getPropertyUsageCount() {
		$count = 0;

		$row = $this->store->getConnection()->selectRow(
			[ SQLStore::PROPERTY_STATISTICS_TABLE ],
			'SUM( usage_count ) AS count',
			[],
			__METHOD__
		);

		$count = $row ? $row->count : $count;

		return (int)$count;
	}

	/**
	 * @since 2.5
	 *
	 * @return number
	 */
	public function getTotalPropertiesCount() {

		$count = 0;

		$conditions = [
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => '',
			'smw_subobject'  => ''
		];

		$row = $this->store->getConnection()->selectRow(
			SQLStore::ID_TABLE,
			'Count( * ) AS count',
			$conditions,
			__METHOD__
		);

		$count = $row ? $row->count : $count;

		return (int)$count;
	}

	/**
	 * @since 3.1
	 *
	 * @return number
	 */
	public function getTotalEntitiesCount() {

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->selectRow(
			SQLStore::ID_TABLE,
			'Count( * ) AS count',
			[],
			__METHOD__
		);

		return isset( $row->count ) ? (int)$row->count : 0;
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getUsedPropertiesCount() {

		$options = [];

		$conditions = [
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => '',
			'smw_subobject'  => '',
			'usage_count > 0'
		];

		$db = $this->store->getConnection( 'mw.db' );

		// Select object ID's against known property ID's that match the conditions
		$res = $db->select(
			[ $db->tableName( SQLStore::ID_TABLE ), $db->tableName( SQLStore::PROPERTY_STATISTICS_TABLE ) ],
			'smw_id',
			$conditions,
			__METHOD__,
			$options,
			[
				$db->tableName( SQLStore::ID_TABLE ) => [ 'INNER JOIN', [ 'smw_id=p_id' ] ]
			]
		);

		return $res->numRows();
	}

	/**
	 * @since 2.4
	 *
	 * @return number
	 */
	public function getDeleteCount() {
		$count = 0;

		$row = $this->store->getConnection()->selectRow(
			SQLStore::ID_TABLE,
			'Count( * ) AS count',
			[ 'smw_iw' => ':smw-delete' ],
			__METHOD__
		);

		$count = $row ? $row->count : $count;

		return (int)$count;
	}

	private function count( $type ) {

		$res = $this->store->getConnection()->select(
			$this->findPropertyTableByType( $type )->getName(),
			'COUNT(s_id) AS count',
			[],
			__METHOD__
		);

		$row = $this->store->getConnection()->fetchObject( $res );

		return isset( $row->count ) ? (int)$row->count : 0;
	}

	private function findPropertyTableByType( $type ) {
		$propertyTables = $this->store->getPropertyTables();

		$tableIdForType = $this->store->findPropertyTableID( new DIProperty( $type ) );

		if ( isset( $propertyTables[$tableIdForType] ) ) {
			return $propertyTables[$tableIdForType];
		}

		throw new RuntimeException( "Tried to access a table that doesn't exist for {$tableIdForType}." );
	}

}
