<?php

namespace SMW\Cache;

use MapCacheLRU;

/**
 * Request-scoped, bounded in-process LRU cache backed by MediaWiki core's
 * {@link MapCacheLRU}. It reproduces the small surface SMW's in-process pool
 * caches rely on (`fetch`/`contains`/`save`/`delete` plus `getStats`, the
 * surface of the former bundled fixed-size in-memory cache).
 *
 * This is deliberately a concrete, final adapter rather than a general cache
 * interface: it exposes only the methods its pool consumers call, is never
 * backed by a persistent or cross-request store, and synthesises the
 * occupancy/hit counters that `MapCacheLRU` does not track. `fetch()` returns
 * the `false` miss-sentinel and `contains()` maps to `MapCacheLRU::has()`, so
 * the pool consumer call sites keep working unchanged.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
final class InMemoryLruCache {

	private MapCacheLRU $cache;
	private int $inserts = 0;
	private int $deletes = 0;
	private int $hits = 0;
	private int $misses = 0;

	/**
	 * @since 7.0.0
	 *
	 * @param int|string $maxCacheCount Cast to int to match the tolerance of
	 *  the former Onoi cache, which accepted string pool sizes from config. A
	 *  non-positive size is clamped to 1, since MapCacheLRU requires a positive
	 *  capacity where the former cache treated a 0 size as "hold nothing".
	 */
	public function __construct( $maxCacheCount = 500 ) {
		$this->cache = new MapCacheLRU( max( 1, (int)$maxCacheCount ) );
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $key
	 *
	 * @return mixed The cached value, or `false` when the key is absent.
	 */
	public function fetch( $key ) {
		if ( $this->cache->has( $key ) ) {
			$this->hits++;
			return $this->cache->get( $key );
		}

		$this->misses++;
		return false;
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $key
	 */
	public function contains( $key ): bool {
		return $this->cache->has( $key );
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Accepted for call-shape compatibility with the former
	 *  Onoi cache; the in-process LRU bounds entries by count, not time, so the
	 *  value is ignored.
	 */
	public function save( $key, $value, $ttl = 0 ): void {
		$this->inserts++;
		$this->cache->set( $key, $value );
	}

	/**
	 * @since 7.0.0
	 *
	 * @param string $key
	 *
	 * @return bool Whether an entry was present and removed.
	 */
	public function delete( $key ): bool {
		if ( !$this->cache->has( $key ) ) {
			return false;
		}

		$this->deletes++;
		$this->cache->clear( [ $key ] );
		return true;
	}

	/**
	 * Occupancy and hit/miss counters in the shape the former
	 * `FixedInMemoryLruCache` exposed, consumed by
	 * `InMemoryPoolCache::getStats()` and `rebuildData --report-poolcache`.
	 *
	 * @since 7.0.0
	 */
	public function getStats(): array {
		return [
			'inserts' => $this->inserts,
			'deletes' => $this->deletes,
			'max' => $this->cache->getMaxSize(),
			'count' => count( $this->cache->getAllKeys() ),
			'hits' => $this->hits,
			'misses' => $this->misses,
		];
	}

}
