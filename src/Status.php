<?php

namespace SMW;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class Status {

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * @since 2.3
	 */
	public function __construct( array $options = [] ) {
		$this->options = $options;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has( string $key ) : bool {
		return isset( $this->options[$key] ) || array_key_exists( $key, $this->options );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	public function is( string $key, $value ) : bool {
		return $this->get( $key ) === $value;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $key
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function get( string $key ) {

		if ( $this->has( $key ) ) {
			return $this->options[$key];
		}

		throw new InvalidArgumentException( "{$key} is an unregistered key!" );
	}

}
