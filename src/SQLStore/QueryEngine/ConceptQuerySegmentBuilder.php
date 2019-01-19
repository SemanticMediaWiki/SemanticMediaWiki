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
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

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
	 * @param ConditionBuilder $conditionBuilder
	 * @param QuerySegmentListProcessor $querySegmentListProcessor
	 */
	public function __construct( ConditionBuilder $conditionBuilder, QuerySegmentListProcessor $querySegmentListProcessor ) {
		$this->conditionBuilder = $conditionBuilder;
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

		$conditionBuilder = $this->conditionBuilder;
		$conditionBuilder->setSortKeys( [] );

		if ( $this->queryParser === null ) {
			throw new RuntimeException( 'Missing a QueryParser instance' );
		}

		$conditionBuilder->buildFromDescription(
			$this->queryParser->getQueryDescription( $conceptDescriptionText )
		);

		$qid = $conditionBuilder->getLastQuerySegmentId();
		$querySegmentList = $conditionBuilder->getQuerySegmentList();

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
		return $this->conditionBuilder->getErrors();
	}

}
