<?php

namespace SMW;

/**
 * This class is responsible for generating a Hash Id
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * This interface is responsible for generating an id
 *
 * @ingroup Utility
 */
interface IdGenerator {

	/**
	 * Generates an id
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function generateId();

}

/**
 * This class is responsible for generating a Hash Id
 *
 * @ingroup Utility
 */
class HashIdGenerator implements IdGenerator {

	/** @var string */
	protected $hashable = false;

	/** @var string */
	protected $prefix;

	/**
	 * @since 1.9
	 *
	 * @param mixed $hashable
	 * @param mixed|null $prefix
	 */
	public function __construct( $hashable, $prefix = null ) {
		$this->hashable = $hashable;
		$this->prefix = $prefix;
	}

	/**
	 * Returns prefix
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * Returns a generated concatenated key string
	 *
	 * @par Example:
	 * @code
	 *  $key = new HashIdGenerator( 'Foo', 'Lula' )
	 *  $key->generateId() returns < Lula > + < 'Foo' md5 hash >
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function generateId() {
		return $this->getPrefix() . md5( json_encode( array( $this->hashable ) ) );
	}
}
