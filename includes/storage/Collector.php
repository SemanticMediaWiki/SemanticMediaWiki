<?php

namespace SMW\Store;

use SMW\ResultCacheMapper;
use SMW\DIProperty;
use SMW\Settings;

use SMWRequestOptions;
use InvalidArgumentException;
use MWTimestamp;

/**
 * Interface for items of groups of individuals to be sampled into a
 * collection of values
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
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
interface Collectible {}

/**
 * Collector base class
 *
 * @ingroup Collector
 * @ingroup Store
 */
abstract class Collector implements Collectible {

	/** @var array */
	protected $results = array();

	/** @var SMWRequestOptions */
	protected $requestOptions = null;

	/** @var boolean */
	protected $isCached = false;

	/** @var string */
	protected $cacheDate = null;

	/**
	 * Collects and returns information in an associative array
	 *
	 * @since 1.9
	 */
	public function getResults() {

		$resultCache = new ResultCacheMapper( $this->cacheAccessor() );

		$results = $resultCache->fetchFromCache();

		if ( $results ) {

			$this->isCached  = true;
			$this->results   = $results;
			$this->cacheDate = $resultCache->getCacheDate();
			wfDebug( get_called_class() . ' served from cache' . "\n" );

		} else {

			$this->results  = $this->doCollect();
			$this->isCached = false;
			$resultCache->recache( $this->results );
		}

		return $this->results;
	}

	/**
	 * Set request options
	 *
	 * @since 1.9
	 *
	 * @param SMWRequestOptions $requestOptions
	 *
	 * @return Collector
	 */
	public function setRequestOptions( SMWRequestOptions $requestOptions ) {
		$this->requestOptions = $requestOptions;
		return $this;
	}

	/**
	 * Returns whether or not results have been cached
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isCached() {
		return $this->isCached;
	}

	/**
	 * In case results were cached, it returns the timestamp of the cached
	 * object
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	public function getCacheDate() {
		return $this->cacheDate;
	}

	/**
	 * Returns number of available results
	 *
	 * @since 1.9
	 *
	 * @return integer
	 */
	public function getCount() {
		return count( $this->results );
	}

	/**
	 * Sub-class is responsible for returning an associative array
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	protected abstract function doCollect();

	/**
	 * Sub-class is returning an ArrayAccessor object necessary for
	 * the ResultCacheMapper instantiation
	 *
	 * @par Example:
	 * @code
	 *  return new ArrayAccessor( array(
	 *   'id'      => 'smwgPropertiesCache' . <...>,
	 *   'type'    => $this->settings->get( 'smwgCacheType' ),
	 *   'enabled' => $this->settings->get( 'smwgPropertiesCache' ),
	 *   'expiry'  => $this->settings->get( 'smwgPropertiesCacheExpiry' )
	 *  ) );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return ArrayAccessor
	 */
	protected abstract function cacheAccessor();

	/**
	 * Returns table definition for a given property type
	 *
	 * @since 1.9
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	protected function getPropertyTables( $type, $dataItemId = true ) {

		$propertyTables = $this->store->getPropertyTables();

		if ( $dataItemId ) {
			$id = $this->store->findTypeTableId( $type );
		} else {
			$id = $this->store->findPropertyTableID( new DIProperty( $type ) );
		}

		return $propertyTables[$id];
	}
}
