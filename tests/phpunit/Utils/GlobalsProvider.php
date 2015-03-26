<?php

namespace SMW\Tests\Utils;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
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
	 * @var array
	 */
	private $container = null;

	/**
	 * @since 1.9.1
	 *
	 * @return GlobalsProvider
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance->setContainer( $GLOBALS );
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

		if ( is_string( $key ) && $this->contains( $key ) ) {
			return $this->lookup( $key );
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
			$this->container[$key] = $value;
		}

		return $this;
	}

	protected function setContainer( $container ) {
		$this->container = $container;
		return $this;
	}

	protected function contains( $key ) {
		return isset( $this->container[$key] ) || array_key_exists( $key, $this->container );
	}

	protected function lookup( $key ) {
		return $this->container[$key];
	}

}
