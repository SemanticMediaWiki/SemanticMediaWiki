<?php

namespace SMW\Elastic\Connection;

use Onoi\Cache\Cache;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class LockManager {

	/**
	 * Identifies the cache namespace
	 */
	const CACHE_NAMESPACE = 'smw:elastic';
	const TYPE_LOCK = 'lock';
	const TYPE_MAINTENANCE = 'maintenance';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @since 3.1
	 *
	 * @param Cache $cache
	 */
	public function __construct( Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * @since 3.1
	 */
	public function hasMaintenanceLock() {

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			self::TYPE_MAINTENANCE
		);

		return $this->cache->fetch( $key ) !== false;
	}

	/**
	 * @since 3.1
	 */
	public function setMaintenanceLock() {

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			self::TYPE_MAINTENANCE
		);

		$this->cache->save( $key, true );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $type
	 * @param string $version
	 */
	public function setLock( $type, $version ) {

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			[ 'lock', $type ]
		);

		$this->cache->save( $key, $version );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $type
	 *
	 * @return boolean
	 */
	public function hasLock( $type ) {

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			[ 'lock', $type ]
		);

		return $this->cache->fetch( $key ) !== false;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $type
	 *
	 * @return mixed
	 */
	public function getLock( $type ) {

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			[ 'lock', $type ]
		);

		return $this->cache->fetch( $key );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $type
	 */
	public function releaseLock( $type ) {

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			[ 'lock', $type ]
		);

		$this->cache->delete( $key );

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			self::TYPE_MAINTENANCE
		);

		$this->cache->delete( $key );
	}

}
