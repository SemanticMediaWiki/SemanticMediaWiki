<?php

namespace SMW\SQLStore\QueryEngine;

use RuntimeException;
use SMW\Query\Parser as QueryParser;
use SMWQuery as Query;

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
	 * @var QueryParser
	 */
	private $queryParser;

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
	 * @since 3.0
	 *
	 * @param QueryParser $queryParser
	 */
	public function setQueryParser( QueryParser $queryParser ) {
		$this->queryParser = $queryParser;
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
		$querySegmentListBuilder->setSortKeys( [] );

		if ( $this->queryParser === null ) {
			throw new RuntimeException( 'Missing a QueryParser instance' );
		}

		$querySegmentListBuilder->getQuerySegmentFrom(
			$this->queryParser->getQueryDescription( $conceptDescriptionText )
		);

		$qid = $querySegmentListBuilder->getLastQuerySegmentId();
		$querySegmentList = $querySegmentListBuilder->getQuerySegmentList();

		if ( $qid < 0 ) {
			return null;
		}

		// execute query tree, resolve all dependencies
		$this->querySegmentListProcessor->setQueryMode(
			Query::MODE_INSTANCES
		);

		$this->querySegmentListProcessor->setQuerySegmentList(
			$querySegmentList
		);

		$this->querySegmentListProcessor->process( $qid );

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
