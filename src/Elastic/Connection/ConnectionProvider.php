<?php

namespace SMW\Elastic\Connection;

use Elasticsearch\ClientBuilder;
use SMW\Connection\ConnectionProvider as IConnectionProvider;
use SMW\Elastic\Exception\InvalidJSONException;
use RuntimeException;
use SMW\ApplicationFactory;
use SMW\Options;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConnectionProvider implements IConnectionProvider {

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
	 * @param Options $options
	 */
	public function __construct( Options $options = null ) {
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

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$options = new Options(
			$settings->get( 'smwgElasticsearchConfig' )
		);

		$options->set(
			'elastic.enabled',
			strpos( $settings->get( 'smwgDefaultStore' ), 'Elastic' ) !== false
		);

		$options->set(
			'endpoints',
			$settings->get( 'smwgElasticsearchEndpoints' )
		);

		if ( ( $contents = $this->readFile( $settings->get( 'smwgElasticsearchProfile' ) ) ) ) {
			$options->loadFromJSON( $contents, true );
		}

		$params = [
			'hosts' => $options->get( 'endpoints' ),
			'retries' => $options->dotGet( 'connection.retries', 1 ),

		// Use `singleHandler` if you know you will never need async capabilities,
		// since it will save a small amount of overhead by reducing indirection
		//	'handler' => ClientBuilder::singleHandler()
		];

		if ( class_exists( '\Elasticsearch\ClientBuilder' ) ) {
			$this->connection = new Client(
				ClientBuilder::fromConfig( $params, true ),
				$applicationFactory->getCache(),
				$options
			);
		} else {
			$this->connection = new DummyClient();
		}

		$this->connection->setLogger(
			$applicationFactory->getMediaWikiLogger( 'smw-elastic' )
		);


		$logger = $applicationFactory->getMediaWikiLogger( 'smw' );

		$context = [
			'role' => 'developer',
			'provider' => 'elastic',
			'hosts' => json_encode( $params['hosts'] )
		];

		$logger->info( "[Connection] '{provider}': {hosts}", $context );

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

	private function readFile( $file ) {

		if ( $file === false ) {
			return false;
		}

		$file = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, realpath( $file ) );

		if ( is_readable( $file ) ) {
			return file_get_contents( $file );
		}

		throw new RuntimeException( "$file is inaccessible!" );
	}

}
