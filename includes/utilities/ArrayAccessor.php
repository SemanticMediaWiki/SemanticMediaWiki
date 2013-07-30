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

interface Arrayable {

	/**
	 * Returns an object as array
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function toArray();

}

/**
 * This class enables access to an arbitrary array
 *
 * @ingroup Utility
 */
class ArrayAccessor extends ArrayObject implements Accessor, Arrayable {

	/**
	 * Returns if a specified key is set or not
	 *
	 * @since 1.9
	 *
	 * @param mixed $key
	 *
	 * @return boolean
	 */
	public function has( $key ) {
		return $this->offsetExists( $key );
	}

	/**
	 * Exports the ArrayObject to an array
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->getArrayCopy();
	}

	/**
	 *  Sets the value to a specific key
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return ArrayAccessor
	 */
	public function set( $key, $value ) {
		$this[$key] = $value;
		return $this;
	}

	/**
	 * Returns a container value
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function get( $key ) {

		if ( !( $this->has( $key ) ) ) {
			throw new InvalidArgumentException( "'{$key}' is unknown" );
		}

		return $this->offsetGet( $key );
	}

}
