<?php

namespace SMW\MediaWiki\Connection;

use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\Connection\ConnectionProvider as IConnectionProvider;
use SMW\Connection\ConnRef;
use SMW\Services\ServicesFactory;

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
			'read'  => DB_REPLICA,
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
					'read'  => $conf['read'] === DB_REPLICA ? 'DB_REPLICA' : 'DB_MASTER',
					'write' => $conf['write'] === DB_REPLICA ? 'DB_REPLICA' : 'DB_MASTER',
				]
			]
		);

		return $connection;
	}

	private function newConnRef( $conf ) {

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

	private function newLoadBalancerConnectionProvider( $id ) {
		return new LoadBalancerConnectionProvider( $id );
	}

	private function newTransactionHandler() {

		$transactionHandler = new TransactionHandler(
			ServicesFactory::getInstance()->create( 'DBLoadBalancerFactory' )
		);

		$transactionHandler->setTransactionProfiler(
			\Profiler::instance()->getTransactionProfiler()
		);

		return $transactionHandler;
	}

}
