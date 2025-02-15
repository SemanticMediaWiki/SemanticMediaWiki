<?php

namespace SMW\Tests\Utils\Connection;

use SMW\Connection\ConnectionProvider;
use SMW\Services\ServicesFactory;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GPL-2.0-or-later
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
		return $lb->getConnectionInternal( $this->id );
	}

	public function releaseConnection() {
	}

	private function getLoadBalancer(): ILoadBalancer {
		return ServicesFactory::getInstance()->create( 'DBLoadBalancer' );
	}

}
