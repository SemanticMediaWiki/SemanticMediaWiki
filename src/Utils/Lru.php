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
	 * @var int
	 */
	private $size;

	/**
	 * @var array
	 */
	private $cache = [];

	/** @var int */
	private $count = 0;

	/**
	 * @since 3.0
	 *
	 * @param int $size
	 */
	public function __construct( $size = 1000 ) {
		$this->size = $size;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|int $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
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
