<?php

namespace SMW\MediaWiki\Connection;

use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\Connection\ConnectionProvider as IConnectionProvider;
use SMW\Services\ServicesFactory;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @license GPL-2.0-or-later
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
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @since 1.9
	 */
	public function __construct(
		private $id,
		private $groups = [],
		private $wiki = false,
	) {
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
	public function setLoadBalancer( ILoadBalancer $loadBalancer ): void {
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
	 * Clear both the cached connection and load balancer so the next
	 * getConnection() call acquires fresh references. This is necessary
	 * for test isolation — without clearing the load balancer, the next
	 * call would reuse a stale LB that may not have the test DB prefix.
	 *
	 * @since 1.9
	 */
	public function releaseConnection(): void {
		$this->connection = null;
		$this->loadBalancer = null;
	}

	/**
	 * @see wfGetLB
	 */
	private function initLoadBalancer( $wiki = false ) {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $wiki === false ) {
			$this->loadBalancer = $servicesFactory->create( 'DBLoadBalancer' );
			return $this->loadBalancer;
		}

		$this->loadBalancer = $servicesFactory->create( 'DBLoadBalancerFactory' )->getMainLB( $wiki );
		return $this->loadBalancer;
	}

}
