<?php

namespace SMW\Elastic\QueryEngine\TermsLookup;

use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\QueryEngine\Condition;
use SMW\Elastic\QueryEngine\TermsLookup as ITermsLookup;
use SMW\Elastic\QueryEngine\FieldMapper;
use SMW\Elastic\QueryEngine\SearchResult;
use SMW\Options;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TermsLookup implements ITermsLookup {

	use LoggerAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var FieldMapper
	 */
	private $fieldMapper;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param Options $options
	 */
	public function __construct( Store $store, Options $options = null ) {
		$this->store = $store;
		$this->options = $options;

		if ( $options === null ) {
			$this->options = new Options();
		}

		$this->fieldMapper = new FieldMapper();
	}

	/**
	 * @since 3.0
	 */
	public function clear() {}

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return Parameters
	 */
	public function newParameters( array $parameters = [] ) {
		return new Parameters( $parameters );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = false ) {
		return $this->options->safeGet( $key, $default );
	}

	/**
	 * @since 3.0
	 *
	 * @param $type
	 * @param Parameters $parameters
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public function lookup( $type, Parameters $parameters ) {

		if ( $type === 'concept' ) {
			return $this->concept_index_lookup( $parameters );
		}

		if ( $type === 'chain' ) {
			return $this->chain_index_lookup( $parameters );
		}

		if ( $type === 'predef' ) {
			return $this->predef_index_lookup( $parameters );
		}

		if ( $type === 'inverse' ) {
			return $this->inverse_index_lookup( $parameters );
		}

		throw new RuntimeException( "$type is unknown!" );
	}

	/**
	 * @since 3.0
	 *
	 * @param Parameters $parameters
	 *
	 * @return array
	 */
	public function concept_index_lookup( Parameters $parameters ) {

		$params = $parameters->get( 'params' );
		$query = $params instanceof Condition ? $params->toArray() : $params;

		if ( $this->options->safeGet( 'subquery.constant.score', true ) ) {
			$query = $this->fieldMapper->constant_score( $query );
		}

		$parameters->set( 'search.body', [ "_source" => false, 'query' => $query ] );
		$parameters->set( 'result_filter.field', '_id' );

		$info = [
			'concept_lookup_query' => [
				$parameters->get( 'hash' ),
				$parameters->get( 'query.string' )
			]
		];

		$parameters->set( 'query.info', $info );

		$results = $this->query_result( $parameters );

		// Already in the `terms_filter` structure?
		if ( isset( $results['type'] ) && isset( $results['id'] ) ) {
			return $results;
		}

		return $this->ids_filter( $results );
	}

	/**
	 * Chainable queries (or better subqueries) aren't natively supported in ES.
	 *
	 * This creates its own query and executes it as independent transaction to
	 * return a list of matchable `_id` to can be fed to the source query.
	 *
	 * @since 3.0
	 *
	 * @param Parameters $parameters
	 *
	 * @return array
	 */
	public function chain_index_lookup( Parameters $parameters ) {

		$id = $parameters->get( 'id' );

		$query = $this->fieldMapper->bool( 'must', $parameters->get( 'params' ) );

		if ( $this->options->safeGet( 'subquery.constant.score', true ) ) {
			$query = $this->fieldMapper->constant_score( $query );
		}

		$parameters->set( 'search.body', [ "_source" => false, 'query' => $query ] );
		$parameters->set( 'result_filter.field', '_id' );

		$info = [
			'chain_lookup_query' => [
				$parameters->get( 'property.key' ),
				$parameters->get( 'query.string' )
			]
		];

		$parameters->set( 'query.info', $info );

		return $this->terms_filter( $parameters->get( 'terms_filter.field' ), $this->query_result( $parameters ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param Parameters $parameters
	 *
	 * @return array
	 */
	public function predef_index_lookup( Parameters $parameters ) {

		$id = $parameters->get( 'id' );
		$params = $parameters->get( 'params' );

		if ( $params instanceof Condition ) {
			$query = $params->toArray();
		} else {
			$query = $this->fieldMapper->bool( 'must', $params );
		}

		if ( $this->options->safeGet( 'subquery.constant.score', true ) ) {
			$query = $this->fieldMapper->constant_score( $query );
		}

		$parameters->set( 'search.body', [ "_source" => false, 'query' => $query ] );
		$parameters->set( 'result_filter.field', '_id' );

		$info = [
			'predef_lookup_query' => $parameters->get( 'query.string' )
		];

		$parameters->set( 'query.info', $info );

		return $this->terms_filter( $parameters->get( 'field' ), $this->query_result( $parameters ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param Parameters $parameters
	 *
	 * @return array
	 */
	public function inverse_index_lookup( Parameters $parameters ) {

		$id = $parameters->get( 'id' );
		$params = $parameters->get( 'params' );

		$info = [
			'inverse_lookup_query' => [
				$parameters->get( 'property.key' ),
				$parameters->get( 'query.string' )
			]
		];

		$parameters->set( 'query.info', $info + [ 'empty' ] );

		if ( !is_string( $params ) && ( $params === [] || $params == 0 ) ) {
			return [];
		}

		$field = $parameters->get( 'field' );

		if ( $params === '' ) {
			$query = $this->fieldMapper->bool( 'must', $this->fieldMapper->exists( "$field" ) );
			// [[-Has subobject::+]] vs. [[-Has number::+]]
			$field = strpos( $field, 'wpg' ) !== false ? $field : "_id";
		} else {
			$query = $this->fieldMapper->bool( 'must', $this->fieldMapper->terms( '_id', $params ) );
		}

		if ( $this->options->safeGet( 'subquery.constant.score', true ) ) {
			$query = $this->fieldMapper->constant_score( $query );
		}

		$parameters->set( 'search.body', [ "_source" => [ $field ], 'query' => $query ] );
		$parameters->set( 'result_filter.field', $field );
		$parameters->set( 'query.info', $info );

		return $this->terms_filter( '_id', $this->query_result( $parameters ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $field
	 * @param array $params
	 *
	 * @return array
	 */
	public function terms_filter( $field, $params ) {

		if ( $params === [] ) {
			// Fail with a non existing condition to avoid a " ...
			// query malformed, must start with start_object ..."
			return $this->fieldMapper->exists( "empty.lookup_query" );
		}

		$params = $this->fieldMapper->terms(
			$field,
			$params
		);

	//	if ( $this->options->safeGet( 'subquery.constant.score', true ) ) {
	//		$params = $this->fieldMapper->constant_score( $params );
	//	}

		return $params;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function ids_filter( $params ) {

		if ( $params === [] ) {
			// Fail with a non existing condition to avoid a " ...
			// query malformed, must start with start_object ..."
			return $this->fieldMapper->exists( "empty.lookup_query" );
		}

		$params = $this->fieldMapper->ids(
			$params
		);

		return $params;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	public function path_filter( $id ) {

		$connection = $this->store->getConnection( 'elastic' );

		$params = [
			'index' => $connection->getIndexName( ElasticClient::TYPE_LOOKUP ),
			'type'  => ElasticClient::TYPE_LOOKUP,
			'id'    => $id
		];

		// Define path for the terms filter
		return $params + [ 'path' => 'id' ];
	}

	private function query_result( Parameters $parameters ) {

		$connection = $this->store->getConnection( 'elastic' );
		$info = $parameters->get( 'query.info' );

		$params = [
			'index' => $connection->getIndexName( ElasticClient::TYPE_DATA ),
			'type'  => ElasticClient::TYPE_DATA,
			'body'  => $parameters->get( 'search.body' ),
			'size'  => $this->options->safeGet( 'subquery.size', 100 )
		];

		$info = $info + [
			'query' => $params,
			'search_info' => [ 'search_info' => [ 'total' => 0 ] ],
			'isFromCache' => false
		];

		$parameters->set( 'query.info', $info );

		if ( $parameters->get( 'params' ) === [] ) {
			return [];
		}

		list( $res, $errors ) = $connection->search(
			$params
		);

		$searchResult = new SearchResult( $res );
		$searchResult->setFilterField( $parameters->get( 'result_filter.field' ) );
		$searchResult->setErrors( $errors );

		$results = $searchResult->getResults();
		$count = $searchResult->get( 'count' );

		if ( $count >= $parameters->get( 'threshold' )  ) {
			$results = $this->terms_index( $parameters->get( 'id' ), $results );
		}

		$info['search_info'] = $searchResult->get( 'info' );

		$parameters->set( 'query.info', $info );
		$parameters->set( 'count', $count );

		return $results;
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-dsl-terms-query.html
	 */
	private function terms_index( $id, $results ) {

		$connection = $this->store->getConnection( 'elastic' );

		$params = [
			'index' => $connection->getIndexName( ElasticClient::TYPE_LOOKUP ),
			'type'  => ElasticClient::TYPE_LOOKUP,
			'id'    => $id
		];

		// https://www.elastic.co/blog/terms-filter-lookup
		// From the documentation "... the terms filter will be fetched from a
		// field in a document with the specified id in the specified type and
		// index. Internally a get request is executed to fetch the values from
		// the specified path. At the moment for this feature to work the _source
		// needs to be stored ..."
		$connection->index( $params + [ 'body' => [ 'id' => $results ] ] );

		// Refresh to ensure results are available for the upcoming search
		$connection->refresh( $params );

		// Define path for the terms filter
		return $params + [ 'path' => 'id' ];
	}

}
