<?php

namespace SMW;

use SMW\ApplicationFactory;

/**
 * A multipurpose non-persistent static pool cache to keep selected items for
 * the duration of a request cacheable.
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class InMemoryPoolCache {

	/**
	 * @var InMemoryPoolCache
	 */
	private static $instance = null;

	/**
	 * @var CacheFactory
	 */
	private $cacheFactory = null;

	/**
	 * @var array
	 */
	private $poolCacheList = array();

	/**
	 * @since 2.3
	 *
	 * @param CacheFactory $cacheFactory
	 */
	public function __construct( CacheFactory $cacheFactory ) {
		$this->cacheFactory = $cacheFactory;
	}

	/**
	 * @since 2.3
	 *
	 * @return InMemoryPoolCache
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self( ApplicationFactory::getInstance()->newCacheFactory() );
		}

		return self::$instance;
	}

	/**
	 * @since 2.3
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $poolCacheName
	 */
	public function resetPoolCacheFor( $poolCacheName ) {
		unset( $this->poolCacheList[$poolCacheName] );
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getStats() {

		$stats = array();

		foreach ( $this->poolCacheList as $key => $value ) {
			$stats[$key] = $value->getStats();
		}

		return $stats;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $poolCacheName
	 *
	 * @return Cache
	 */
	public function getPoolCacheFor( $poolCacheName ) {

		if ( !isset( $this->poolCacheList[$poolCacheName] ) ) {
			$this->poolCacheList[$poolCacheName] = $this->cacheFactory->newFixedInMemoryCache( 500 );
		}

		return $this->poolCacheList[$poolCacheName];
	}

}
