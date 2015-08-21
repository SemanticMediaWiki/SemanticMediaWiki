<?php

namespace SMW\SQLStore\Lookup;

use SMW\Store;
use SMW\Store\PropertyStatisticsStore;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\DIProperty;
use RuntimeException;

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
	 * Returns alist of statistical information as an associative array
	 * with the following keys:
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
	 *
	 * @since 2.2
	 *
	 * @return array
	 */
	public function fetchList() {
		return array(
			'OWNPAGE' => $this->getPropertyPageCount(),
			'QUERY' => $this->getQueryCount(),
			'QUERYSIZE' => $this->getQuerySize(),
			'QUERYFORMATS' => $this->getQueryFormatsCount(),
			'CONCEPTS' => $this->getConceptCount(),
			'SUBOBJECTS' => $this->getSubobjectCount(),
			'DECLPROPS' => $this->getDeclaredPropertiesCount(),
			'PROPUSES' => $this->getPropertyUsageCount(),
			'USEDPROPS' => $this->getUsedPropertiesCount(),
			'ERRORUSES' => $this->getImproperValueForCount()
		);
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function isCached() {
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
	public function getLookupIdentifier() {
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
		$count = array();

		$res = $this->store->getConnection()->select(
			$this->findPropertyTableByType( '_ASKFO' )->getName(),
			'o_hash, COUNT(s_id) AS count',
			array(),
			__METHOD__,
			array(
				'ORDER BY' => 'count DESC',
				'GROUP BY' => 'o_hash'
			)
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

		$count = $this->store->getConnection()->estimateRowCount(
			'page',
			'*',
			array( 'page_namespace' => SMW_NS_PROPERTY )
		);

		return (int)$count;
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
			array( $this->store->getStatisticsTable() ),
			'SUM( usage_count ) AS count',
			array(),
			__METHOD__
		);

		$count = $row ? $row->count : $count;

		return (int)$count;
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getUsedPropertiesCount() {
		$count = 0;

		$row = $this->store->getConnection()->selectRow(
			array( $this->store->getStatisticsTable() ),
			'Count( * ) AS count',
			array( 'usage_count > 0' ),
			__METHOD__
		);

		$count = $row ? $row->count : $count;

		return (int)$count;
	}

	private function count( $type ) {

		$res = $this->store->getConnection()->select(
			$this->findPropertyTableByType( $type )->getName(),
			'COUNT(s_id) AS count',
			array(),
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
