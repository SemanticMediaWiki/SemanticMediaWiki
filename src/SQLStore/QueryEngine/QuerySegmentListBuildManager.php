<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\DIWikiPage;
use SMWQuery as Query;
use SMW\MediaWiki\Database;
use SMW\SQLStore\SQLStore;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class QuerySegmentListBuildManager {

	/**
	 * @var QuerySegment[]
	 */
	private $querySegmentList = array();

	/**
	 * @var string[]
	 */
	private $errors = array();

	/**
	 * @var string[]
	 */
	private $sortKeys;

	/**
	 * @var QuerySegmentListBuilder
	 */
	private $querySegmentListBuilder;

	/**
	 * @var OrderConditionsComplementor
	 */
	private $orderConditionsComplementor;

	/**
	 * @since 2.5
	 *
	 * @param Database $connection
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 * @param OrderConditionsComplementor $orderConditionsComplementor
	 */
	public function __construct( Database $connection, QuerySegmentListBuilder $querySegmentListBuilder, OrderConditionsComplementor $orderConditionsComplementor ) {
		$this->connection = $connection;
		$this->querySegmentListBuilder = $querySegmentListBuilder;
		$this->orderConditionsComplementor = $orderConditionsComplementor;
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
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getQuerySegmentList() {
		return $this->querySegmentList;
	}

	/**
	 * Compute abstract representation of the query (compilation)
	 *
	 * @param Query $query
	 *
	 * @return integer
	 */
	public function getQuerySegmentFrom( Query $query ) {

		$this->sortKeys = $query->sortkeys;

		// Anchor IT_TABLE as root element
		$rootSegmentNumber = QuerySegment::$qnum;
		$rootSegment = new QuerySegment();
		$rootSegment->joinTable = SQLStore::ID_TABLE;
		$rootSegment->joinfield = "$rootSegment->alias.smw_id";

		$this->querySegmentListBuilder->addQuerySegment(
			$rootSegment
		);

		$this->querySegmentListBuilder->setSortKeys(
			$this->sortKeys
		);

		// compile query, build query "plan"
		$this->querySegmentListBuilder->getQuerySegmentFrom(
			$query->getDescription()
		);

		$qid = $this->querySegmentListBuilder->getLastQuerySegmentId();
		$this->querySegmentList = $this->querySegmentListBuilder->getQuerySegmentList();
		$this->errors = $this->querySegmentListBuilder->getErrors();

		// no valid/supported condition; ensure that at least only proper pages
		// are delivered
		if ( $qid < 0 ) {
			$qid = $rootSegmentNumber;
			$qobj = $this->querySegmentList[$rootSegmentNumber];
			$qobj->where = "$qobj->alias.smw_iw!=" . $this->connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) .
				" AND $qobj->alias.smw_iw!=" . $this->connection->addQuotes( SMW_SQL3_SMWREDIIW ) .
				" AND $qobj->alias.smw_iw!=" . $this->connection->addQuotes( SMW_SQL3_SMWBORDERIW ) .
				" AND $qobj->alias.smw_iw!=" . $this->connection->addQuotes( SMW_SQL3_SMWINTDEFIW );
			$this->querySegmentListBuilder->addQuerySegment( $qobj );
		}

		if ( isset( $this->querySegmentList[$qid]->joinTable ) && $this->querySegmentList[$qid]->joinTable != SQLStore::ID_TABLE ) {
			// manually make final root query (to retrieve namespace,title):
			$rootid = $rootSegmentNumber;
			$qobj = $this->querySegmentList[$rootSegmentNumber];
			$qobj->components = array( $qid => "$qobj->alias.smw_id" );
			$qobj->sortfields = $this->querySegmentList[$qid]->sortfields;
			$this->querySegmentListBuilder->addQuerySegment( $qobj );
		} else { // not such a common case, but worth avoiding the additional inner join:
			$rootid = $qid;
		}

		$this->orderConditionsComplementor->setSortKeys(
			$this->sortKeys
		);

		// Include order conditions (may extend query if needed for sorting):
		$this->querySegmentList = $this->orderConditionsComplementor->applyOrderConditions(
			$rootid
		);

		$this->sortKeys = $this->orderConditionsComplementor->getSortKeys();
		$this->errors = $this->orderConditionsComplementor->getErrors();

		return $rootid;
	}

}
