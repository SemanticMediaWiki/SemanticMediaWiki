<?php

namespace SMW\Cache;

/**
 * Specifying a common interface to access a cache instance
 *
 * @note The interface is made similar to Doctrine\Common\Cache and allows for a
 * drop-in replacement at a later occasion
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
interface Cache {

	/**
	 * Returns a cache item or false if no entry was found
	 *
	 * @since 2.1
	 *
	 * @param string $id
	 *
	 * @return mixed|false
	 */
	public function fetch( $id );

	/**
	 * Whether an entry is available for the given id
	 *
	 * @since 2.1
	 *
	 * @param string $id
	 *
	 * @return boolean
	 */
	public function contains( $id );

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 * @param mixed $data
	 * @param integer $ttl
	 *
	 * @return mixed
	 */
	public function save( $id, $data, $ttl = 0 );

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 *
	 * @return boolean
	 */
	public function delete( $id );

	/**
	 * @since 2.1
	 *
	 * @return array|null
	 */
	public function getStats();

}
