<?php

namespace SMW\SQLStore\QueryEngine;

use InvalidArgumentException;
use OutOfBoundsException;
use SMW\DataItems\WikiPage;
use SMW\Localizer\Message;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Query;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\DispatchingDescriptionInterpreter;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\CircularReferenceGuard;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ConditionBuilder {

	/**
	 * Minimum number of WikiPage-typed condition values/categories required
	 * before they are batch-resolved up front. Batching adds a small fixed
	 * overhead (one `IN` lookup plus a link batch), so it is only worthwhile
	 * once a condition carries more than a handful of page-typed entities;
	 * smaller conditions keep resolving each value individually.
	 */
	private const WARM_UP_THRESHOLD = 10;

	private DispatchingDescriptionInterpreter $dispatchingDescriptionInterpreter;

	private bool $isFilterDuplicates = true;

	/**
	 * Array of generated QueryContainer query descriptions (index => object).
	 *
	 * @var QuerySegment[]
	 */
	private array $querySegmentList = [];

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
	private array $errors = [];

	/**
	 * @var int
	 */
	private $lastQuerySegmentId = -1;

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly Store $store,
		private readonly OrderCondition $orderCondition,
		DescriptionInterpreterFactory $descriptionInterpreterFactory,
		private readonly CircularReferenceGuard $circularReferenceGuard,
	) {
		$this->dispatchingDescriptionInterpreter = $descriptionInterpreterFactory->newDispatchingDescriptionInterpreter( $this );
		QuerySegment::$qnum = 0;
	}

	/**
	 * Filter dulicate segments that represent the same query and to be identified
	 * by the same hash.
	 *
	 * @since 2.5
	 *
	 * @param bool $isFilterDuplicates
	 */
	public function isFilterDuplicates( $isFilterDuplicates ): void {
		$this->isFilterDuplicates = (bool)$isFilterDuplicates;
	}

	/**
	 * @since 2.2
	 *
	 * @param array $sortKeys
	 *
	 * @return $this
	 */
	public function setSortKeys( $sortKeys ): static {
		$this->sortKeys = $sortKeys;
		return $this;
	}

	/**
	 * @since 2.2
	 *
	 * @return string[]
	 */
	public function getSortKeys(): array {
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
	public function getQuerySegmentList(): array {
		return $this->querySegmentList;
	}

	/**
	 * @since 2.2
	 *
	 * @param QuerySegment $query
	 */
	public function addQuerySegment( QuerySegment $query ): void {
		$this->querySegmentList[$query->queryNumber] = $query;
	}

	/**
	 * @since 2.2
	 *
	 * @return int
	 */
	public function getLastQuerySegmentId() {
		return $this->lastQuerySegmentId;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since 2.2
	 *
	 * @param string|array $error
	 * @param int|string|null $type
	 */
	public function addError( $error, $type = Message::TEXT ): void {
		$this->errors[Message::getHash( $error, $type )] = Message::encode( $error, $type );
	}

	/**
	 * Compute abstract representation of the query (compilation)
	 *
	 * @param Query $query
	 *
	 * @return int
	 */
	public function buildCondition( Query $query ): int {
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

		$qid = -1;

		$description = $query->getDescription();
		if ( $description !== null ) {
			// Warm the entity id cache for the WikiPage-typed values and
			// categories carried by the condition, so the per-value id
			// resolution performed by the description interpreters hits the
			// in-memory cache instead of issuing one SELECT on `smw_object_ids`
			// per value (#6559).
			$this->warmUpEntityIdCache( $description );

			// compile query, build query "plan"
			$qid = $this->buildFromDescription( $description );
		}

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
	 * Collect the WikiPage-typed values and categories referenced by the query
	 * condition and resolve their ids in a single batch up front.
	 *
	 * Without this, a condition carrying a large value disjunction
	 * (`[[Property::A||B||...||N]]`) or category list resolves every alternative
	 * with its own `smw_object_ids` SELECT during compilation, which can amount
	 * to thousands of round-trips for a single large query.
	 */
	private function warmUpEntityIdCache( Description $description ): void {
		$wikiPages = [];
		$this->collectWikiPages( $description, $wikiPages );

		if ( count( $wikiPages ) >= self::WARM_UP_THRESHOLD ) {
			$this->store->getObjectIds()->warmUpCache( $wikiPages );
		}
	}

	/**
	 * Walks the description tree gathering the entities the interpreters later
	 * resolve to an id by title: WikiPage values from `ValueDescription` and
	 * categories from `ClassDescription`. `ConceptDescription` is intentionally
	 * left out (a concept compiles through its own cached subquery), as are
	 * descriptions that carry no page-typed entity.
	 *
	 * @param Description $description
	 * @param WikiPage[] &$wikiPages
	 */
	private function collectWikiPages( Description $description, array &$wikiPages ): void {
		if ( $description instanceof Conjunction || $description instanceof Disjunction ) {
			foreach ( $description->getDescriptions() as $subDescription ) {
				$this->collectWikiPages( $subDescription, $wikiPages );
			}
		} elseif ( $description instanceof SomeProperty ) {
			$this->collectWikiPages( $description->getDescription(), $wikiPages );
		} elseif ( $description instanceof ClassDescription ) {
			foreach ( $description->getCategories() as $category ) {
				$wikiPages[] = $category;
			}
		} elseif ( $description instanceof ValueDescription ) {
			$dataItem = $description->getDataItem();

			if ( $dataItem instanceof WikiPage ) {
				$wikiPages[] = $dataItem;
			}
		}
	}

	/**
	 * Create a new QueryContainer object that can be used to obtain results
	 * for the given description. The result is stored in $this->queries
	 * using a numeric key that is returned as a result of the function.
	 * Returns -1 if no query was created.
	 *
	 * @param Description $description
	 *
	 * @return int
	 */
	public function buildFromDescription( Description $description ) {
		$fingerprint = $description->getFingerprint();

		// Get membership of descriptions that are resolved recursively
		if ( $description->getMembership() !== '' ) {
			$fingerprint .= $description->getMembership();
		}

		$querySegment = $this->findDuplicates( $fingerprint );
		if ( $querySegment ) {
			return $querySegment;
		}

		$querySegment = $this->dispatchingDescriptionInterpreter->interpretDescription(
			$description
		);

		$querySegment->fingerprint = $fingerprint;
		// $querySegment->membership = $description->getMembership();
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
		if ( $this->errors !== [] || !$this->isFilterDuplicates ) {
			return false;
		}

		foreach ( $this->querySegmentList as $querySegment ) {
			if ( $querySegment->fingerprint === $fingerprint ) {
				return $querySegment->queryNumber;
			}
		}

		return false;
	}

}
