<?php

namespace SMW;

/**
 * Generating a Hash Id
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * This generating a Hash Id from an arbitrary content
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
	 * Sets prefix
	 *
	 * @since 1.9
	 *
	 * @param string $prefix
	 *
	 * @return HashIdGenerator
	 */
	public function setPrefix( $prefix ) {
		$this->prefix = $prefix;
		return $this;
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
	 * Generates an Id
	 *
	 * It returns a string that is concatenated of a prefix and a md5 value
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
