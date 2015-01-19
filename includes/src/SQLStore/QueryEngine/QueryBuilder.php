<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\Compiler\NamespaceCompiler;
use SMW\SQLStore\QueryEngine\Compiler\DisjunctionConjunctionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\ClassDescriptionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\ValueDescriptionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\ConceptDescriptionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\SomePropertyCompiler;

use SMW\Query\Language\Description;

use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class QueryBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var QueryCompiler[]
	 */
	private $queryCompilers = array();

	/**
	 * Array of generated QueryContainer query descriptions (index => object).
	 */
	private $queries = array();

	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC"). Used during query
	 * processing (where these property names are searched while compiling the query
	 * conditions).
	 */
	private $sortkeys = array();

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @var integer
	 */
	private $lastContainerId = -1;

	/**
	 * @since  2.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;

		$this->setToInitialBuildState();

		$this->registerQueryCompiler( new SomePropertyCompiler( $this ) );
		$this->registerQueryCompiler( new DisjunctionConjunctionCompiler( $this ) );
		$this->registerQueryCompiler( new NamespaceCompiler( $this ) );
		$this->registerQueryCompiler( new ClassDescriptionCompiler( $this ) );
		$this->registerQueryCompiler( new ValueDescriptionCompiler( $this ) );
		$this->registerQueryCompiler( new ConceptDescriptionCompiler( $this ) );
	}

	/**
	 * @since 2.1
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * @since 2.1
	 *
	 * @param array $sortkeys
	 *
	 * @return QueryBuilder
	 */
	public function setSortKeys( $sortkeys ) {
		$this->sortkeys = $sortkeys;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param array $sortkeys
	 */
	public function getSortKeys() {
		return $this->sortkeys;
	}

	/**
	 * @since  2.1
	 *
	 * @return array
	 */
	public function getQueryContainer( $id = null ) {

		if ( $id === null ) {
			return $this->queries;
		}

		return isset( $this->queries[ $id ] ) ? $this->queries[ $id ] : array();
	}

	/**
	 * @since  2.1
	 *
	 * @param $id
	 * @param QueryContainer $query
	 *
	 * @return QueryBuilder
	 */
	public function addQueryContainerForId( $id, QueryContainer $query ) {
		$this->queries[ $id ] = $query;
		return $this;
	}

	/**
	 * @since  2.1
	 *
	 * @return integer
	 */
	public function getLastContainerId() {
		return $this->lastContainerId;
	}

	/**
	 * @since  2.1
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since  2.1
	 *
	 * @return string $error
	 */
	public function addError( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * @since 2.1
	 *
	 * @return QueryBuilder
	 */
	public function setToInitialBuildState() {
		QueryContainer::$qnum = 0;
		$this->lastContainerId = -1;
		$this->sortkeys = array();
		$this->queries = array();
		$this->errors = array();

		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param  Description $description
	 *
	 * @return integer
	 */
	public function buildQueryContainer( Description $description ) {
		return $this->compileQueries( $description );
	}

	/**
	 * Create a new QueryContainer object that can be used to obtain results
	 * for the given description. The result is stored in $this->queries
	 * using a numeric key that is returned as a result of the function.
	 * Returns -1 if no query was created.
	 *
	 * @param Description $description
	 *
	 * @return integer
	 */
	public function compileQueries( Description $description ) {

		$queryCompiler = $this->getQueryCompiler( $description );

		if ( $queryCompiler !== null ) {
			$query = $queryCompiler->compileDescription( $description );
		} else {
			$query = new QueryContainer();
			$query->type = QueryContainer::Q_NOQUERY; // no condition
		}

		$this->registerQuery( $query );

		return $this->lastContainerId = $query->type !== QueryContainer::Q_NOQUERY ? $query->queryNumber : -1;
	}

	/**
	 * @since  2.1
	 *
	 * @param QueryCompiler $queryCompiler
	 */
	public function registerQueryCompiler( QueryCompiler $queryCompiler ) {
		$this->queryCompilers[] = $queryCompiler;
	}

	protected function getQueryCompiler( Description $description ) {
		foreach ( $this->queryCompilers as $queryCompiler ) {
			if ( $queryCompiler->canCompileDescription( $description ) ) {
				return $queryCompiler;
			}
		}

		// throw new RuntimeException( "Description has no registered compiler" );
		return null;
	}

	/**
	 * Register a query object to the internal query list, if the query is
	 * valid. Also make sure that sortkey information is propagated down
	 * from subqueries of this query.
	 */
	protected function registerQuery( QueryContainer $query ) {

		if ( $query->type === QueryContainer::Q_NOQUERY ) {
			return null;
		}

		$this->addQueryContainerForId( $query->queryNumber, $query );

		// Propagate sortkeys from subqueries:
		if ( $query->type !== QueryContainer::Q_DISJUNCTION ) {
			// Sortkeys are killed by disjunctions (not all parts may have them),
			// NOTE: preprocessing might try to push disjunctions downwards to safe sortkey, but this seems to be minor
			foreach ( $query->components as $cid => $field ) {
				$query->sortfields = array_merge( $this->getQueryContainer( $cid )->sortfields, $query->sortfields );
			}
		}
	}

}
