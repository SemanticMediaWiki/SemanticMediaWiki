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
	 * Stats as plain string
	 */
	const FORMAT_PLAIN = 'plain';

	/**
	 * Stats as JSON output
	 */
	const FORMAT_JSON = 'json';

	/**
	 * Stats as HTML list output
	 */
	const FORMAT_HTML = 'html';

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
	 * @param string $format
	 *
	 * @return string
	 */
	public function getFormattedStats( $format = self::FORMAT_PLAIN ) {

		$stats = $this->computeStats();
		$output = '';

		if ( $format === self::FORMAT_PLAIN ) {
			foreach ( $stats as $key => $value ) {
				$output .= '- ' . $key . "\n";

				foreach ( $value as $k => $v ) {
					$output .= '  - ' . $k . ': ' . $v . "\n" ;
				}
			}
		}

		if ( $format === self::FORMAT_HTML ) {
			$output .= '<ul>';
			foreach ( $stats as $key => $value ) {
				$output .= '<li>' . $key . '<ul>';
				foreach ( $value as $k => $v ) {
					$output .= '<li>' . $k . ': ' . $v . "</li>" ;
				}
				$output .= '</ul></li>';
			}
			$output .= '</ul>';
		}

		if ( $format === self::FORMAT_JSON ) {
			$output .= json_encode( $stats, JSON_PRETTY_PRINT );
		}

		return $output;
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
		return $this->getPoolCacheById( $poolCacheName, $cacheSize );
	}

	/**
	 * @since 2.3
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
		$stats = array();

		foreach ( $this->poolCacheList as $key => $value ) {
			$stats[$key] = array();

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
