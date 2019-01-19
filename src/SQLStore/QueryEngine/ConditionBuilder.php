<?php

namespace SMW\SQLStore\QueryEngine;

use InvalidArgumentException;
use OutOfBoundsException;
use SMW\Message;
use SMW\Query\Language\Conjuncton;
use SMW\Query\Language\Description;
use SMW\Store;
use SMW\SQLStore\SQLStore;
use SMW\Utils\CircularReferenceGuard;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ConditionBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var OrderCondition
	 */
	private $orderCondition;

	/**
	 * @var DispatchingDescriptionInterpreter
	 */
	private $dispatchingDescriptionInterpreter;

	/**
	 * @var boolean
	 */
	private $isFilterDuplicates = true;

	/**
	 * Array of generated QueryContainer query descriptions (index => object).
	 *
	 * @var QuerySegment[]
	 */
	private $querySegmentList = [];

	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC"). Used during query
	 * processing (where these property names are searched while compiling the query
	 * conditions).
	 *
	 * @var string[]
	 */
	private $sortKeys = [];

	/**
	 * @var string[]
	 */
	private $errors = [];

	/**
	 * @var integer
	 */
	private $lastQuerySegmentId = -1;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param OrderCondition $orderCondition
	 * @param DescriptionInterpreterFactory $descriptionInterpreterFactory
	 * @param CircularReferenceGuard $circularReferenceGuard
	 */
	public function __construct( Store $store, OrderCondition $orderCondition, DescriptionInterpreterFactory $descriptionInterpreterFactory, CircularReferenceGuard $circularReferenceGuard ) {
		$this->store = $store;
		$this->orderCondition = $orderCondition;
		$this->circularReferenceGuard = $circularReferenceGuard;
		$this->dispatchingDescriptionInterpreter = $descriptionInterpreterFactory->newDispatchingDescriptionInterpreter( $this );
		QuerySegment::$qnum = 0;
	}

	/**
	 * Filter dulicate segments that represent the same query and to be identified
	 * by the same hash.
	 *
	 * @since 2.5
	 *
	 * @param boolean $isFilterDuplicates
	 */
	public function isFilterDuplicates( $isFilterDuplicates ) {
		$this->isFilterDuplicates = (bool)$isFilterDuplicates;
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
	 * @return QuerySegment
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function findQuerySegment( $id ) {

		if ( !is_int( $id ) ) {
			throw new InvalidArgumentException( '$id needs to be an integer' );
		}

		if ( !array_key_exists( $id, $this->querySegmentList ) ) {
			throw new OutOfBoundsException( 'There is no query segment with id ' . $id );
		}

		return $this->querySegmentList[$id];
	}

	/**
	 * @since 2.2
	 *
	 * @return QuerySegment[]
	 */
	public function getQuerySegmentList() {
		return $this->querySegmentList;
	}

	/**
	 * @since 2.2
	 *
	 * @param QuerySegment $query
	 */
	public function addQuerySegment( QuerySegment $query ) {
		$this->querySegmentList[$query->queryNumber] = $query;
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
	public function addError( $error, $type = Message::TEXT ) {
		$this->errors[Message::getHash( $error, $type )] = Message::encode( $error, $type );
	}

	/**
	 * Compute abstract representation of the query (compilation)
	 *
	 * @param Query $query
	 *
	 * @return integer
	 */
	public function buildCondition( Query $query ) {

		$this->sortKeys = $query->sortkeys;
		$connection = $this->store->getConnection( 'mw.db.queryengine' );

		// Anchor ID_TABLE as root element
		$rootSegmentNumber = QuerySegment::$qnum;
		$rootSegment = new QuerySegment();
		$rootSegment->joinTable = SQLStore::ID_TABLE;
		$rootSegment->joinfield = "$rootSegment->alias.smw_id";

		$this->addQuerySegment(
			$rootSegment
		);

		// compile query, build query "plan"
		$qid = $this->buildFromDescription(
			$query->getDescription()
		);

		// no valid/supported condition; ensure that at least only proper pages
		// are delivered
		if ( $qid < 0 ) {
			$qid = $rootSegmentNumber;
			$qobj = $this->querySegmentList[$rootSegmentNumber];
			$qobj->where = "$qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) .
				" AND $qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWREDIIW ) .
				" AND $qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWBORDERIW ) .
				" AND $qobj->alias.smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWINTDEFIW );
			$this->addQuerySegment( $qobj );
		}

		if ( isset( $this->querySegmentList[$qid]->joinTable ) && $this->querySegmentList[$qid]->joinTable != SQLStore::ID_TABLE ) {
			// manually make final root query (to retrieve namespace,title):
			$rootid = $rootSegmentNumber;
			$qobj = $this->querySegmentList[$rootSegmentNumber];
			$qobj->components = [ $qid => "$qobj->alias.smw_id" ];
			$qobj->sortfields = $this->querySegmentList[$qid]->sortfields;
			$this->addQuerySegment( $qobj );
		} else { // not such a common case, but worth avoiding the additional inner join:
			$rootid = $qid;
		}

		$this->orderCondition->setSortKeys(
			$this->sortKeys
		);

		// Include order conditions (may extend query if needed for sorting):
		$this->orderCondition->addConditions(
			$this,
			$rootid
		);

		$this->sortKeys = $this->orderCondition->getSortKeys();

		return $rootid;
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
	public function buildFromDescription( Description $description ) {

		$fingerprint = $description->getFingerprint();

		// Get membership of descriptions that are resolved recursively
		if ( $description->getMembership() !== '' ) {
			$fingerprint = $fingerprint . $description->getMembership();
		}

		if ( ( $querySegment = $this->findDuplicates( $fingerprint ) ) ) {
			return $querySegment;
		}

		$querySegment = $this->dispatchingDescriptionInterpreter->interpretDescription(
			$description
		);

		$querySegment->fingerprint = $fingerprint;
		//$querySegment->membership = $description->getMembership();
		//$querySegment->queryString = $description->getQueryString();

		$this->lastQuerySegmentId = $this->registerQuerySegment(
			$querySegment
		);

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

	private function findDuplicates( $fingerprint ) {

		if ( $this->errors !== [] || $this->isFilterDuplicates === false ) {
			return false;
		}

		foreach ( $this->querySegmentList as $querySegment ) {
			if ( $querySegment->fingerprint === $fingerprint ) {
				return $querySegment->queryNumber;
			};
		}

		return false;
	}

}
