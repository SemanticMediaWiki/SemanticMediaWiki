<?php

namespace SMW\Tests\Utils\Connection;

use DatabaseBase;
use SMW\Services\ServicesFactory;
use SMW\Connection\ConnectionProvider as IConnectionProvider;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ConnectionProvider implements IConnectionProvider {

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

		return $this->connection = $this->getLoadBalancer()->getConnection( $this->id );
	}

	public function releaseConnection() {
	}

	private function getLoadBalancer() {
		return ServicesFactory::getInstance()->create( 'DBLoadBalancer' );
	}

}
