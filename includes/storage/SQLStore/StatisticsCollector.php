<?php

namespace SMW\SQLStore;

use SMW\Store\CacheableResultCollector;

use SMW\SimpleDictionary;
use SMW\DIProperty;
use SMW\Settings;
use SMW\Store;

use DatabaseBase;

/**
 * Collects statistical information provided by the store
 *
 * @ingroup SQLStore
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 * @author Nischay Nahata
 */
class StatisticsCollector extends CacheableResultCollector {

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/** @var DatabaseBase */
	protected $dbConnection;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param DatabaseBase $dbw
	 * @param Settings $settings
	 */
	public function __construct( Store $store, DatabaseBase $dbw, Settings $settings ) {
		$this->store = $store;
		$this->dbConnection = $dbw;
		$this->settings = $settings;
	}

	/**
	 * Collects statistical information as an associative array
	 * with the following keys:
	 *
	 * - 'PROPUSES': Number of property instances (value assignments) in the dbConnection
	 * - 'USEDPROPS': Number of properties that are used with at least one value
	 * - 'DECLPROPS': Number of properties that have been declared (i.e. assigned a type)
	 * - 'OWNPAGE': Number of properties with their own page
	 * - 'QUERY': Number of inline queries
	 * - 'QUERYSIZE': Represents collective query size
	 * - 'CONCEPTS': Number of declared concepts
	 * - 'SUBOBJECTS': Number of declared subobjects
	 * - 'QUERYFORMATS': Array of used formats and its usage count
	 *
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	public function runCollector() {

		return array(
			'OWNPAGE' => $this->getPropertyPageCount(),
			'QUERY' => $this->getQueryCount(),
			'QUERYSIZE' => $this->getQuerySize(),
			'QUERYFORMATS' => $this->getQueryFormatsCount(),
			'CONCEPTS' => $this->getConceptCount(),
			'SUBOBJECTS' => $this->getSubobjectCount(),
			'DECLPROPS' => $this->getDeclaredPropertiesCount(),
			'PROPUSES' => $this->getPropertyUsageCount(),
			'USEDPROPS' => $this->getUsedPropertiesCount()
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
	 * @return array
	 */
	public function getQueryFormatsCount() {

		$count = array();
		$res = $this->dbConnection->select(
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

		$count = 0;
		$count = $this->dbConnection->estimateRowCount(
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
		$row = $this->dbConnection->selectRow(
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
		$row = $this->dbConnection->selectRow(
			array( $this->store->getStatisticsTable() ),
			'Count( * ) AS count',
			array( 'usage_count > 0' ),
			__METHOD__
		);

		$count = $row ? $row->count : $count;

		return (int)$count;
	}

	/**
	 * Convenience method to count on a single table for a given type
	 *
	 * @since 1.9
	 *
	 * @param string $type
	 *
	 * @return number
	 */
	protected function count( $type ) {
		$caller = wfGetCaller();

		$count = 0;
		$res = $this->dbConnection->select(
			$this->findPropertyTableByType( $type )->getName(),
			'COUNT(s_id) AS count',
			array(),
			__METHOD__
		);
		$row = $this->dbConnection->fetchObject( $res );

		return (int)$row->count;
	}

	/**
	 * @see CacheableObjectCollector::cacheSetup
	 *
	 * @since 1.9
	 *
	 * @return ObjectDictionary
	 */
	protected function cacheSetup() {
		return new SimpleDictionary( array(
			'id'      => array( 'smwgStatisticsCache', $this->requestOptions ),
			'type'    => $this->settings->get( 'smwgCacheType' ),
			'enabled' => $this->settings->get( 'smwgStatisticsCache' ),
			'expiry'  => $this->settings->get( 'smwgStatisticsCacheExpiry' )
		) );
	}

}
