<?php

namespace SMW;

/**
 * Class implementing non-persistent storage and retrieval of objects using an
 * associative array
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class ObjectStorage {

	/** @var array */
	protected $storage = array();

	/**
	 * @since 1.9
	 *
	 * @param array $storage
	 */
	public function __construct( array $storage = array() ) {
		$this->storage = $storage;
	}

	/**
	 * Returns whether the storage contains an element for a given key
	 *
	 * @note Using isset() for performance reasons and checking the NULL
	 * element with the help of array_key_exists
	 *
	 * @since  1.9
	 *
	 * @param mixed $key
	 *
	 * @return boolean
	 */
	protected function contains( $key ) {
		return isset( $this->storage[$key] ) || array_key_exists( $key, $this->storage );
	}

	/**
	 * Append an element to a collection
	 *
	 * @since  1.9
	 *
	 * @param string $key
	 * @param mixed|null $key
	 */
	protected function attach( $key, $value = null ) {
		$this->storage[$key] = $value;
	}

	/**
	 * Finds an element (if any) that is bound to a given key
	 *
	 * @since  1.9
	 *
	 * @param string $key
	 *
	 * @return mixed|null
	 */
	protected function lookup( $key ) {
		return $this->storage[$key];
	}

	/**
	 * Remove an element from a collection
	 *
	 * @since  1.9
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	protected function detach( $key ) {

		if ( $this->contains( $key ) ) {
			unset( $this->storage[$key] );
			reset( $this->storage );
			return true;
		}

		return false;
	}

}
