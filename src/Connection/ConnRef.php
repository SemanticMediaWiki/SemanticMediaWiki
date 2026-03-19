<?php

namespace SMW\Connection;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ConnRef {

	/**
	 * @var array
	 */
	private $connections = [];

	/**
	 * @since 3.0
	 */
	public function __construct( private array $connectionProviders ) {
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function hasConnection( $key ): bool {
		return isset( $this->connectionProviders[$key] );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return ConnectionProvider
	 * @throws RuntimeException
	 */
	public function getConnection( $key ) {
		if ( isset( $this->connections[$key] ) ) {
			return $this->connections[$key];
		}

		if ( isset( $this->connectionProviders[$key] ) && $this->connectionProviders[$key] instanceof ConnectionProvider ) {
			$this->connections[$key] = $this->connectionProviders[$key]->getConnection();
			return $this->connections[$key];
		}

		throw new RuntimeException( "$key is unknown" );
	}

	/**
	 * @since 3.0
	 */
	public function releaseConnections(): void {
		$this->connections = [];

		foreach ( $this->connectionProviders as $connectionProvider ) {
			$connectionProvider->releaseConnection();
		}
	}

}
