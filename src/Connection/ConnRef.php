<?php

namespace SMW\Connection;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConnRef {

	/**
	 * @var array
	 */
	private $connectionProviders = [];

	/**
	 * @var array
	 */
	private $connections = [];

	/**
	 * @since 3.0
	 *
	 * @param array $connectionProviders
	 */
	public function __construct( array $connectionProviders ) {
		$this->connectionProviders = $connectionProviders;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function hasConnection( $key ) {
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
			return $this->connections[$key] = $this->connectionProviders[$key]->getConnection();
		}

		throw new RuntimeException( "$key is unknown" );
	}

	/**
	 * @since 3.0
	 */
	public function releaseConnections() {
		$this->connections = [];

		foreach ( $this->connectionProviders as $connectionProvider ) {
			$connectionProvider->releaseConnection();
		}
	}

}
