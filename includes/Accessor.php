<?php

namespace SMW;

use ArrayObject;
use InvalidArgumentException;

/**
 * Interface specifying access to an object
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
interface Accessor {

	/**
	 * Returns if a specific key can be accessed
	 *
	 * @since 1.9
	 *
	 * @param  mixed $key specific key
	 *
	 * @return boolean
	 */
	public function has( $key );

	/**
	 * Returns a value for a specific key
	 *
	 * @since 1.9
	 *
	 * @param  mixed $key specific key
	 *
	 * @return mixed
	 */
	public function get( $key );

	/**
	 * Sets a value for a specific key
	 *
	 * @since 1.9
	 *
	 * @param  mixed $key
	 * @param  mixed $value
	 *
	 * @return boolean
	 */
	public function set( $key, $value );

}
