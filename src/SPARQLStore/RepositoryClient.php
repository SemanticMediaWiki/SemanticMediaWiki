<?php

namespace SMW\SPARQLStore;

/**
 * Provides information about the client and how to communicate with
 * its services
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class RepositoryClient {

	/**
	 * The URI of the default graph that is used to store data.
	 * Can be the empty string to omit this information in all requests
	 * (not supported by all stores).
	 *
	 * @var string
	 */
	private $defaultGraph = '';

	/**
	 * The URL of the endpoint for executing read queries.
	 *
	 * @var string
	 */
	private $queryEndpoint = '';

	/**
	 * The URL of the endpoint for executing update queries, or empty if
	 * update is not allowed/supported.
	 *
	 * @var string
	 */
	private $updateEndpoint = '';

	/**
	 * The URL of the endpoint for using the SPARQL Graph Store HTTP
	 * Protocol with, or empty if this method is not allowed/supported.
	 *
	 * @var string
	 */
	private $dataEndpoint = '';

	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @since 2.2
	 *
	 * @param string $defaultGraph
	 * @param string $queryEndpoint
	 * @param string $updateEndpoint
	 * @param string $dataEndpoint
	 */
	public function __construct( $defaultGraph, $queryEndpoint, $updateEndpoint = '', $dataEndpoint = '' ) {
		$this->defaultGraph = $defaultGraph;
		$this->queryEndpoint = $queryEndpoint;
		$this->updateEndpoint = $updateEndpoint;
		$this->dataEndpoint = $dataEndpoint;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $name
	 */
	public function setName( $name ) {
		$this->name = $name;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getDefaultGraph() {
		return $this->defaultGraph;
	}

	/**
	 * @since 2.2
	 *
	 * @return string|false
	 */
	public function getQueryEndpoint() {
		return $this->queryEndpoint;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getUpdateEndpoint() {
		return $this->updateEndpoint;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getDataEndpoint() {
		return $this->dataEndpoint;
	}

}
