<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TestConfig {

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var array
	 */
	private $configurations = [];

	/**
	 * @var array
	 */
	private $newKeys = [];

	/**
	 * @since 3.0
	 */
	public function __construct() {
		$this->settings = ApplicationFactory::getInstance()->getSettings();
	}

	/**
	 * @since 3.0
	 *
	 * @param array $configurations
	 */
	public function set( array $configurations = [] ) {

		foreach ( $configurations as $key => $value ) {

			if ( array_key_exists( $key, $GLOBALS ) ) {
				$this->configurations[$key] = $GLOBALS[$key];
			} else {
				$this->newKeys[] = $key;
			}

			$GLOBALS[$key] = $value;
			$this->settings->set( $key, $value );
		}
	}

	/**
	 * @since 3.0
	 */
	public function reset() {

		foreach ( $this->configurations as $key => $value ) {
			$GLOBALS[$key] = $value;
			$this->settings->set( $key, $value );
		}

		foreach ( $this->newKeys as $key ) {
			unset( $GLOBALS[$key] );
			$this->settings->delete( $key );
		}
	}

}
