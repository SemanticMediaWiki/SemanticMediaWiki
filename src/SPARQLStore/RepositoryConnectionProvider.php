<?php

namespace SMW\SPARQLStore;

use RuntimeException;
use SMW\DBConnectionProvider;
use SMW\CurlRequest;
use SMW\SPARQLStore\RepositoryConnection;

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
		'default'   => 'SMW\SPARQLStore\Connector\GenericHttpRepositoryConnector',
		'generic'   => 'SMW\SPARQLStore\Connector\GenericHttpRepositoryConnector',
		'sesame'    => 'SMW\SPARQLStore\Connector\GenericHttpRepositoryConnector',
		'fuseki'    => 'SMW\SPARQLStore\Connector\FusekiHttpRepositoryConnector',
		'virtuoso'  => 'SMW\SPARQLStore\Connector\VirtuosoHttpRepositoryConnector',
		'4store'    => 'SMW\SPARQLStore\Connector\FourstoreHttpRepositoryConnector',
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

			if ( $this->connectorId === null ) {
				$this->connectorId = $GLOBALS['smwgSparqlDatabaseConnector'];
			}

			$this->defaultGraph = $defaultGraph;
			$this->queryEndpoint = $queryEndpoint;
			$this->updateEndpoint = $updateEndpoint;
			$this->dataEndpoint = $dataEndpoint;
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

		$connection = new $repositoryConnector(
			new RepositoryClient( $this->defaultGraph, $this->queryEndpoint, $this->updateEndpoint, $this->dataEndpoint ),
			new CurlRequest( curl_init() )
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
