<?php

namespace SMW;

use SMW\ApplicationFactory;
use Onoi\BlobStore\BlobStore;

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
	 * @var FixedInMemoryCache
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	private $poolCacheList = array();

	/**
	 * @since 2.3
	 *
	 * @return FixedInMemoryCache
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
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
	 * @since 2.2
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
	 * @since 2.2
	 *
	 * @param string $poolCacheName
	 *
	 * @return Cache
	 */
	public function getPoolCacheFor( $poolCacheName ) {

		if ( !isset( $this->poolCacheList[$poolCacheName] ) ) {
			$this->poolCacheList[$poolCacheName] = ApplicationFactory::getInstance()->newCacheFactory()->newFixedInMemoryCache( 1500 );
		}

		return $this->poolCacheList[$poolCacheName];
	}

}
