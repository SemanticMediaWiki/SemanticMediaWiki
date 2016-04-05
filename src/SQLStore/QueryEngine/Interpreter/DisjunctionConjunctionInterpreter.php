<?php

namespace SMW\SQLStore\QueryEngine\Interpreter;

use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class DisjunctionConjunctionInterpreter implements DescriptionInterpreter {

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
		return $description instanceof Conjunction || $description instanceof Disjunction;
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
		$query->type = $description instanceof Conjunction ? QuerySegment::Q_CONJUNCTION : QuerySegment::Q_DISJUNCTION;

		foreach ( $description->getDescriptions() as $subDescription ) {

			$subQueryId = $this->querySegmentListBuilder->buildQuerySegmentFor( $subDescription );

			if ( $subQueryId >= 0 ) {
				$query->components[$subQueryId] = true;
			}
		}

		// All subconditions failed, drop this as well.
		if ( count( $query->components ) == 0 ) {
			$query->type = QuerySegment::Q_NOQUERY;
		}

		return $query;
	}

}
