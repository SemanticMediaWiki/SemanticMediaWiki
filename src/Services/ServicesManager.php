<?php

namespace SMW\Services;

use RuntimeException;
use SMW\Services\Exception\ServiceNotFoundException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ServicesManager {

	/**
	 * @var array
	 */
	private $services = [];

	/**
	 * @since 3.0
	 *
	 * @param array $services
	 */
	public static function newFromArray( array $services ) {

		$servicesManager = new ServicesManager();

		foreach ( $services as $serviceName => $callback ) {
			if ( is_callable( $callback ) ) {
				$servicesManager->registerCallback( $serviceName, $callback );
			}
		}

		return $servicesManager;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $serviceName
	 *
	 * @return boolean
	 */
	public function has( $serviceName ) {
		return isset( $this->services[strtolower($serviceName)] );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $serviceName
	 *
	 * @return mixed
	 * @throws ServiceNotFoundException
	 */
	public function get( $serviceName ) {

		if ( !isset( $this->services[strtolower($serviceName)] ) ) {
			throw new ServiceNotFoundException( "$serviceName is an unknown service. Registered services only include: `"  . implode( ', ', array_keys( $this->services ) ) . '`' );
		}

		$parameters = func_get_args();
		array_shift( $parameters );

		return call_user_func_array( $this->services[strtolower($serviceName)], $parameters );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $serviceName
	 * @param callable $callback
	 */
	public function registerCallback( $serviceName, callable $callback ) {
		$this->services[strtolower($serviceName)] = $callback;
	}

	/**
	 * @since 3.0
	 *
	 * @return callable
	 */
	public function returnCallback() {
		return function( $serviceName ) { return $this->get( $serviceName ); };
	}

}
