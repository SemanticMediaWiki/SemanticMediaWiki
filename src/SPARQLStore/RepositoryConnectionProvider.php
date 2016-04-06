<?php

namespace SMW\SPARQLStore;

use Onoi\HttpRequest\CurlRequest;
use RuntimeException;
use SMW\DBConnectionProvider;

/**
 * Provides an one-stop solution for creating a valid instance for a
 * RepositoryConnection using available settings
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectionProvider implements DBConnectionProvider {

	/**
	 * List of supported standard connectors
	 *
	 * @var array
	 */
	private $connectorIdToClass = array(
		'default'   => 'SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector',
		'generic'   => 'SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector',
		'sesame'    => 'SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector',
		'fuseki'    => 'SMW\SPARQLStore\RepositoryConnector\FusekiHttpRepositoryConnector',
		'virtuoso'  => 'SMW\SPARQLStore\RepositoryConnector\VirtuosoHttpRepositoryConnector',
		'4store'    => 'SMW\SPARQLStore\RepositoryConnector\FourstoreHttpRepositoryConnector',
	);

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
			$this->connectorId = $GLOBALS['smwgSparqlDatabaseConnector'];
		}

		if ( $this->defaultGraph === null ) {
			$this->defaultGraph = $GLOBALS['smwgSparqlDefaultGraph'];
		}

		if ( $this->queryEndpoint === null ) {
			$this->queryEndpoint = $GLOBALS['smwgSparqlQueryEndpoint'];
		}

		if ( $this->updateEndpoint === null ) {
			$this->updateEndpoint = $GLOBALS['smwgSparqlUpdateEndpoint'];
		}

		if ( $this->dataEndpoint === null ) {
			$this->dataEndpoint = $GLOBALS['smwgSparqlDataEndpoint'];
		}
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
	 * @see DBConnectionProvider::getConnection
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
	 * @see DBConnectionProvider::releaseConnection
	 *
	 * @since 2.0
	 */
	public function releaseConnection() {
		$this->connection = null;
	}

	private function connectTo( $connectorId ) {

		$repositoryConnector = $this->mapConnectorIdToClass( $connectorId );

		$curlRequest = new CurlRequest( curl_init() );

		// https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1306
		if ( $this->httpVersion ) {
			$curlRequest->setOption( CURLOPT_HTTP_VERSION, $this->httpVersion );
		}

		$connection = new $repositoryConnector(
			new RepositoryClient( $this->defaultGraph, $this->queryEndpoint, $this->updateEndpoint, $this->dataEndpoint ),
			$curlRequest
		);

		if ( $this->isRepositoryConnection( $connection ) ) {
			return $connection;
		}

		throw new RuntimeException( 'Expected a RepositoryConnection instance' );
	}

	private function mapConnectorIdToClass( $connectorId ) {

		$databaseConnector = $this->connectorIdToClass['default'];

		if ( isset( $this->connectorIdToClass[$connectorId] ) ) {
			$databaseConnector = $this->connectorIdToClass[$connectorId];
		}

		if ( $connectorId === 'custom' ) {
			$databaseConnector = $GLOBALS['smwgSparqlDatabase'];
		}

		if ( !class_exists( $databaseConnector ) ) {
			throw new RuntimeException( "{$databaseConnector} is not available" );
		}

		return $databaseConnector;
	}

	private function isRepositoryConnection( $connection ) {
		return $connection instanceof RepositoryConnection;
	}

}
