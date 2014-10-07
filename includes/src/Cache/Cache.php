<?php

namespace SMW\Cache;

/**
 * Interface for specifying access to a cache instance
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
interface Cache {

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 *
	 * @return boolean
	 */
	public function has( $id );

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function get( $id );

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 * @param mixed $value
	 * @param integer $ttl
	 *
	 * @return mixed
	 */
	public function set( $id, $value, $ttl );

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 *
	 * @return boolean
	 */
	public function delete( $id );

	/**
	 * Whether the current instance can be safely used and allows an interaction
	 * with a cache instance
	 *
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function isSafe();

}
