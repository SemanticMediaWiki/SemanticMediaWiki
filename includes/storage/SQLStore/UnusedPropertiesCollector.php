<?php

namespace SMW\SQLStore;

use SMW\Store\Collector;

use SMW\InvalidPropertyException;
use SMW\CacheHandler;
use SMW\DIProperty;
use SMW\Settings;
use SMW\Profiler;
use SMW\Store;

use SMWDIError;

use Message;
use DatabaseBase;

/**
 * Collects unused properties from a store entity
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
 * Collects unused properties from a store entity
 *
 * @ingroup SMW
 */
class UnusedPropertiesCollector extends Collector {

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
	 * Factory method for immediate instantiation of a UnusedPropertiesCollector object
	 *
	 * @par Example:
	 * @code
	 *  $properties = \SMW\SQLStore\UnusedPropertiesCollector::newFromStore( $store )->getResults();
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
	 * Collects and returns unused properties
	 *
	 * @see $smwgUnusedPropertiesCache
	 * @see $smwgUnusedPropertiesCacheExpiry
	 *
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	public function getResults() {

		$useCache = $this->settings->get( 'smwgUnusedPropertiesCache' );
		$results  = $this->getCache()->setCacheEnabled( $useCache )
			->key( 'collector', md5( 'unused-' . serialize( $this->requestOptions ) ) )
			->get();

		if ( $results ) {

			$this->isCached = true;
			$this->results  = isset( $results['data'] ) ? unserialize( $results['data'] ) : array();
			wfDebug( __METHOD__ . ' served from cache' . "\n" );

		} else {

			$this->isCached = false;
			$this->results  = $this->getUnusedProperties();
			$this->getCache()->setCacheEnabled( $useCache && $this->results !== array() )->set(
				array( 'time' => $this->getTimestamp(), 'data' => serialize( $this->results ) ),
				$this->settings->get( 'smwgUnusedPropertiesCacheExpiry' )
			);
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
	 * Set request options
	 *
	 * @since 1.9
	 *
	 * @param SMWRequestOptions $requestOptions
	 *
	 * @return UnusedPropertiesCollector
	 */
	public function setRequestOptions( $requestOptions ) {
		$this->requestOptions = $requestOptions;
		return $this;
	}

	/**
	 * Returns unused properties
	 *
	 * @since 1.9
	 *
	 * @return DIProperty[]
	 */
	protected function getUnusedProperties() {
		Profiler::In( __METHOD__ );

		$result = array();

		// the query needs to do the filtering of internal properties, else LIMIT is wrong
		$options = array( 'ORDER BY' => 'smw_sortkey' );

		if ( $this->requestOptions !== null ) {
			if ( $this->requestOptions->limit > 0 ) {
				$options['LIMIT'] = $this->requestOptions->limit;
				$options['OFFSET'] = max( $this->requestOptions->offset, 0 );
			}
		}

		$conditions = array(
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_iw' => ''
		);

		$conditions['usage_count'] = 0;

		$res = $this->dbConnection->select(
			array( $this->store->getObjectIds()->getIdTable(), $this->store->getStatisticsTable() ),
			array( 'smw_title', 'usage_count' ),
			$conditions,
			__METHOD__,
			$options,
			array( $this->store->getObjectIds()->getIdTable() => array( 'INNER JOIN', array( 'smw_id=p_id' ) ) )
		);

		foreach ( $res as $row ) {

			try {
				$property = new DIProperty( $row->smw_title );
			} catch ( InvalidPropertyException $e ) {
				$property = new SMWDIError( new Message( 'smw_noproperty', array( $row->smw_title ) ) );
			}

			$result[] = $property;
		}

		Profiler::Out( __METHOD__ );
		return $result;
	}
}
