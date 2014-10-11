<?php

namespace SMW\Cache;

/**
 * Implements a simple LRU (Least Recently Used) algorithm for a fixed in-memory
 * hashmap
 *
 * @note For a size of more than 10K it is suggested to use PHP's SplFixedArray
 * instead as it is optimized for large array sets
 *
 * @license GNU GPL v2+
 * @since 2.1
 */
class FixedInMemoryCache implements Cache {

	/**
	 * @var array
	 */
	private $cache = array();

	/**
	 * @var integer
	 */
	private $maxCacheIds;

	/**
	 * @var integer
	 */
	private $count = 0;

	/**
	 * @var integer
	 */
	private $cacheHits = 0;

	/**
	 * @var integer
	 */
	private $cacheMisses = 0;

	/**
	 * @since 2.1
	 *
	 * @param integer $maxCacheIds
	 */
	public function __construct( $maxCacheIds = 500 ) {
		$this->maxCacheIds = (int)$maxCacheIds;
	}

	/**
	 * @since 2.1
	 *
	 * {@inheritDoc}
	 */
	public function contains( $id ) {
		return isset( $this->cache[ $id ] ) || array_key_exists( $id, $this->cache );
	}

	/**
	 * @since 2.1
	 *
	 * {@inheritDoc}
	 */
	public function fetch( $id ) {

		if ( $this->contains( $id ) ) {
			$this->cacheHits++;
			return $this->moveToMostRecentlyUsed( $id );
		}

		$this->cacheMisses++;
		return false;
	}

	/**
	 * @since 2.1
	 *
	 * {@inheritDoc}
	 */
	public function save( $id, $value, $ttl = 0 ) {
		$this->count++;

		if ( $this->contains( $id ) ) {
			$this->count--;
			$this->moveToMostRecentlyUsed( $id );
		} elseif ( $this->count > $this->maxCacheIds ) {
			$this->count--;
			reset( $this->cache );
			unset( $this->cache[ key( $this->cache ) ] );
		}

		$this->cache[ $id ] = $value;
	}

	/**
	 * @since 2.1
	 *
	 * {@inheritDoc}
	 */
	public function delete( $id ) {

		if ( $this->contains( $id ) ) {
			$this->count--;
			unset( $this->cache[ $id ] );
			return true;
		}

		return false;
	}

	/**
	 * @since 2.1
	 */
	public function reset() {
		$this->cache = array();
		$this->count = 0;
		$this->cacheMisses = 0;
		$this->cacheHits = 0;
	}

	/**
	 * @since 2.1
	 *
	 * {@inheritDoc}
	 */
	public function getStats() {
		return array(
			'max'    => $this->maxCacheIds,
			'count'  => $this->count,
			'hits'   => $this->cacheHits,
			'misses' => $this->cacheMisses
		);
	}

	private function moveToMostRecentlyUsed( $id ) {
		$value = $this->cache[ $id ];
		unset( $this->cache[ $id ] );
		$this->cache[ $id ] = $value;

		return $value;
	}

}
