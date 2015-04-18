<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QuerySegment;
use SMWQueryParser as QueryParser;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptQueryResolver {

	/**
	 * @var QueryEngine
	 */
	private $queryEngine;

	/**
	 * @var integer
	 */
	private $conceptFeatures;

	/**
	 * @since 2.2
	 *
	 * @param QueryEngine $queryEngine
	 */
	public function __construct( QueryEngine $queryEngine ) {
		$this->queryEngine = $queryEngine;
	}

	/**
	 * @since 2.2
	 *
	 * @param integer $conceptFeatures
	 */
	public function setConceptFeatures( $conceptFeatures ) {
		$this->conceptFeatures = $conceptFeatures;
	}

	/**
	 * @param string $conceptDescriptionText
	 *
	 * @return QuerySegment|null
	 */
	public function prepareQuerySegmentFor( $conceptDescriptionText ) {

		$querySegements = array();
		QuerySegment::$qnum = 0;

		$queryBuilder = $this->queryEngine->getQueryBuilder();
		$queryBuilder->setSortKeys( array() );

		$qp = new QueryParser( $this->conceptFeatures );

		$queryBuilder->buildQuerySegmentFor(
			$qp->getQueryDescription( $conceptDescriptionText )
		);

		$qid = $queryBuilder->getLastQuerySegmentId();
		$querySegements = $queryBuilder->getQuerySegments();

		if ( $qid < 0 ) {
			return null;
		}

		// execute query tree, resolve all dependencies
		$querySegmentListResolver = $this->queryEngine->getQuerySegmentListResolver();

		$querySegmentListResolver->setQueryMode( Query::MODE_INSTANCES );
		$querySegmentListResolver->setQuerySegmentList( $querySegements );
		$querySegmentListResolver->resolveForSegmentId( $qid );

		return $querySegements[$qid];
	}

	/**
	 * @since 2.2
	 */
	public function cleanUp() {
		$this->queryEngine->getQuerySegmentListResolver()->setQueryMode( Query::MODE_INSTANCES );
		$this->queryEngine->getQuerySegmentListResolver()->cleanUp();
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->queryEngine->getQueryBuilder()->getErrors();
	}

}
