<?php

namespace SMW;

use ObjectCache;
use BagOStuff;

/**
 * Encapsulate access to MW's BagOStuff class
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CacheHandler {

	/** @var BagOStuff */
	protected $cache = null;

	/** @var string */
	protected $key = false;

	/** @var boolean */
	protected $cacheEnabled = false;

	/** @var CacheHandler[] */
	private static $instance = array();

	/**
	 * @since 1.9
	 *
	 * @param BagOStuff|null $cache
	 */
	public function __construct( BagOStuff $cache = null ) {
		$this->cache = $cache;
	}

	/**
	 * Factory method that creates a CacheHandler instance and instantiate a
	 * BagOStuff object from available settings ($smwgCacheType) while
	 * invoking additional parameters ($wgCachePrefix etc.)
	 *
	 * @par Example:
	 * @code
	 *  $cache = new CacheHandler::newFromId()->setkey( new CachIdGenerator( 'Foo' ) )
	 *
	 *  $cache->set( 'CacheableObject' )
	 *  $cache->get() returns 'CacheableObject'
	 *  $cache->delete() deletes 'CacheableObject'
	 * @endcode
	 *
	 * @note If a BagOStuff instance is not available setCacheEnabled() is
	 * disabled by default which prevents to run into unintended behaviour
	 * while trying to access BagOStuff methods.
	 *
	 * @note This method is exposed to $wgObjectCaches, $wgCachePrefix globals
	 * which can't and shouldn't be accessed otherwise. It is the task of this
	 * method alone to invoke globals and avoid leakage into the object
	 * life cycle.
	 *
	 * @note This method serves invoked instances from a static variable due to
	 * the fact that the actual working object is being accessed through
	 * getCache() and therefore not in direct conflict with its testability.
	 *
	 * @since 1.9
	 *
	 * @param string $id Ids available in wgObjectCaches
	 *
	 * @return CacheHandler
	 */
	public static function newFromId( $id = false ) {

		$cacheType = $id ? $id : Settings::newFromGlobals()->get( 'smwgCacheType' );

		if ( !isset( self::$instance[$cacheType] ) ) {

			if ( $cacheType && array_key_exists( $cacheType, $GLOBALS['wgObjectCaches'] ) ) {
				$cache = new self( ObjectCache::getInstance( $cacheType ) );
			} else {
				$cache = new self;
			}

			$cache->setCacheEnabled( true );

			self::$instance[$cacheType] = $cache;
		}

		return self::$instance[$cacheType];
	}

	/**
	 * Returns key
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * Sets key
	 *
	 * @since 1.9
	 *
	 * @param IdGenerator $id
	 *
	 * @return CacheHandler
	 */
	public function setKey( IdGenerator $id ) {
		$this->key = $id->generateId();
		return $this;
	}

	/**
	 * Returns invoked cache instance
	 *
	 * @since 1.9
	 *
	 * @return BagOStuff|null
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * Stores an object in cache for the invoked key
	 *
	 * @since 1.9
	 *
	 * @param mixed $value
	 * @param int $exptime
	 *
	 * @return boolean
	 */
	public function set( $value, $exptime = 0 ) {
		return $this->isEnabled() ? $this->getCache()->set( $this->getKey(), $value, $exptime ) : false;
	}

	/**
	 * Returns object from cache for the invoked key
	 *
	 * @since 1.9
	 *
	 * @return mixed|false
	 */
	public function get() {
		return $this->isEnabled() ? $this->getCache()->get( $this->getKey() ) : false;
	}

	/**
	 * Deletes object from cache for the invoked key
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function delete() {
		return $this->isEnabled() ? $this->getCache()->delete( $this->getKey() ) : false;
	}

	/**
	 * Sets availability for the current instance
	 *
	 * @note It will assert the availability of the BagOStuff object by default
	 * and return false independently from the parameter invoked (this
	 * safeguards against overriding the availability status of a non-BagOStuff
	 * instance)
	 *
	 * @since 1.9
	 *
	 * @param boolean $cacheEnabled
	 *
	 * @return CacheHandler
	 */
	public function setCacheEnabled( $cacheEnabled ) {
		$this->cacheEnabled = $this->getCache() instanceof BagOStuff ? (bool)$cacheEnabled : false;
		return $this;
	}

	/**
	 * Returns current status of the cache instance
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		return $this->cacheEnabled && $this->key;
	}

	/**
	 * Reset instance
	 *
	 * @since 1.9
	 */
	public static function reset() {
		self::$instance = array();
	}
}
