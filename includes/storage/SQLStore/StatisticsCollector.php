<?php

namespace SMW\SQLStore;

use SMW\CacheHandler;
use SMW\Settings;

use DatabaseBase;
use SMWDIProperty;
use SMWStore;

/**
 * Collects statistical information provided by the store
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * This class provides access to store related statistical information
 *
 * @ingroup SMW
 */
class StatisticsCollector {

	/** @var SMWStore */
	protected $store;

	/** @var DatabaseBase */
	protected $dbConnection;

	/** @var Settings */
	protected $settings;

	/** @var Boolean */
	protected $isCached;

	/**
	 * // FIXME The store itself should know which database connection is being
	 * used therefore this info should come from the store object rather than
	 * doing an extra injection here
	 *
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param DatabaseBase $dbw
	 * @param Settings $settings
	 */
	public function __construct( SMWStore $store, DatabaseBase $dbw, Settings $settings ) {
		$this->store = $store;
		$this->dbConnection = $dbw;
		$this->settings = $settings;
		$this->isCached = false;
	}

	/**
	 * Factory method for immediate instantiation of a StatisticsCollector object
	 *
	 * @par Example:
	 * @code
	 * $statistics = \SMW\SQLStore\StatisticsCollector::newFromStore( $store )->doCollect();
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param SMWStore $store
	 * @param $dbw Boolean or DatabaseBase:
	 * - Boolean: whether to use a dedicated DB or Slave
	 * - DatabaseBase: database connection to use
	 *
	 * @return StatisticsCollector
	 */
	public static function newFromStore( SMWStore $store, $dbw = false ) {

		$dbw = $dbw instanceof DatabaseBase ? $dbw : wfGetDB( DB_SLAVE );

		$settings = Settings::newFromArray( array(
			'smwgCacheType' => $GLOBALS['smwgCacheType'],
			'smwgStatisticsCache' => $GLOBALS['smwgStatisticsCache'],
			'smwgStatisticsCacheExpiry' => $GLOBALS['smwgStatisticsCacheExpiry']
		) );

		return new self( $store, $dbw, $settings );
	}

	/**
	 * Collects and returns statistical information as an associative array
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
	 * @note Results are cacheable if necessary settings are available
	 *
	 * @see $smwgStatisticsCache
	 * @see $smwgStatisticsCacheExpiry
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function doCollect() {

		$cache = CacheHandler::newFromId( $this->settings->get( 'smwgCacheType' ) );
		$cache->setCacheEnabled( $this->settings->get( 'smwgStatisticsCache' ) )->key( 'stats', 'store' );

		if ( $cache->get() ) {
			wfDebug( __METHOD__ . ' statistics served from cache' . "\n");
			$this->isCached = true;
			return $cache->get();
		}

		$statistics = array(
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

		$cache->set( $statistics, $this->settings->get( 'smwgStatisticsCacheExpiry' ) );

		return $statistics;
	}

	/**
	 * Returns if the current collection has been recalled from cache
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isCached() {
		return $this->isCached;
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
		return $this->count( SMWDIProperty::TYPE_SUBOBJECT );
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getDeclaredPropertiesCount() {
		return $this->count( SMWDIProperty::TYPE_HAS_TYPE );
	}

	/**
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getQueryFormatsCount() {
		wfProfileIn( __METHOD__ );

		$count = array();
		$res = $this->dbConnection->select(
			$this->getTypeTable( '_ASKFO' )->getName(),
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

		wfProfileOut( __METHOD__ );
		return $count;
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getPropertyPageCount() {
		wfProfileIn( __METHOD__ );

		$count = 0;
		$count = $this->dbConnection->estimateRowCount(
			'page',
			'*',
			array( 'page_namespace' => SMW_NS_PROPERTY )
		);

		wfProfileOut( __METHOD__ );
		return (int)$count;
	}

	/**
	 * Count property uses by counting rows in property tables
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
		wfProfileIn( __METHOD__ );

		$count = 0;
		foreach ( $this->store->getPropertyTables() as $propertyTable ) {
			$res = $this->dbConnection->select(
				$propertyTable->getName(),
				'COUNT(*) AS count',
				array(),
				__METHOD__
			);
			$row = $this->dbConnection->fetchObject( $res );
			$count += $row->count;
		}

		wfProfileOut( __METHOD__ );
		return (int)$count;
	}

	/**
	 * @since 1.9
	 *
	 * @return number
	 */
	public function getUsedPropertiesCount() {
		wfProfileIn( __METHOD__ );

		$count = 0;
		foreach ( $this->store->getPropertyTables() as $propertyTable ) {
			if ( !$propertyTable->isFixedPropertyTable() ) {
				$res = $this->dbConnection->select(
					$propertyTable->getName(),
					'COUNT(DISTINCT(p_id)) AS count',
					array(),
					__METHOD__
				);
				$row = $this->dbConnection->fetchObject( $res );
				$count += $row->count;
			} else {
				$res = $this->dbConnection->select(
					$propertyTable->getName(),
					'*',
					array(),
					__METHOD__,
					array( 'LIMIT' => 1 )
				);

				if ( $this->dbConnection->numRows( $res ) > 0 ) {
					$count++;
				}
			}
		}

		wfProfileOut( __METHOD__ );
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
		wfProfileIn( $caller );

		$count = 0;
		$res = $this->dbConnection->select(
			$this->getTypeTable( $type )->getName(),
			'COUNT(s_id) AS count',
			array(),
			__METHOD__
		);
		$row = $this->dbConnection->fetchObject( $res );

		wfProfileOut( $caller );
		return (int)$row->count;
	}

	/**
	 * Returns table declaration for a given property type
	 *
	 * @since 1.9
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	protected function getTypeTable( $type ) {
		$typeProp = new SMWDIProperty( $type );
		$propertyTables = $this->store->getPropertyTables();
		return $propertyTables[$this->store->findPropertyTableID( $typeProp )];
	}
}
