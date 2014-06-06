<?php

namespace SMW\Cache;

/**
 * @ingroup Cache
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class InMemoryCache {

	protected $cache = array();
	protected $expiry = array();

	/**
	 * @since 1.9.3
	 *
	 * @param array $cache
	 */
	public function __construct( $cache = array() ) {
		$this->cache = $cache;
	}

	/**
	 * @since 1.9.3
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has( $key ) {
		return isset( $this->cache[ $key ] ) || array_key_exists( $key, $this->cache );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param string $key
	 *
	 * @return mixed|false
	 */
	public function get( $key ) {

		if ( $this->has( $key ) ) {
			return $this->fetchValueOrRemoveIfNotExists( $key );
		}

		return false;
	}

	/**
	 * @since 1.9.3
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param integer $expiryTime in seconds
	 *
	 * @return boolean
	 */
	public function set( $key, $value, $expiryTime = 0 ) {

		$this->cache[ $key ] = $value;

		if ( $expiryTime !== 0 ) {
			$this->expiry[ $key ] = time() + $expiryTime;
		}

		return true;
	}

	/**
	 * @since 1.9.3
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function delete( $key ) {

		if ( $this->has( $key ) ) {
			unset( $this->cache[ $key ] );
			unset( $this->expiry[ $key ] );
			return true;
		}

		return false;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return boolean
	 */
	public function isSafe() {
		return true;
	}

	private function fetchValueOrRemoveIfNotExists( $key ) {

		if ( !isset( $this->expiry[ $key ] ) || $this->expiry[ $key ] >= time() ) {
			return $this->cache[ $key ];
		}

		$this->delete( $key );

		return false;
	}

}
