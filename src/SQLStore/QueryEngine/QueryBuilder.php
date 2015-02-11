<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\Query\Language\Description;
use SMW\SQLStore\QueryEngine\Compiler\ClassDescriptionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\ConceptDescriptionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\DisjunctionConjunctionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\NamespaceCompiler;
use SMW\SQLStore\QueryEngine\Compiler\SomePropertyCompiler;
use SMW\SQLStore\QueryEngine\Compiler\ThingDescriptionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\ValueDescriptionCompiler;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.2
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
	 *
	 * @var SqlQueryPart[]
	 */
	private $sqlQueryParts = array();

	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC"). Used during query
	 * processing (where these property names are searched while compiling the query
	 * conditions).
	 *
	 * @var string[]
	 */
	private $sortKeys = array();

	/**
	 * @var string[]
	 */
	private $errors = array();

	/**
	 * @var integer
	 */
	private $lastQueryPartId = -1;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;

		SqlQueryPart::$qnum = 0;

		$this->registerQueryCompiler( new SomePropertyCompiler( $this ) );
		$this->registerQueryCompiler( new DisjunctionConjunctionCompiler( $this ) );
		$this->registerQueryCompiler( new NamespaceCompiler( $this ) );
		$this->registerQueryCompiler( new ClassDescriptionCompiler( $this ) );
		$this->registerQueryCompiler( new ValueDescriptionCompiler( $this ) );
		$this->registerQueryCompiler( new ConceptDescriptionCompiler( $this ) );
	}

	/**
	 * @since 2.2
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * @since 2.2
	 *
	 * @param array $sortKeys
	 *
	 * @return $this
	 */
	public function setSortKeys( $sortKeys ) {
		$this->sortKeys = $sortKeys;
		return $this;
	}

	/**
	 * @since 2.2
	 *
	 * @return string[]
	 */
	public function getSortKeys() {
		return $this->sortKeys;
	}

	/**
	 * @since 2.2
	 *
	 * @param int|null $id
	 *
	 * @return array
	 */
	public function getSqlQueryPart( $id = null ) {
		if ( $id === null ) {
			return $this->sqlQueryParts;
		}

		return isset( $this->sqlQueryParts[$id] ) ? $this->sqlQueryParts[$id] : array();
	}

	/**
	 * @since 2.2
	 *
	 * @param int $id
	 * @param SqlQueryPart $query
	 *
	 * @return QueryBuilder
	 */
	public function addSqlQueryPartForId( $id, SqlQueryPart $query ) {
		$this->sqlQueryParts[$id] = $query;
		return $this;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getLastSqlQueryPartId() {
		return $this->lastQueryPartId;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $error
	 */
	public function addError( $error ) {
		$this->errors[] = $error;
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
	public function buildSqlQueryPartFor( Description $description ) {
		$query = $this->getQueryCompiler( $description )->compileDescription( $description );

		$this->registerQueryPart( $query );

		$this->lastQueryPartId = $query->type === SqlQueryPart::Q_NOQUERY ? -1 : $query->queryNumber;

		return $this->lastQueryPartId;
	}

	/**
	 * @since 2.2
	 *
	 * @param QueryCompiler $queryCompiler
	 */
	public function registerQueryCompiler( QueryCompiler $queryCompiler ) {
		$this->queryCompilers[] = $queryCompiler;
	}

	private function getQueryCompiler( Description $description ) {
		foreach ( $this->queryCompilers as $queryCompiler ) {
			if ( $queryCompiler->canCompileDescription( $description ) ) {
				return $queryCompiler;
			}
		}

		// Instead of throwing an exception we return a ThingDescriptionCompiler
		// for all unregistered/unknown descriptions
		return new ThingDescriptionCompiler( $this );
	}

	/**
	 * Register a query object to the internal query list, if the query is
	 * valid. Also make sure that sortkey information is propagated down
	 * from subqueries of this query.
	 *
	 * @param SqlQueryPart $query
	 */
	private function registerQueryPart( SqlQueryPart $query ) {
		if ( $query->type === SqlQueryPart::Q_NOQUERY ) {
			return;
		}

		$this->addSqlQueryPartForId( $query->queryNumber, $query );

		// Propagate sortkeys from subqueries:
		if ( $query->type !== SqlQueryPart::Q_DISJUNCTION ) {
			// Sortkeys are killed by disjunctions (not all parts may have them),
			// NOTE: preprocessing might try to push disjunctions downwards to safe sortkey, but this seems to be minor
			foreach ( $query->components as $cid => $field ) {
				$query->sortfields = array_merge( $this->getSqlQueryPart( $cid )->sortfields, $query->sortfields );
			}
		}
	}

}
