<?php

namespace SMW;

/**
 * Interface specifying methods for an accessible object
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface Accessible {

	/**
	 * Returns whether a specific element is accessible
	 *
	 * @since 1.9
	 *
	 * @param  mixed $key specific key
	 *
	 * @return boolean
	 */
	public function has( $key );

	/**
	 * Returns a element for a specific key
	 *
	 * @since 1.9
	 *
	 * @param  mixed $key specific key
	 *
	 * @return mixed
	 */
	public function get( $key );

}

/**
 * Interface specifying methods for a changeable object
 *
 * @ingroup Utility
 */
interface Changeable {

	/**
	 * Adds an new element (key, value pair) to an existing collection
	 *
	 * @since 1.9
	 *
	 * @param  mixed $key
	 * @param  mixed $value
	 */
	public function set( $key, $value );

	/**
	 * Removes an element from a collection
	 *
	 * @since 1.9
	 *
	 * @param  mixed $key specific key
	 */
	public function remove( $key );

}

/**
 * Interface specifying methods for a combinable object
 *
 * @ingroup Utility
 */
interface Combinable {

	/**
	 * Returns an array
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function toArray();

	/**
	 * Merges elements of one or more arrays together
	 *
	 * @since 1.9
	 *
	 * @param array $mergeable
	 */
	public function merge( array $mergeable );

}

/**
 * Interface specifying an object dictionary
 *
 * @ingroup Utility
 */
interface ObjectDictionary extends Accessible, Changeable, Combinable {
}
