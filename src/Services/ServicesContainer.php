<?php

namespace SMW\Services;

use RuntimeException;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ServicesContainer {

	/**
	 * @var callable[]
	 */
	private $services;

	/**
	 * @since 3.0
	 */
	public function __construct( array $services = [] ) {
		$this->services = $services;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key, ...$args ) {

		if ( !isset( $this->services[$key] ) ) {
			throw new RuntimeException( "$key is an unknown service!" );
		};

		if ( !is_callable( $this->services[$key] ) ) {
			throw new RuntimeException( "$key is not a callable service!" );
		};

		return $this->services[$key]( ...$args );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param  callable $service
	 */
	public function add( $key, callable $service ) {
		$this->services[$key] = $service;
	}

}
