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
	 * @var Options
	 */
	private $options;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var ElasticClient
	 */
	private $connection;

	/**
	 * @since 3.0
	 *
	 * @param Options $options
	 * @param Cache $cache
	 */
	public function __construct( Options $options, Cache $cache ) {
		$this->options = $options;
		$this->cache = $cache;
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

		if ( $this->hasClientBuilder() ) {
			$this->connection = new Client(
				ClientBuilder::fromConfig( $params, true ),
				$this->cache,
				$this->options
			);
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

	private function hasClientBuilder() {

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
