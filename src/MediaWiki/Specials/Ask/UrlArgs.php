<?php

namespace SMW\MediaWiki\Specials\Ask;

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
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		return isset( $this->args[$key] ) ? $this->args[$key] : null;
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
		return  $this->args;
	}

	/**
	 * @see __toString
	 */
	public function __toString() {
		return wfArrayToCGI( $this->args ) . ( $this->fragment !== '' ? '#' . $this->fragment : '' );
	}

}
