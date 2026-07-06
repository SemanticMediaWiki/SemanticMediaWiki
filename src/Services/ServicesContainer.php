<?php

namespace SMW\Services;

use RuntimeException;
use SMW\Services\Exception\ServiceNotFoundException;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ServicesContainer {

	/**
	 * Cache of singleton instances keyed by a hash of (service key + args).
	 *
	 * @var array<string, mixed>
	 */
	private array $singletons = [];

	/**
	 * @since 3.0
	 */
	public function __construct( private array $services = [] ) {
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
		}

		$type = null;
		$service = $this->services[$key];

		if ( !is_callable( $service ) && isset( $service['_type'] ) && isset( $service['_service'] ) ) {
			$type = $service['_type'];
			$service = $service['_service'];
		}

		if ( !is_callable( $service ) ) {
			throw new RuntimeException( "$key is not a callable service!" );
		}

		$instance = $service( ...$args );

		if ( $type !== null && !is_a( $instance, $type ) ) {
			throw new RuntimeException( "Service $key is not of the expected $type type!" );
		}

		return $instance;
	}

	/**
	 * Returns a singleton instance for the given key and args. Repeated calls
	 * with the same key and identical args return the same object.
	 *
	 * @since 7.0.0
	 *
	 * @param string $key
	 */
	public function singleton( string $key, ...$args ) {
		if ( !$this->isRegistered( $key ) ) {
			throw new ServiceNotFoundException( "$key is an unknown service!" );
		}

		$cacheKey = $this->makeCacheKey( $key, $args );

		if ( !array_key_exists( $cacheKey, $this->singletons ) ) {
			$this->singletons[$cacheKey] = $this->get( $key, ...$args );
		}

		return $this->singletons[$cacheKey];
	}

	/**
	 * Creates and returns a fresh instance for the given key and args. Never
	 * returns a cached instance.
	 *
	 * @since 7.0.0
	 *
	 * @param string $key
	 */
	public function create( string $key, ...$args ) {
		if ( !$this->isRegistered( $key ) ) {
			throw new ServiceNotFoundException( "$key is an unknown service!" );
		}

		return $this->get( $key, ...$args );
	}

	/**
	 * Returns true when a service callable has been registered for the given key.
	 *
	 * @since 7.0.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function isRegistered( string $key ): bool {
		return isset( $this->services[$key] );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param callable $service
	 */
	public function add( $key, callable $service ): void {
		$this->services[$key] = $service;
	}

	/**
	 * Builds a stable cache key from the service key and the args.
	 *
	 * Objects are identified by their runtime object id rather than serialized,
	 * so non-serializable args (for example a container holding closures) can be
	 * passed to a singleton service.
	 */
	private function makeCacheKey( string $key, array $args ): string {
		$parts = [];

		foreach ( $args as $arg ) {
			if ( is_object( $arg ) ) {
				$parts[] = 'o:' . spl_object_id( $arg );
			} else {
				$parts[] = 's:' . serialize( $arg );
			}
		}

		return $key . ':' . md5( implode( '|', $parts ) );
	}

}
