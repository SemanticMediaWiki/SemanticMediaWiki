<?php

namespace SMW\Store;

use SMW\CacheableResultMapper;
use SMW\DIProperty;
use SMW\Settings;

use SMWRequestOptions;
use InvalidArgumentException;
use MWTimestamp;

/**
 * Class specifying an ObjectCollector and its concrete implementation
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface for items of groups of individuals to be sampled into a
 * collection of values
 *
 * @ingroup Collector
 */
interface ObjectCollector {

	/**
	 * Returns collected information
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function getResults();

}

/**
 * Base class specifying methods to represent a cacheable ObjectCollector
 *
 * @ingroup Collector
 * @ingroup Store
 */
abstract class CacheableObjectCollector implements ObjectCollector {

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

		$cacheableResult = new CacheableResultMapper( $this->cacheSetup()->set( 'prefix', 'collector' ) );

		$results = $cacheableResult->fetchFromCache();

		if ( $results ) {

			$this->isCached  = true;
			$this->results   = $results;
			$this->cacheDate = $cacheableResult->getCacheDate();
			wfDebug( get_called_class() . ' served from cache' . "\n" );

		} else {

			$this->results  = $this->doCollect();
			$this->isCached = false;
			$cacheableResult->recache( $this->results );
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
	 * @return CacheableObjectCollector
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
	 * results
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
	 * Sub-class is returning an ObjectDictionary that specifies details needed
	 * for the CachedResultMapper instantiation
	 *
	 * @par Example:
	 * @code
	 *  return new SimpleDictionary( array(
	 *     'id'      => 'Foo',
	 *     'type'    => 'FooType',
	 *     'enabled' => true or false,
	 *     'expiry'  => 3600
	 *  ) );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return ObjectDictionary
	 */
	protected abstract function cacheSetup();

	/**
	 * Returns table definition for a given property type
	 *
	 * @since 1.9
	 *
	 * @param string $type
	 *
	 * @return array
	 * @@codeCoverageIgnore
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
