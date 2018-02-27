<?php

namespace SMW\MediaWiki;

use SMW\Connection\ConnectionProvider;
use SMW\Connection\ConnectionProviderRef;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DBConnectionProvider implements ConnectionProvider {

	use LoggerAwareTrait;

	/**
	 * @var string
	 */
	private $provider;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var array
	 */
	private $localConnectionConf = array();

	/**
	 * @var boolean
	 */
	private $resetTransactionProfiler = false;

	/**
	 * @since 3.0
	 *
	 * @param string|null $provider
	 */
	public function __construct( $provider = null ) {
		$this->provider = $provider;
	}

	/**
	 * @see #2532
	 *
	 * @param array $localConnectionConf
	 *
	 * @since 3.0
	 */
	public function setLocalConnectionConf( array $localConnectionConf ) {
		$this->localConnectionConf = $localConnectionConf;
	}

	/**
	 * @see DBConnectionProvider::getConnection
	 *
	 * @since 2.1
	 *
	 * @return Database
	 */
	public function getConnection() {

		if ( $this->connection !== null ) {
			return $this->connection;
		}

		// Default configuration
		$connectionConf = array(
			'read'  => DB_SLAVE,
			'write' => DB_MASTER
		);

		if ( isset( $this->localConnectionConf[$this->provider] ) ) {
			$connectionConf = $this->localConnectionConf[$this->provider];
		}

		return $this->connection = $this->createConnection( $connectionConf );
	}

	/**
	 * @see DBConnectionProvider::releaseConnection
	 *
	 * @since 2.1
	 */
	public function releaseConnection() {

		if ( $this->connection !== null ) {
			$this->connection->releaseConnection();
		}

		$this->connection = null;
	}

	private function createConnection( $connectionConf ) {

		if ( isset( $connectionConf['callback'] ) && is_callable( $connectionConf['callback'] ) ) {
			return call_user_func( $connectionConf['callback'] );
		}

		if ( !isset( $connectionConf['read'] ) || !isset( $connectionConf['write'] ) ) {
			throw new RuntimeException( "The configuration is incomplete (requires a read + write identifier)." );
		}

		$connectionProviders = [];

		$connectionProviders['read'] = new DBLoadBalancerConnectionProvider(
			$connectionConf['read']
		);

		if ( $connectionConf['read'] === $connectionConf['write'] ) {
			$connectionProviders['write'] = $connectionProviders['read'];
		} else {
			$connectionProviders['write'] = new DBLoadBalancerConnectionProvider(
				$connectionConf['write']
			);
		}

		$connection = new Database(
			new ConnectionProviderRef( $connectionProviders )
		);

		// Only required because of SQlite
		$connection->setDBPrefix( $GLOBALS['wgDBprefix'] );

		$context = [
			'role' => 'developer',
			'provider' => $this->provider,
			'read' => $connectionConf['read'],
			'write' => $connectionConf['write'],
		];

		$this->logger->info( "[Connection] '{provider}': [read:{read}, write:{write}]", $context );

		return $connection;
	}

}
