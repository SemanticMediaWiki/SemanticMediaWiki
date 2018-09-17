<?php

namespace SMW\Elastic\QueryEngine\TermsLookup;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Parameters {

	/**
	 * @var array
	 */
	private $parameters = [];

	/**
	 * @since 3.0
	 */
	public function __construct( array $parameters = [] ) {
		$this->parameters = $parameters;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		$this->parameters[$key] = $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param array $value
	 */
	public function merge( $key, array $value ) {

		if ( !isset( $this->parameters[$key] ) ) {
			$this->parameters[$key] = [];
		}

		$this->parameters[$key] += $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has( $key ) {
		return isset( $this->parameters[$key] ) || array_key_exists( $key, $this->parameters );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function get( $key ) {

		if ( $this->has( $key ) ) {
			return $this->parameters[$key];
		}

		throw new InvalidArgumentException( "$key is an unregistered key." );
	}

}
