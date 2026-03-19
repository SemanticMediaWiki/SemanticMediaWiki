<?php

namespace SMW\SPARQLStore;

use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use RuntimeException;
use SMW\Connection\ConnectionProvider;
use SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector;

/**
 * @private
 *
 * Provides a RepositoryConnection on the available settings.
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectionProvider implements ConnectionProvider {

	/**
	 * List of supported standard connectors
	 */
	private array $repositoryConnectors = [
		'default'   => GenericRepositoryConnector::class,
		'generic'   => GenericRepositoryConnector::class,
		'sesame'    => GenericRepositoryConnector::class,
		'fuseki'    => FusekiRepositoryConnector::class,
		'virtuoso'  => VirtuosoRepositoryConnector::class,
		'4store'    => FourstoreRepositoryConnector::class,
	];

	private ?RepositoryConnection $connection = null;

	private ?HttpRequestFactory $httpRequestFactory = null;

	private int $featureSet = 0;

	/**
	 * @since 2.0
	 */
	public function __construct(
		private ?string $connectorId = null,
		private ?string $defaultGraph = null,
		private ?string $queryEndpoint = null,
		private ?string $updateEndpoint = null,
		private ?string $dataEndpoint = null,
	) {
		if ( $this->connectorId === null ) {
			$this->connectorId = $GLOBALS['smwgSparqlRepositoryConnector'];
		}

		if ( $this->defaultGraph === null ) {
			$this->defaultGraph = $GLOBALS['smwgSparqlDefaultGraph'];
		}

		if ( $this->queryEndpoint === null ) {
			$this->queryEndpoint = $GLOBALS['smwgSparqlEndpoint']['query'];
		}

		if ( $this->updateEndpoint === null ) {
			$this->updateEndpoint = $GLOBALS['smwgSparqlEndpoint']['update'];
		}

		if ( $this->dataEndpoint === null ) {
			$this->dataEndpoint = $GLOBALS['smwgSparqlEndpoint']['data'];
		}
	}

	/**
	 * @since 3.0
	 */
	public function setHttpRequestFactory( HttpRequestFactory $httpRequestFactory ): void {
		$this->httpRequestFactory = $httpRequestFactory;
	}

	/**
	 * @since 3.2
	 */
	public function setFeatureSet( int $featureSet ): void {
		$this->featureSet = $featureSet;
	}

	/**
	 * @see ConnectionProvider::getConnection
	 *
	 * @since 2.0
	 *
	 * @return SparqlDatabase
	 * @throws RuntimeException
	 */
	public function getConnection(): RepositoryConnection {
		if ( $this->connection === null ) {
			$this->connection = $this->connectTo( strtolower( $this->connectorId ) );
		}

		return $this->connection;
	}

	/**
	 * @see ConnectionProvider::releaseConnection
	 *
	 * @since 2.0
	 */
	public function releaseConnection(): void {
		$this->connection = null;
	}

	private function connectTo( string $id ): RepositoryConnection {
		if ( $this->httpRequestFactory === null ) {
			$this->httpRequestFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
		}

		$repositoryClient = new RepositoryClient(
			$this->defaultGraph,
			$this->queryEndpoint,
			$this->updateEndpoint,
			$this->dataEndpoint
		);

		$repositoryClient->setFeatureSet( $this->featureSet );
		$repositoryClient->setName( $id );

		$repositoryConnector = $this->createRepositoryConnector(
			$id,
			$repositoryClient
		);

		if ( $this->isRepositoryConnection( $repositoryConnector ) ) {
			return $repositoryConnector;
		}

		throw new RuntimeException( 'Expected a RepositoryConnection instance' );
	}

	private function createRepositoryConnector( string $id, RepositoryClient $repositoryClient ): object {
		$repositoryConnector = $this->repositoryConnectors['default'];

		if ( isset( $this->repositoryConnectors[$id] ) ) {
			$repositoryConnector = $this->repositoryConnectors[$id];
		}

		if ( $id === 'custom' ) {
			$repositoryConnector = $GLOBALS['smwgSparqlCustomConnector'];
		}

		if ( !class_exists( $repositoryConnector ) ) {
			throw new RuntimeException( "{$repositoryConnector} is not available" );
		}

		return new $repositoryConnector( $repositoryClient, $this->httpRequestFactory );
	}

	private function isRepositoryConnection( object $connection ): bool {
		return $connection instanceof RepositoryConnection;
	}

}
