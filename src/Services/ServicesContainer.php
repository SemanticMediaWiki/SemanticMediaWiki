<?php

namespace SMW\Services;

use RuntimeException;
use SMW\Services\Exception\ServiceNotFoundException;

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
			throw new ServiceNotFoundException( "$key is an unknown service!" );
		};

		$type = null;
		$service = $this->services[$key];

		if ( !is_callable( $service ) && isset( $service['_type'] ) && isset( $service['_service'] ) ) {
			$type = $service['_type'];
			$service = $service['_service'];
		}

		if ( !is_callable( $service ) ) {
			throw new RuntimeException( "$key is not a callable service!" );
		};

		$instance = $service( ...$args );

		if ( $type !== null && !is_a( $instance, $type ) ) {
			throw new RuntimeException( "Service $key is not of the expected $type type!" );
		}

		return $instance;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param callable $service
	 */
	public function add( $key, callable $service ) {
		$this->services[$key] = $service;
	}

}
