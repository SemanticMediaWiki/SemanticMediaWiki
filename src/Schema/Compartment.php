<?php

namespace SMW\Schema;

use JsonSerializable;
use SMW\Utils\DotArray;
use IteratorAggregate;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class Compartment implements JsonSerializable, IteratorAggregate {

	/**
	 * An internal key to track the association to a schema the compartment is
	 * part of.
	 */
	const ASSOCIATED_SCHEMA = '___assoc_schema';

	/**
	 * An internal key to identify a possible section the compartment is part of.
	 */
	const ASSOCIATED_SECTION = '___assoc_section';

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @since 3.1
	 *
	 * @param array $data
	 */
	public function __construct( array $data = [] ) {
		$this->data = $data;
	}

	/**
	 * @since 3.2
	 *
	 * @return boolean
	 */
	public function isEmpty() : bool {
		return $this->data === [];
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has( $key ) {
		return $this->get( $key, false ) !== false;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed|null
	 */
	public function get( $key, $default = null ) {
		return DotArray::get( $this->data, $key, $default );
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function jsonSerialize() {
		return json_encode( $this->data );
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->jsonSerialize();
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getFingerprint() {
		return sha1( $this->jsonSerialize() );
	}

	/**
	 * @see IteratorAggregate::getIterator
	 * @since 3.2
	 *
	 * @return Iterator
	 */
	public function getIterator() {

		foreach ( $this->data as $key => $value ) {

			if ( is_string( $value ) ) {
				continue;
			}

			if ( isset( $this->data[self::ASSOCIATED_SCHEMA] ) ) {
				$value[self::ASSOCIATED_SCHEMA] = $this->data[self::ASSOCIATED_SCHEMA];
			}

			$value[self::ASSOCIATED_SECTION] = $key;

			yield new Compartment( $value );
		}
	}

}
