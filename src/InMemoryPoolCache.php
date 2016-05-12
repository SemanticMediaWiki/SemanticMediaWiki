<?php

namespace SMW;

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
	public function resetPoolCacheFor( $poolCacheName = '' ) {
		foreach ( $this->poolCacheList as $key => $value ) {
			if ( $key === $poolCacheName || $poolCacheName === '' ) {
				unset( $this->poolCacheList[$key] );
			}
		}
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
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getFormattedStats() {

		$stats = '';
		ksort( $this->poolCacheList );

		foreach ( $this->poolCacheList as $key => $value ) {
			$stats .= '- ' . $key . "\n";

			$hits = 0;
			$misses = 0;

			foreach ( $value->getStats() as $k => $v ) {
				$stats .= '  - ' . $k . ' ' . $v . "\n";

				if ( $k === 'hits' ) {
					$hits = $v;
				}

				if ( $k === 'inserts' ) {
					$misses = $v;
				}

				if ( $k === 'misses' && $v > 0 ) {
					$misses = $v;
				}
			}

			$hitRatio = $hits > 0 ? round( $hits / ( $hits + $misses ), 4 ) : 0;
			$stats .= '  - ' . 'hit ratio ' . $hitRatio . ', miss ratio ' . round( 1 - $hitRatio, 4 ) . "\n";
		}

		return $stats;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $poolCacheName
	 * @param integer $cacheSize
	 *
	 * @return Cache
	 */
	public function getPoolCacheFor( $poolCacheName, $cacheSize = 500 ) {

		if ( !isset( $this->poolCacheList[$poolCacheName] ) ) {
			$this->poolCacheList[$poolCacheName] = $this->cacheFactory->newFixedInMemoryCache( $cacheSize );
		}

		return $this->poolCacheList[$poolCacheName];
	}

}
