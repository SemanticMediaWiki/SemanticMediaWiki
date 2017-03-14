<?php

namespace SMW;

use SMW\Utils\StatsFormatter;

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
	 * Stats as plain string
	 */
	const FORMAT_PLAIN = StatsFormatter::FORMAT_PLAIN;

	/**
	 * Stats as JSON output
	 */
	const FORMAT_JSON = StatsFormatter::FORMAT_JSON;

	/**
	 * Stats as HTML list output
	 */
	const FORMAT_HTML = StatsFormatter::FORMAT_HTML;

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
	private $poolCacheList = [];

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
	public function resetPoolCacheById( $poolCacheName = '' ) {
		foreach ( $this->poolCacheList as $key => $value ) {
			if ( $key === $poolCacheName || $poolCacheName === '' ) {
				unset( $this->poolCacheList[$key] );
			}
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param string|null $format
	 *
	 * @return string|array
	 */
	public function getStats( $format = null ) {
		return StatsFormatter::format( $this->computeStats(), $format );
	}

	/**
	 * @deprecated since 2.5, use InMemoryPoolCache::getPoolCacheById
	 * @since 2.3
	 *
	 * @param string $poolCacheName
	 * @param integer $cacheSize
	 *
	 * @return Cache
	 */
	public function getPoolCacheFor( $poolCacheName, $cacheSize = 500 ) {
		return $this->getPoolCacheById( $poolCacheName, $cacheSize );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $poolCacheId
	 * @param integer $cacheSize
	 *
	 * @return Cache
	 */
	public function getPoolCacheById( $poolCacheId, $cacheSize = 500 ) {

		if ( !isset( $this->poolCacheList[$poolCacheId] ) ) {
			$this->poolCacheList[$poolCacheId] = $this->cacheFactory->newFixedInMemoryCache( $cacheSize );
		}

		return $this->poolCacheList[$poolCacheId];
	}

	private function computeStats() {

		ksort( $this->poolCacheList );
		$stats = [];

		foreach ( $this->poolCacheList as $key => $value ) {
			$stats[$key] = [];

			$hits = 0;
			$misses = 0;

			foreach ( $value->getStats() as $k => $v ) {
				$stats[$key][$k] = $v;

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

			$stats[$key]['hit ratio'] = $hitRatio;
			$stats[$key]['miss ratio'] = $hitRatio > 0 ? round( 1 - $hitRatio, 4 ) : 0;
		}

		return $stats;
	}

}
