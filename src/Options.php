<?php

namespace SMW;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class Options {

	/**
	 * @var array
	 */
	private $options = array();

	/**
	 * @since 2.3
	 */
	public function __construct( array $options = array() ) {
		$this->options = $options;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 */
	public function delete( $key ) {
		unset( $this->options[ $key ] );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has( $key ) {
		return isset( $this->options[$key] ) || array_key_exists( $key, $this->options );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $key
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function get( $key ) {

		if ( $this->has( $key ) ) {
			return $this->options[$key];
		}

		throw new InvalidArgumentException( "{$key} is an unregistered option" );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function safeGet( $key, $default = false ) {
		return $this->has( $key ) ? $this->options[$key] : $default;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param integer $flag
	 *
	 * @return boolean
	 */
	public function isFlagSet( $key, $flag ) {
		return ( ( $this->safeGet( $key ) & $flag ) == $flag );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return boolean
	 */
	public function isValueSet( $key, $value ) {
		return $this->safeGet( $key ) === $value;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

}
