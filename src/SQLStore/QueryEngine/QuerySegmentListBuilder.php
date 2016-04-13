<?php

namespace SMW\SQLStore\QueryEngine;

use InvalidArgumentException;
use OutOfBoundsException;
use SMW\CircularReferenceGuard;
use SMW\Query\Language\Description;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class QuerySegmentListBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DispatchingDescriptionInterpreter
	 */
	private $dispatchingDescriptionInterpreter = null;

	/**
	 * Array of generated QueryContainer query descriptions (index => object).
	 *
	 * @var QuerySegment[]
	 */
	private $querySegments = array();

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
	private $lastQuerySegmentId = -1;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param DescriptionInterpreterFactory $descriptionInterpreterFactory
	 */
	public function __construct( Store $store, DescriptionInterpreterFactory $descriptionInterpreterFactory ) {
		$this->store = $store;
		$this->dispatchingDescriptionInterpreter = $descriptionInterpreterFactory->newDispatchingDescriptionInterpreter( $this );
		$this->circularReferenceGuard = new CircularReferenceGuard( 'sql-query' );
		$this->circularReferenceGuard->setMaxRecursionDepth( 2 );

		QuerySegment::$qnum = 0;
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
	 * @return CircularReferenceGuard
	 */
	public function getCircularReferenceGuard() {
		return $this->circularReferenceGuard;
	}

	/**
	 * @since 2.2
	 *
	 * @param int $id
	 *
	 * @return QuerySegment
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function findQuerySegment( $id ) {

		if ( !is_int( $id ) ) {
			throw new InvalidArgumentException( '$id needs to be an integer' );
		}

		if ( !array_key_exists( $id, $this->querySegments ) ) {
			throw new OutOfBoundsException( 'There is no query segment with id ' . $id );
		}

		return $this->querySegments[$id];
	}

	/**
	 * @since 2.2
	 *
	 * @return QuerySegment[]
	 */
	public function getQuerySegmentList() {
		return $this->querySegments;
	}

	/**
	 * @since 2.2
	 *
	 * @param QuerySegment $query
	 */
	public function addQuerySegment( QuerySegment $query ) {
		$this->querySegments[$query->queryNumber] = $query;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getLastQuerySegmentId() {
		return $this->lastQuerySegmentId;
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
	public function buildQuerySegmentFor( Description $description ) {

		$querySegment = $this->dispatchingDescriptionInterpreter->interpretDescription(
			$description
		);

		$this->lastQuerySegmentId = $this->registerQuerySegment( $querySegment );

		return $this->lastQuerySegmentId;
	}

	/**
	 * Register a query object to the internal query list, if the query is
	 * valid. Also make sure that sortkey information is propagated down
	 * from subqueries of this query.
	 *
	 * @param QuerySegment $query
	 */
	private function registerQuerySegment( QuerySegment $query ) {
		if ( $query->type === QuerySegment::Q_NOQUERY ) {
			return -1;
		}

		$this->addQuerySegment( $query );

		// Propagate sortkeys from subqueries:
		if ( $query->type !== QuerySegment::Q_DISJUNCTION ) {
			// Sortkeys are killed by disjunctions (not all parts may have them),
			// NOTE: preprocessing might try to push disjunctions downwards to safe sortkey, but this seems to be minor
			foreach ( $query->components as $cid => $field ) {
				$query->sortfields = array_merge( $this->findQuerySegment( $cid )->sortfields, $query->sortfields );
			}
		}

		return $query->queryNumber;
	}

}
