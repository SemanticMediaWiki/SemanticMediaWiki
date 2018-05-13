<?php

namespace SMW;

use InvalidArgumentException;
use RuntimeException;

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
	 * @since 3.0
	 *
	 * @param string $data
	 * @param boolean $merge
	 */
	public function loadFromJSON( $data, $merge = false ) {

		$data = json_decode( $data, true );

		if ( ( $error = json_last_error() ) !== JSON_ERROR_NONE ) {
			throw new RuntimeException( 'JSON returned with a "' . json_last_error_msg() . '"' );
		}

		foreach ( $data as $key => $value ) {

			if ( $merge && isset( $this->options[$key] ) && is_array( $value ) && is_array( $this->options[$key] ) ) {
				$value = array_merge( $this->options[$key], $value );
			}

			$this->options[$key] = $value;
		}
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
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function dotGet( $key, $default = false ) {
		return $this->digDeep( $this->options, $key, $default );
	}

	private function digDeep( $array, $key, $default ) {

		if ( strpos( $key, '.' ) !== false ) {
			$list = explode( '.', $key, 2 );

			foreach ( $list as $k => $v ) {
				if ( isset( $array[$v] ) ) {
					return $this->digDeep( $array[$v], $list[$k+1], $default );
				}
			}
		}

		if ( isset( $array[$key] ) ) {
			return $array[$key];
		}

		return $default;
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
		return ( ( (int)$this->safeGet( $key, 0 ) & $flag ) == $flag );
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->options;
	}

	/**
	 * @deprecated since 3.0, use Options::toArray
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->toArray();
	}

	/**
	 * @since 3.0
	 *
	 * @param array $keys
	 *
	 * @return array
	 */
	public function filter( array $keys ) {

		$options = [];

		foreach ( $keys as $key ) {
			if ( isset( $this->options[$key] ) ) {
				$options[$key] = $this->options[$key];
			}
		}

		return $options;
	}

}
