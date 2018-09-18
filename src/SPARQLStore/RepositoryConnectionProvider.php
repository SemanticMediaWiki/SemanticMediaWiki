<?php

namespace SMW\SPARQLStore;

use Onoi\HttpRequest\CurlRequest;
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
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectionProvider implements ConnectionProvider {

	/**
	 * List of supported standard connectors
	 *
	 * @var array
	 */
	private $repositoryConnectors = [
		'default'   => GenericRepositoryConnector::class,
		'generic'   => GenericRepositoryConnector::class,
		'sesame'    => GenericRepositoryConnector::class,
		'fuseki'    => FusekiRepositoryConnector::class,
		'virtuoso'  => VirtuosoRepositoryConnector::class,
		'4store'    => FourstoreRepositoryConnector::class,
	];

	/**
	 * @var RepositoryConnection
	 */
	private $connection = null;

	/**
	 * @var string|null
	 */
	private $connectorId = null;

	/**
	 * @var string|null
	 */
	private $defaultGraph = null;

	/**
	 * @var string|null
	 */
	private $queryEndpoint = null;

	/**
	 * @var string|null
	 */
	private $updateEndpoint = null;

	/**
	 * @var string|null
	 */
	private $dataEndpoint = null;

	/**
	 * @var HttpRequest
	 */
	private $httpRequest;

	/**
	 * @var boolean|integer
	 */
	private $httpVersion = false;

	/**
	 * @since 2.0
	 *
	 * @param string|null $connectorId
	 * @param string|null $defaultGraph
	 * @param string|null $queryEndpoint
	 * @param string|null $updateEndpoint
	 * @param string|null $dataEndpoint
	 */
	public function __construct( $connectorId = null, $defaultGraph = null, $queryEndpoint = null, $updateEndpoint = null, $dataEndpoint = null ) {
		$this->connectorId = $connectorId;
		$this->defaultGraph = $defaultGraph;
		$this->queryEndpoint = $queryEndpoint;
		$this->updateEndpoint = $updateEndpoint;
		$this->dataEndpoint = $dataEndpoint;

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
	 *
	 * @return HttpRequest $httpRequest
	 */
	public function setHttpRequest( HttpRequest $httpRequest ) {
		$this->httpRequest = $httpRequest;
	}

	/**
	 * @since 2.3
	 *
	 * @return integer $httpVersion
	 */
	public function setHttpVersionTo( $httpVersion ) {
		$this->httpVersion = $httpVersion;
	}

	/**
	 * @see ConnectionProvider::getConnection
	 *
	 * @since 2.0
	 *
	 * @return SparqlDatabase
	 * @throws RuntimeException
	 */
	public function getConnection() {

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
	public function releaseConnection() {
		$this->connection = null;
	}

	private function connectTo( $id ) {

		if ( $this->httpRequest === null ) {
			$this->httpRequest = new CurlRequest( curl_init() );
		}

		// https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1306
		if ( $this->httpVersion ) {
			$this->httpRequest->setOption( CURLOPT_HTTP_VERSION, $this->httpVersion );
		}

		$repositoryClient = new RepositoryClient(
			$this->defaultGraph,
			$this->queryEndpoint,
			$this->updateEndpoint,
			$this->dataEndpoint
		);

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

	private function createRepositoryConnector( $id, $repositoryClient ) {

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

		return new $repositoryConnector( $repositoryClient, $this->httpRequest );
	}

	private function isRepositoryConnection( $connection ) {
		return $connection instanceof RepositoryConnection;
	}

}
