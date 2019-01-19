<?php

namespace SMW\Elastic\Indexer;

use SMW\Elastic\Connection\Client as ElasticClient;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Bulk {

	/**
	 * @var ElasticClient
	 */
	private $connection;

	/**
	 * @var array
	 */
	private $bulk = [];

	/**
	 * @var array
	 */
	private $head = [];

	/**
	 * @since 3.0
	 */
	public function __construct( ElasticClient $connection ) {
		$this->connection = $connection;
	}

	/**
	 * @since 3.0
	 */
	public function clear() {
		$this->bulk = [];
		$this->head = [];
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function head( array $params ) {
		$this->head = $params;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 */
	public function delete( array $params ) {
		$this->bulk['body'][] = [ 'delete' => $params + $this->head ];
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 * @param array $source
	 */
	public function index( array $params, array $source ) {
		$this->bulk['body'][] = [ 'index' => $params + $this->head ];
		$this->bulk['body'][] = $source;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 * @param array $source
	 */
	public function upsert( array $params, array $doc ) {
		$this->bulk['body'][] = [ 'update' => $params + $this->head ];
		$this->bulk['body'][] = [ 'doc' => $doc, "doc_as_upsert" => true ];
	}

	/**
	 * @since 3.1
	 */
	public function isEmpty() {
		return $this->bulk === [];
	}

	/**
	 * @since 3.0
	 */
	public function execute() {

		$response = $this->connection->bulk(
			$this->bulk
		);

		$this->bulk = [];

		return $response;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function toJson( $flags = 0 ) {
		return json_encode( $this->bulk, $flags );
	}

}
