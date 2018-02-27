<?php

namespace SMW;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ProcessLruCache {

	const POOLCACHE_ID = 'smw.process.cache';

	/**
	 * @var Cache[]
	 */
	private $caches = array();

	/**
	 * @since 3.0
	 *
	 * @param array $cache
	 */
	public function __construct( array $caches ) {
		$this->caches = $caches;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $config
	 *
	 * @return self
	 */
	public static function newFromConfig( array $config ) {

		$inMemoryPoolCache = ApplicationFactory::getInstance()->getInMemoryPoolCache();
		$caches = array();

		foreach ( $config as $id => $cacheSize ) {
			$caches[$id] = $inMemoryPoolCache->getPoolCacheById( self::POOLCACHE_ID . '.' . $id, $cacheSize );
		}

		return new self( $caches );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 *
	 * @return Cache
	 * @throws RuntimeException
	 */
	public function get( $id ) {

		if ( isset( $this->caches[$id] ) ) {
			return $this->caches[$id];
		}

		throw new RuntimeException( "$id is not registered" );
	}

	/**
	 * @since 3.0
	 */
	public function reset() {

		$inMemoryPoolCache = ApplicationFactory::getInstance()->getInMemoryPoolCache();
		$caches = array();

		foreach ( $this->caches as $id => $cache ) {
			$stats = $cache->getStats();
			$inMemoryPoolCache->resetPoolCacheById( self::POOLCACHE_ID . '.' . $id );
			$caches[$id] = $inMemoryPoolCache->getPoolCacheById( self::POOLCACHE_ID . '.' . $id, $stats['max'] );
		}

		$this->caches = $caches;
		unset( $caches );
	}

}
