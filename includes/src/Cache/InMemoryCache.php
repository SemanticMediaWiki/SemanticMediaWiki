<?php

namespace SMW\Cache;

/**
 * Implements a simple LRU (Least Recently Used) algorithm for an in-memory
 * hashmap
 *
 * @license GNU GPL v2+
 * @since 2.1
 */
class InMemoryCache implements Cache {

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
	public $cacheHit = 0;

	/**
	 * @var integer
	 */
	public $cacheMiss = 0;

	/**
	 * @since 2.1
	 *
	 * @param integer $maxCacheIds
	 */
	public function __construct( $maxCacheIds = 500 ) {
		$this->maxCacheIds = $maxCacheIds;
	}

	/**
	 * @since 2.1
	 *
	 * @param mixed $id
	 *
	 * @return boolean
	 */
	public function has( $id ) {
		return isset( $this->cache[ $id ] ) || array_key_exists( $id, $this->cache );
	}

	/**
	 * @since 2.1
	 *
	 * @param mixed $id
	 *
	 * @return mixed|boolean
	 */
	public function get( $id ) {

		if ( $this->has( $id ) ) {
			$this->cacheHit++;
			return $this->moveToMostRecentlyUsed( $id );
		}

		$this->cacheMiss++;
		return false;
	}

	/**
	 * @since 2.1
	 *
	 * @param mixed $id
	 * @param mixed $value
	 */
	public function set( $id, $value, $ttl = 0 ) {
		$this->count++;

		if ( $this->has( $id ) ) {
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
	 * @param mixed $id
	 *
	 * @return boolean
	 */
	public function delete( $id ) {

		if ( $this->has( $id ) ) {
			$this->count--;
			unset( $this->cache[ $id ] );
			return true;
		}

		return false;
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function isSafe() {
		return true;
	}

	/**
	 * @since 2.1
	 */
	public function reset() {
		$this->cache = array();
		$this->count = 0;
		$this->cacheMiss = 0;
		$this->cacheHit = 0;
	}

	/**
	 * @since 2.1
	 *
	 * @return integer
	 */
	public function getCount() {
		return $this->count;
	}

	private function moveToMostRecentlyUsed( $id ) {
		$value = $this->cache[ $id ];
		unset( $this->cache[ $id ] );
		$this->cache[ $id ] = $value;

		return $value;
	}

}
