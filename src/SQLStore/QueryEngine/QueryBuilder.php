<?php

namespace SMW\SQLStore\QueryEngine;

use OutOfBoundsException;
use InvalidArgumentException;
use SMW\Query\Language\Description;
use SMW\SQLStore\QueryEngine\Interpreter\ClassDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\ConceptDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\DisjunctionConjunctionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\NamespaceDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\SomePropertyInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\ThingDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\ValueDescriptionInterpreter;
use SMW\SQLStore\QueryEngine\Interpreter\DispatchingInterpreter;
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
	 * @var DispatchingInterpreter
	 */
	private $dispatchingInterpreter = null;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;

		SqlQueryPart::$qnum = 0;

		$this->dispatchingInterpreter = new DispatchingInterpreter();
		$this->dispatchingInterpreter->addDefaultInterpreter( new ThingDescriptionInterpreter( $this ) );

		$this->dispatchingInterpreter->addInterpreter( new SomePropertyInterpreter( $this ) );
		$this->dispatchingInterpreter->addInterpreter( new DisjunctionConjunctionInterpreter( $this ) );
		$this->dispatchingInterpreter->addInterpreter( new NamespaceDescriptionInterpreter( $this ) );
		$this->dispatchingInterpreter->addInterpreter( new ClassDescriptionInterpreter( $this ) );
		$this->dispatchingInterpreter->addInterpreter( new ValueDescriptionInterpreter( $this ) );
		$this->dispatchingInterpreter->addInterpreter( new ConceptDescriptionInterpreter( $this ) );
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
	 * @param int $id
	 *
	 * @return SqlQueryPart
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function getSqlQueryPart( $id ) {
		if ( !is_int( $id ) ) {
			throw new InvalidArgumentException( '$id needs to be an integer' );
		}

		if ( !array_key_exists( $id, $this->sqlQueryParts ) ) {
			throw new OutOfBoundsException( 'There is no query part with id ' . $id );
		}

		return $this->sqlQueryParts[$id];
	}

	/**
	 * @since 2.2
	 *
	 * @return SqlQueryPart[]
	 */
	public function getSqlQueryParts() {
		return $this->sqlQueryParts;
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

		$query = $this->dispatchingInterpreter->interpretDescription( $description );

		$this->registerQueryPart( $query );

		$this->lastQueryPartId = $query->type === SqlQueryPart::Q_NOQUERY ? -1 : $query->queryNumber;

		return $this->lastQueryPartId;
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
