<?php

namespace SMW\Elastic\Connection;

use Elasticsearch\ClientBuilder;
use SMW\Elastic\Exception\ClientBuilderNotFoundException;
use SMW\ApplicationFactory;
use SMW\Connection\ConnectionProvider as IConnectionProvider;
use SMW\Options;
use Psr\Log\LoggerAwareTrait;
use Onoi\Cache\Cache;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConnectionProvider implements IConnectionProvider {

	use LoggerAwareTrait;

	/**
	 * @var Cache
	 */
	private $lockManager;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var ElasticClient
	 */
	private $connection;

	/**
	 * @since 3.0
	 *
	 * @param LockManager $lockManager
	 * @param Options $options
	 */
	public function __construct( LockManager $lockManager, Options $options ) {
		$this->lockManager = $lockManager;
		$this->options = $options;
	}

	/**
	 * @see ConnectionProvider::getConnection
	 *
	 * @since 3.0
	 *
	 * @return Connection
	 */
	public function getConnection() {

		if ( $this->connection !== null ) {
			return $this->connection;
		}

		$params = [
			'hosts' => $this->options->get( 'endpoints' ),
			'retries' => $this->options->dotGet( 'connection.retries', 1 ),

			'client' => [

				// controls the request timeout
				'timeout' => $this->options->dotGet( 'connection.timeout', 30 ),

				// controls the original connection timeout duration
				'connect_timeout' => $this->options->dotGet( 'connection.connect_timeout', 30 )
			]

			// Use `singleHandler` if you know you will never need async capabilities,
			// since it will save a small amount of overhead by reducing indirection
			// 'handler' => ClientBuilder::singleHandler()
		];

		if ( $this->hasAvailableClientBuilder() ) {
			$this->connection = $this->newClient( ClientBuilder::fromConfig( $params, true ) );
		} else {
			$this->connection = new DummyClient();
		}

		$this->connection->setLogger(
			$this->logger
		);

		$this->logger->info(
			[
				'Connection',
				'{provider} : {hosts}'
			],
			[
				'role' => 'developer',
				'provider' => 'elastic',
				'hosts' => $params['hosts']
			]
		);

		return $this->connection;
	}

	/**
	 * @see ConnectionProvider::releaseConnection
	 *
	 * @since 3.0
	 */
	public function releaseConnection() {
		$this->connection = null;
	}

	private function newClient( $clientBuilder ) {
		return new Client( $clientBuilder, $this->lockManager, $this->options );
	}

	private function hasAvailableClientBuilder() {

		if ( $this->options->dotGet( 'is.elasticstore', false ) === false ) {
			return false;
		}

		// Fail hard because someone selected the ElasticStore but forgot to install
		// the elastic interface!
		if ( !class_exists( '\Elasticsearch\ClientBuilder' ) ) {
			throw new ClientBuilderNotFoundException();
		}

		return true;
	}

}
