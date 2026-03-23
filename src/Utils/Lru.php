<?php

namespace SMW\Utils;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class Lru {

	/**
	 * @var array
	 */
	private $cache = [];

	private int $count = 0;

	/**
	 * @since 3.0
	 */
	public function __construct( private $size = 1000 ) {
	}

	/**
	 * @since 3.0
	 *
	 * @param string|int $key
	 * @param mixed $value
	 */
	public function set( $key, $value ): void {
		$this->count++;

		if ( isset( $this->cache[$key] ) ) {
			$this->count--;
			$value = $this->cache[$key];
			unset( $this->cache[$key] );
		} elseif ( $this->count > $this->size ) {
			$this->count--;
			reset( $this->cache );
			unset( $this->cache[ key( $this->cache ) ] );
		}

		$this->cache[$key] = $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|int $key
	 * @param mixed|null $default
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		if ( !isset( $this->cache[$key] ) ) {
			return $default;
		}

		$value = $this->cache[$key];
		unset( $this->cache[$key] );
		$this->cache[$key] = $value;

		return $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|int $key
	 */
	public function delete( $key ) {
		if ( !isset( $this->cache[$key] ) ) {
			return $default;
		}

		$this->count--;
		unset( $this->cache[$key] );
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->cache;
	}

}
