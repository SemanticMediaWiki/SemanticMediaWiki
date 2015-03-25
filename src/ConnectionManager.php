<?php

namespace SMW;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConnectionManager {

	/**
	 * By design this variable is static to ensure that ConnectionProvider
	 * instances are only intialized once per request.
	 *
	 * @var array
	 */
	private static $connectionProviderTypeIdMap = array();

	/**
	 * @since 2.1
	 *
	 * @param string|null $connectionTypeId
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function getConnection( $connectionTypeId = null ) {
		return $this->getConnectionProviderForId( strtolower( $connectionTypeId ) )->getConnection();
	}

	/**
	 * @since 2.1
	 *
	 * @return ConnectionManager
	 */
	public function releaseConnections() {

		foreach ( self::$connectionProviderTypeIdMap as $connectionProvider ) {
			$connectionProvider->releaseConnection();
		}

		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $connectionTypeId
	 * @param DBConnectionProvider $connectionProvider
	 */
	public function registerConnectionProvider( $connectionTypeId, DBConnectionProvider $connectionProvider ) {
		self::$connectionProviderTypeIdMap[strtolower( $connectionTypeId )] = $connectionProvider;
	}

	private function getConnectionProviderForId( $connectionTypeId ) {

		if ( isset( self::$connectionProviderTypeIdMap[$connectionTypeId] ) ) {
			return self::$connectionProviderTypeIdMap[$connectionTypeId];
		}

		throw new RuntimeException( "{$connectionTypeId} is missing a registered connection provider" );
	}

}
