<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class UrlArgs {

	/**
	 * @var array
	 */
	private $args = [];

	/**
	 * @var array
	 */
	private $fragment = '';

	/**
	 * @since 3.2
	 *
	 * @param array $args
	 */
	public function __construct( array $args = [] ) {
		$this->args = $args;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function set( $key, $value ) {
		$this->args[$key] = $value;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return isset( $this->args[$key] ) ? $this->args[$key] : $default;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param int|null $default
	 *
	 * @return int|null
	 */
	public function getInt( string $key, ?int $default = null ) : ?int {

		if ( isset( $this->args[$key] ) ) {
			return (int)$this->args[$key];
		}

		return $default;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 *
	 * @return array
	 */
	public function getArray( string $key ) : array {

		if ( !isset( $this->args[$key] ) || $this->args[$key] === '' ) {
			return [];
		}

		return (array)$this->args[$key];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 */
	public function delete( $key ) {
		unset( $this->args[$key] );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $fragment
	 */
	public function setFragment( $fragment ) {
		$this->fragment = $fragment;
	}

	/**
	 * @see __toString
	 */
	public function toArray() {
		return $this->args;
	}

	/**
	 * @since 3.2
	 */
	public function clone() : self {
		return clone $this;
	}

	/**
	 * @see __toString
	 */
	public function __toString() {
		return $this->cgi( $this->args ) . ( $this->fragment !== '' ? '#' . $this->fragment : '' );
	}

	/**
	 * @see wfArrayToCgi
	 */
	private function cgi( $args, $prefix = '' ) {
		$cgi = '';

		foreach ( $args as $key => $value ) {
			if ( $value !== null && $value !== false ) {

				if ( $cgi != '' ) {
					$cgi .= '&';
				}

				if ( $prefix !== '' ) {
					$key = $prefix . "[$key]";
				}

				if ( is_array( $value ) ) {
					$firstTime = true;
					foreach ( $value as $k => $v ) {
						$cgi .= $firstTime ? '' : '&';
						if ( is_array( $v ) ) {
							$cgi .= $this->cgi( $v, $key . "[$k]" );
						} else {
							$cgi .= urlencode( $key . "[$k]" ) . '=' . urlencode( $v );
						}
						$firstTime = false;
					}
				} else {
					if ( is_object( $value ) ) {
						$value = $value->__toString();
					}
					$cgi .= urlencode( $key ) . '=' . urlencode( $value );
				}
			}
		}

		return $cgi;
	}

}
