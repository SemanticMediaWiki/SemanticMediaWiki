<?php

namespace SMW\Tests\Utils;

use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 * @since 1.9.1
 *
 * @author mwjames
 */
class GlobalsProvider {

	/**
	 * @var GlobalsProvider
	 */
	private static $instance = null;

	/**
	 * @since 1.9.1
	 *
	 * @return GlobalsProvider
	 */
	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @since 1.9.1
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 1.9.1
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		if ( isset( $GLOBALS[$key ] ) ) {
			return $GLOBALS[$key];
		}

		throw new InvalidArgumentException( 'Configuration key is unkown' );
	}

	/**
	 * @since 2.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		if ( is_string( $key ) ) {
			$GLOBALS[$key] = $value;
		}

		return $this;
	}

}
