<?php

namespace SMW\Store;

use SMW\CacheableResultMapper;
use SMW\ResultCollector;
use SMW\DIProperty;
use SMW\Settings;

use SMWRequestOptions;
use InvalidArgumentException;
use MWTimestamp;

/**
 * Base class thats represents a cacheable ResultCollector
 *
 * @ingroup Store
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class CacheableResultCollector implements ResultCollector {

	/** @var array */
	protected $results = array();

	/** @var SMWRequestOptions */
	protected $requestOptions = null;

	/** @var boolean */
	protected $isCached = false;

	/** @var string */
	protected $cacheDate = null;

	/**
	 * @see ResultCollector::getResults
	 *
	 * @since 1.9
	 *
	 * @return array
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

			$this->results  = $this->runCollector();
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
	 * @return CacheableResultCollector
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
	 * Sub-class is returning an ObjectDictionary that specifies details needed
	 * for the CacheableResultMapper instantiation
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
	 * @codeCoverageIgnore
	 * @since 1.9
	 *
	 * @param string $type
	 *
	 * @return TableDefinition
	 */
	protected function findPropertyTableByType( $type, $dataItemId = false ) {

		$propertyTables = $this->store->getPropertyTables();

		if ( $dataItemId ) {
			$id = $this->store->findTypeTableId( $type );
		} else {
			$id = $this->store->findPropertyTableID( new DIProperty( $type ) );
		}

		return $propertyTables[$id];
	}

}
