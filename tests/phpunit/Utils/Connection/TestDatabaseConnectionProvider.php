<?php

namespace SMW\Tests\Utils\Connection;

use DatabaseBase;
use SMW\Services\ServicesFactory;
use SMW\Connection\ConnectionProvider;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class TestDatabaseConnectionProvider implements ConnectionProvider {

	/**
	 * @var DatabaseBase
	 */
	protected $connection;

	protected $id;

	/**
	 * @since 2.0
	 *
	 * @param int $id
	 */
	public function __construct( $id = DB_MASTER ) {
		$this->id = $id;
	}

	/**
	 * @since  2.0
	 *
	 * @return DatabaseBase
	 */
	public function getConnection() {

		if ( $this->connection !== null ) {
			return $this->connection;
		}

		$loadBalancer = $this->getLoadBalancer();

		// MW 1.39
		if ( method_exists( $loadBalancer, 'getConnectionInternal' ) ) {
			return $this->connection = $loadBalancer->getConnectionInternal( $this->id );
		}

		return $this->connection = $loadBalancer->getConnection( $this->id );
	}

	public function releaseConnection() {
	}

	private function getLoadBalancer() {
		return ServicesFactory::getInstance()->create( 'DBLoadBalancer' );
	}

}
