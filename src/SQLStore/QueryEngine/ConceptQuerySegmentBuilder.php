<?php

namespace SMW\SQLStore\QueryEngine;

use SMWQuery as Query;
use SMWQueryParser as QueryParser;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ConceptQuerySegmentBuilder {

	/**
	 * @var QuerySegmentListBuilder
	 */
	private $querySegmentListBuilder;

	/**
	 * @var QuerySegmentListProcessor
	 */
	private $querySegmentListProcessor;

	/**
	 * @var integer
	 */
	private $conceptFeatures;

	/**
	 * @since 2.2
	 *
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 * @param QuerySegmentListProcessor $querySegmentListProcessor
	 */
	public function __construct( QuerySegmentListBuilder $querySegmentListBuilder, QuerySegmentListProcessor $querySegmentListProcessor ) {
		$this->querySegmentListBuilder = $querySegmentListBuilder;
		$this->querySegmentListProcessor = $querySegmentListProcessor;
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
	 * @since 2.2
	 *
	 * @param string $conceptDescriptionText
	 *
	 * @return QuerySegment|null
	 */
	public function getQuerySegmentFrom( $conceptDescriptionText ) {

		QuerySegment::$qnum = 0;

		$querySegmentListBuilder = $this->querySegmentListBuilder;
		$querySegmentListBuilder->setSortKeys( array() );

		$qp = new QueryParser( $this->conceptFeatures );

		$querySegmentListBuilder->getQuerySegmentFrom(
			$qp->getQueryDescription( $conceptDescriptionText )
		);

		$qid = $querySegmentListBuilder->getLastQuerySegmentId();
		$querySegmentList = $querySegmentListBuilder->getQuerySegmentList();

		if ( $qid < 0 ) {
			return null;
		}

		// execute query tree, resolve all dependencies
		$querySegmentListProcessor = $this->querySegmentListProcessor;

		$querySegmentListProcessor->setQueryMode( Query::MODE_INSTANCES );
		$querySegmentListProcessor->setQuerySegmentList( $querySegmentList );
		$querySegmentListProcessor->doResolveQueryDependenciesById( $qid );

		return $querySegmentList[$qid];
	}

	/**
	 * @since 2.2
	 */
	public function cleanUp() {
		$this->querySegmentListProcessor->setQueryMode( Query::MODE_INSTANCES );
		$this->querySegmentListProcessor->cleanUp();
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->querySegmentListBuilder->getErrors();
	}

}
