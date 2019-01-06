<?php

namespace SMW\MediaWiki\Connection;

use DatabaseBase;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\Connection\ConnectionProvider as IConnectionProvider;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LoadBalancerConnectionProvider implements IConnectionProvider {

	use LoggerAwareTrait;

	/**
	 * @var DatabaseBase|null
	 */
	protected $connection = null;

	/**
	 * @var int|null
	 */
	protected $id = null;

	/**
	 * @var string|array
	 */
	protected $groups;

	/**
	 * @var string|boolean $wiki
	 */
	protected $wiki;

	/**
	 * @since 1.9
	 *
	 * @param int $id
	 * @param string|array $groups
	 * @param string|boolean $wiki
	 */
	public function __construct( $id, $groups = [], $wiki = false ) {
		$this->id = $id;
		$this->groups = $groups;
		$this->wiki = $wiki;
	}

	/**
	 * @see IConnectionProvider::getConnection
	 *
	 * @since 1.9
	 *
	 * @return DatabaseBase
	 * @throws RuntimeException
	 */
	public function getConnection() {

		if ( $this->connection === null ) {
			$this->connection = wfGetLB( $this->wiki )->getConnection( $this->id, $this->groups, $this->wiki );
		}

		if ( $this->connection instanceof DatabaseBase ) {
			return $this->connection;
		}

		throw new RuntimeException( 'Expected a DatabaseBase instance' );
	}

	/**
	 * @see IConnectionProvider::releaseConnection
	 *
	 * @since 1.9
	 */
	public function releaseConnection() {
		if ( $this->wiki !== false && $this->connection !== null ) {
			wfGetLB( $this->wiki )->reuseConnection( $this->connection );
		}
	}

}
