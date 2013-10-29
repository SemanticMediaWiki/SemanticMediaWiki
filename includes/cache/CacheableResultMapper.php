<?php

namespace SMW;

use InvalidArgumentException;
use MWTimestamp;

/**
 * Handling of cached results
 *
 * Convenience class that fetches results from cache or recaches the results and
 * stores meta information (cache time etc.) about each set with the object
 *
 * @code
 *  $dictionary = new SimpleDictionary( array(
 *   'id'      => ...,
 *   'type'    => ...,
 *   'enabled' => ...,
 *   'expiry'  => ...
 *  ) );
 *
 *  $resultCache = new CacheableResultMapper( $dictionary );
 *  $resultCache->recache( array() );
 *  $resultCache->fetchFromCache();
 *  $resultCache->getCacheDate();
 * @endcode
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CacheableResultMapper {

	/** @var ObjectDictionary */
	protected $cacheSetup;

	/**
	 * @since 1.9
	 *
	 * @param ObjectDictionary $cacheSetup
	 */
	public function __construct( ObjectDictionary $cacheSetup ) {
		$this->cacheSetup = $cacheSetup;
	}

	/**
	 * Fetches results from cache for a given CacheIdGenerator
	 *
	 * @since 1.9
	 *
	 * @return array|false
	 */
	public function fetchFromCache() {
		$result = $this->getCache()
			->setCacheEnabled( $this->cacheSetup->get( 'enabled' ) )
			->setKey( $this->getIdGenerator() )->get();

		return $result ? $this->mapping( $result ) : $result;
	}

	/**
	 * Stores results in the cache
	 *
	 * @note Each cache object stores the time and its results as serialized
	 * array in order to allow any arbitrary content to be cacheable
	 *
	 * @note Results are serialized as they can contain an array of objects
	 * where when retrieved from cache those objects are going to be
	 * unserialized to restore its former condition
	 *
	 * @since 1.9
	 *
	 * @param array $results
	 */
	public function recache( array $results ) {
		$this->getCache()
			->setCacheEnabled( $this->cacheSetup->get( 'enabled' ) && $results !== array() )
			->setKey( $this->getIdGenerator() )
			->set( array( 'time' => $this->getTimestamp(), 'result' => serialize( $results ) ), $this->cacheSetup->get( 'expiry' )
		);
	}

	/**
	 * Returns a CacheIdGenerator objects
	 *
	 * @since 1.9
	 *
	 * @return CacheIdGenerator
	 */
	public function getIdGenerator() {
		return new CacheIdGenerator( $this->cacheSetup->get( 'id' ), $this->cacheSetup->get( 'prefix' ) );
	}

	/**
	 * Returns the timestamp of the cached objects
	 *
	 * @since 1.9
	 *
	 * @return integer|null
	 */
	public function getCacheDate() {
		return $this->cacheSetup->has( 'cacheDate' ) ? $this->cacheSetup->get( 'cacheDate' ) : null;
	}

	/**
	 * Returns a CacheHandler instance
	 *
	 * @since 1.9
	 *
	 * @return CacheHandler
	 */
	public function getCache() {
		return CacheHandler::newFromId( $this->cacheSetup->get( 'type' ) );
	}

	/**
	 * Mapping of cached content
	 *
	 * Returns unserialized objects and the timestamp of the cached content
	 *
	 * @since 1.9
	 *
	 * @param array $resultCache
	 *
	 * @return array
	 */
	protected function mapping( array $resultCache ) {
		$this->cacheSetup->set( 'cacheDate', isset( $resultCache['time'] ) ? $resultCache['time'] : null );
		return isset( $resultCache['result'] ) ? unserialize( $resultCache['result'] ) : array();
	}

	/**
	 * Returns a timestamp
	 *
	 * @todo Apparently MW 1.19 does not have a MWTimestamp class, please
	 * remove this clutter as soon as MW 1.19 is not supported any longer
	 *
	 * @since 1.9
	 *
	 * @return integer
	 */
	protected function getTimestamp() {

		if ( class_exists( 'MWTimestamp' ) ) {
			$timestamp = new MWTimestamp();
			return $timestamp->getTimestamp( TS_UNIX );
		} else {
			// @codeCoverageIgnoreStart
			return wfTimestamp( TS_UNIX );
			// @codeCoverageIgnoreEnd
		}

	}

}
