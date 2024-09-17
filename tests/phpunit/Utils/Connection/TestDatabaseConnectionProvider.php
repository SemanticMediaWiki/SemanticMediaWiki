<?php

namespace SMW\Tests\Utils\Connection;

use SMW\Services\ServicesFactory;
use SMW\Connection\ConnectionProvider;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class TestDatabaseConnectionProvider implements ConnectionProvider {

	protected $id;

	/**
	 * @since 2.0
	 *
	 * @param int $id
	 */
	public function __construct( $id = DB_PRIMARY ) {
		$this->id = $id;
	}

	/**
	 * @since  2.0
	 *
	 * @return IDatabase
	 */
	public function getConnection() {
		$lb = $this->getLoadBalancer();

		// MW 1.39+
		if ( method_exists( $lb, 'getConnectionInternal' ) ) {
			return $lb->getConnectionInternal( $this->id );
		}

		return $lb->getConnection( $this->id );
	}

	public function releaseConnection() {
	}

	private function getLoadBalancer(): ILoadBalancer {
		return ServicesFactory::getInstance()->create( 'DBLoadBalancer' );
	}

}
