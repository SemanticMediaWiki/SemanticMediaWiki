<?php

namespace SMW\MediaWiki\Connection;

use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\Connection\ConnectionProvider as IConnectionProvider;
use SMW\Services\ServicesFactory;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LoadBalancerConnectionProvider implements IConnectionProvider {

	use LoggerAwareTrait;

	/**
	 * @var IDatabase
	 */
	private $connection;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string|array
	 */
	private $groups;

	/**
	 * @var string|boolean $wiki
	 */
	private $wiki;

	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @since 1.9
	 *
	 * @param int $id
	 * @param string|array $groups
	 * @param string|boolean $wiki Wiki ID, or false for the current wiki
	 */
	public function __construct( $id, $groups = [], $wiki = false ) {
		$this->id = $id;
		$this->groups = $groups;
		$this->wiki = $wiki;
	}

	/**
	 * @since 3.1
	 *
	 * @deprecated since 5.0
	 *
	 * @param boolean $asConnectionRef
	 */
	public function asConnectionRef( $asConnectionRef ) {
	}

	/**
	 * @since 3.1
	 *
	 * @param loadBalancer $loadBalancer
	 */
	public function setLoadBalancer( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @see IConnectionProvider::getConnection
	 *
	 * @since 1.9
	 *
	 * @return IDatabase
	 * @throws RuntimeException
	 */
	public function getConnection() {
		if ( $this->connection !== null ) {
			return $this->connection;
		}

		if ( $this->loadBalancer === null ) {
			$this->initLoadBalancer( $this->wiki );
		}

		$this->connection = $this->loadBalancer->getConnection( $this->id, $this->groups, $this->wiki );

		if ( $this->connection instanceof IDatabase ) {
			return $this->connection;
		}

		throw new RuntimeException( 'Expected a IDatabase instance!' );
	}

	/**
	 * @see IConnectionProvider::releaseConnection
	 *
	 * @since 1.9
	 */
	public function releaseConnection() {
		if ( $this->loadBalancer !== null && $this->connection !== null ) {
			$this->loadBalancer->reuseConnection( $this->connection );
		}
	}

	/**
	 * @see wfGetLB
	 */
	private function initLoadBalancer( $wiki = false ) {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $wiki === false ) {
			return $this->loadBalancer = $servicesFactory->create( 'DBLoadBalancer' );
		}

		return $this->loadBalancer = $servicesFactory->create( 'DBLoadBalancerFactory' )->getMainLB( $wiki );
	}

}
