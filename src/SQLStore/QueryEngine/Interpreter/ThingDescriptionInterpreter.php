<?php

namespace SMW\SQLStore\QueryEngine\Interpreter;

use SMW\Query\Language\Description;
use SMW\Query\Language\ThingDescription;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ThingDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @var QuerySegmentListBuilder
	 */
	private $querySegmentListBuilder;

	/**
	 * @since 2.2
	 *
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 */
	public function __construct( QuerySegmentListBuilder $querySegmentListBuilder ) {
		$this->querySegmentListBuilder = $querySegmentListBuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof ThingDescription;
	}

	/**
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return QuerySegment
	 */
	public function interpretDescription( Description $description ) {

		$query = new QuerySegment();
		$query->type = QuerySegment::Q_NOQUERY;

		return $query;
	}

}
