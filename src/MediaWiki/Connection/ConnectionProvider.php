<?php

namespace SMW\MediaWiki\Connection;

use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\Connection\ConnectionProvider as IConnectionProvider;
use SMW\Connection\ConnRef;
use SMW\ApplicationFactory;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConnectionProvider implements IConnectionProvider {

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
	private $localConnectionConf = [];

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
	 * @see IConnectionProvider::getConnection
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
		$conf = [
			'read'  => DB_SLAVE,
			'write' => DB_MASTER
		];

		if ( isset( $this->localConnectionConf[$this->provider] ) ) {
			$conf = $this->localConnectionConf[$this->provider];
		}

		return $this->connection = $this->createConnection( $conf );
	}

	/**
	 * @see IConnectionProvider::releaseConnection
	 *
	 * @since 2.1
	 */
	public function releaseConnection() {

		if ( $this->connection !== null ) {
			$this->connection->releaseConnection();
		}

		$this->connection = null;
	}

	private function createConnection( $conf ) {

		if ( isset( $conf['callback'] ) && is_callable( $conf['callback'] ) ) {
			return call_user_func( $conf['callback'] );
		}

		if ( !isset( $conf['read'] ) || !isset( $conf['write'] ) ) {
			throw new RuntimeException( "The configuration is incomplete (requires a `read` and `write` identifier)." );
		}

		$connectionProviders = [];

		$connectionProviders['read'] = new LoadBalancerConnectionProvider(
			$conf['read']
		);

		if ( $conf['read'] === $conf['write'] ) {
			$connectionProviders['write'] = $connectionProviders['read'];
		} else {
			$connectionProviders['write'] = new LoadBalancerConnectionProvider(
				$conf['write']
			);
		}

		$transactionProfiler = new TransactionProfiler(
			\Profiler::instance()->getTransactionProfiler()
		);

		$transactionProfiler->silenceTransactionProfiler();

		$connection = new Database(
			new ConnRef( $connectionProviders ),
			ApplicationFactory::getInstance()->create( 'DBLoadBalancerFactory' )
		);

		$connection->setTransactionProfiler(
			$transactionProfiler
		);

		// Only required because of SQlite
		$connection->setDBPrefix( $GLOBALS['wgDBprefix'] );

		$this->logger->info(
			[
				'Connection',
				'{provider}: {conf}',
			],
			[
				'role' => 'developer',
				'provider' => $this->provider,
				'conf' => [
					'read'  => $conf['read'] === DB_SLAVE ? 'DB_SLAVE' : 'DB_MASTER',
					'write' => $conf['write'] === DB_SLAVE ? 'DB_SLAVE' : 'DB_MASTER',
				]
			]
		);

		return $connection;
	}

}
