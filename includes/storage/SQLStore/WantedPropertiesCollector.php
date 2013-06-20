<?php

namespace SMW\SQLStore;

use SMW\Store\Collector;
use SMW\CacheHandler;
use SMW\DIProperty;
use SMW\Profiler;
use SMW\Settings;
use SMW\Store;

use DatabaseBase;

/**
 * Collects wanted properties from a store entity
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
 *
 * @license GNU GPL v2+
 * @author mwjames
 * @author Nischay Nahata
 */

/**
 * Collects wanted properties from a store entity
 *
 * @ingroup Collector
 * @ingroup SQLStore
 */
class WantedPropertiesCollector extends Collector {

	/** @var DatabaseBase */
	protected $dbConnection;

	/** @var SMWRequestOptions */
	protected $requestOptions = null;

	/** @var array */
	protected $results = array();

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
	 * Factory method for immediate instantiation of a WantedPropertiesCollector object
	 *
	 * @par Example:
	 * @code
	 *  $properties = \SMW\SQLStore\WantedPropertiesCollector::newFromStore( $store )->getResults();
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param $dbw Boolean or DatabaseBase:
	 * - Boolean: whether to use a dedicated DB or Slave
	 * - DatabaseBase: database connection to use
	 *
	 * @return Collector
	 */
	public static function newFromStore( Store $store, $dbw = false ) {

		$dbw = $dbw instanceof DatabaseBase ? $dbw : wfGetDB( DB_SLAVE );
		$settings = Settings::newFromGlobals();
		return new self( $store, $dbw, $settings );
	}

	/**
	 * Collects and returns wanted properties
	 *
	 * @par Example:
	 * @code
	 *  $wantedProperties = \SMW\SQLStore\WantedPropertiesCollector::newFromStore( $store );
	 *
	 *  $results = $wantedProperties->setRequestOptions( null )->getResults()
	 *  $count = $wantedProperties->count()
	 * @endcode
	 *
	 * @note This function is very resource intensive and needs to be cached on
	 * medium/large wikis.
	 *
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	public function getResults() {

		$type = $this->settings->get( 'smwgPDefaultType' );

		$cache = CacheHandler::newFromId( $this->settings->get( 'smwgCacheType' ) );
		$results = $cache->setCacheEnabled( $this->settings->get( 'smwgWantedPropertiesCache' ) )
			->key( 'collector', md5( 'wanted' . $type . serialize( $this->requestOptions ) ) )
			->get();

		if ( $results ) {

			$this->isCached = true;
			$this->results = unserialize( $results );
			wfDebug( __METHOD__ . ' wanted properties served from cache' . "\n");

		} else {

			// Wanted Properties must have the default type
			$this->propertyTables = $this->getPropertyTables( $type );

			// anything else would be crazy, but let's fail gracefully even if the whole world is crazy
			if ( !$this->propertyTables->isFixedPropertyTable() ) {
				$this->results = $this->getWantedProperties();
			}

			$this->isCached = false;
			$cache->set( serialize( $this->results ), $this->settings->get( 'smwgWantedPropertiesCacheExpiry' ) );
		}

		return $this->results;
	}

	/**
	 * Whether return results are cached
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isCached() {
		return $this->isCached;
	}

	/**
	 * Returns number of available results
	 *
	 * @since 1.9
	 *
	 * @return integer
	 */
	public function count() {
		return count( $this->results );
	}

	/**
	 * Set options
	 *
	 * @since 1.9
	 *
	 * @param SMWRequestOptions $requestOptions
	 *
	 * @return WantedPropertiesCollector
	 */
	public function setRequestOptions( $requestOptions ) {
		$this->requestOptions = $requestOptions;
		return $this;
	}

	/**
	 * Returns wanted properties
	 *
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	protected function getWantedProperties() {
		Profiler::In( __METHOD__ );

		$result = array();
		$storeIdTableName = $this->store->smwIds->getTableName();

		$options = $this->store->getSQLOptions( $this->requestOptions, 'title' );
		$options['ORDER BY'] = 'count DESC';

		// TODO: this is not how JOINS should be specified in the select function
		$res = $this->dbConnection->select(
			$this->dbConnection->tableName( $this->propertyTables->getName() ) . ' INNER JOIN ' .
				$this->dbConnection->tableName( $storeIdTableName ) . ' ON p_id=smw_id LEFT JOIN ' .
				$this->dbConnection->tableName( 'page' ) . ' ON (page_namespace=' .
				$this->dbConnection->addQuotes( SMW_NS_PROPERTY ) . ' AND page_title=smw_title)',
			'smw_title, COUNT(*) as count',
			'smw_id > 50 AND page_id IS NULL GROUP BY smw_title',
			__METHOD__,
			$options
		);

		foreach ( $res as $row ) {
			if ( $row->smw_title{0} !== '_' ) {
				$result[] = array( new DIProperty( $row->smw_title ), $row->count );
			}
		}

		Profiler::Out( __METHOD__ );
		return $result;
	}
}
