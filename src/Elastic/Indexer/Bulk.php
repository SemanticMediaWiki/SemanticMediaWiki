<?php

namespace SMW\Elastic\Indexer;

use SMW\Elastic\Connection\Client as ElasticClient;
use JsonSerializable;

/**
 * @note Elasticsearch provides a bulk API to perform several index/delete operations
 * with a single API call and can greatly improve the indexing speed.
 *
 * This class builds a call stack for a single bulk request and can contain different
 * operational tasks.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Bulk implements JsonSerializable {

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
	 * @var array
	 */
	private $response = [];

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
		$this->response = [];
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
	 * @since 3.2
	 *
	 * @param Document $document
	 */
	public function infuseDocument( Document $document ) {

		if ( $document->isType( Document::TYPE_DELETE ) ) {
			$this->delete( [ '_id' => $document->getId() ] );
		}

		foreach ( $document->getPriorityDeleteList() as $id ) {
			$this->delete( [ '_id' => $id ] );
		}

		if ( $document->isType( Document::TYPE_UPSERT ) ) {
			$this->upsert( [ '_id' => $document->getId() ], $document->getData() );
		}

		if ( $document->isType( Document::TYPE_INSERT ) ) {
			$this->index( [ '_id' => $document->getId() ], $document->getData() );
		}

		foreach ( $document->getSubDocuments() as $subDocument ) {
			$this->infuseDocument( $subDocument );
		}
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function getResponse() : array {
		return $this->response;
	}

	/**
	 * @since 3.0
	 */
	public function execute() {

		$this->response = $this->connection->bulk(
			$this->bulk
		);

		$this->bulk = [];
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function jsonSerialize() {
		return json_encode( $this->bulk );
	}

}
