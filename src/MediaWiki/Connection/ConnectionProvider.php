<?php

namespace SMW\MediaWiki\Connection;

use Profiler;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\Connection\ConnectionProvider as IConnectionProvider;
use SMW\Connection\ConnRef;
use SMW\Services\ServicesFactory;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ConnectionProvider implements IConnectionProvider {

	use LoggerAwareTrait;

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
	 */
	public function __construct( private $provider = null ) {
	}

	/**
	 * @see #2532
	 *
	 * @param array $localConnectionConf
	 *
	 * @since 3.0
	 */
	public function setLocalConnectionConf( array $localConnectionConf ): void {
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
			'read'  => DB_REPLICA,
			'write' => DB_PRIMARY
		];

		if ( isset( $this->localConnectionConf[$this->provider] ) ) {
			$conf = $this->localConnectionConf[$this->provider];
		}

		$this->connection = $this->createConnection( $conf );
		return $this->connection;
	}

	/**
	 * @see IConnectionProvider::releaseConnection
	 *
	 * @since 2.1
	 */
	public function releaseConnection(): void {
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

		$connection = new Database(
			$this->newConnRef( $conf ),
			$this->newTransactionHandler()
		);

		$this->logger->info(
			[
				'Connection',
				'{provider}: {conf}',
			],
			[
				'role' => 'developer',
				'provider' => $this->provider,
				'conf' => [
					'read'  => $conf['read'] === DB_REPLICA ? 'DB_REPLICA' : 'DB_PRIMARY',
					'write' => $conf['write'] === DB_REPLICA ? 'DB_REPLICA' : 'DB_PRIMARY',
				]
			]
		);

		return $connection;
	}

	private function newConnRef( $conf ): ConnRef {
		$read = $this->newLoadBalancerConnectionProvider( $conf['read'] );

		if ( $conf['read'] !== $conf['write'] ) {
			$write = $this->newLoadBalancerConnectionProvider( $conf['write'] );
		} else {
			$write = $read;
		}

		return new ConnRef(
			[
				'read'  => $read,
				'write' => $write
			]
		);
	}

	private function newLoadBalancerConnectionProvider( $id ): LoadBalancerConnectionProvider {
		return new LoadBalancerConnectionProvider( $id );
	}

	private function newTransactionHandler(): TransactionHandler {
		$transactionHandler = new TransactionHandler(
			ServicesFactory::getInstance()->create( 'DBLoadBalancerFactory' )
		);

		$transactionHandler->setTransactionProfiler(
			Profiler::instance()->getTransactionProfiler()
		);

		return $transactionHandler;
	}

}
