<?php

namespace SMW\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\Language\Description;
use SMW\Query\Language\ThingDescription;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ThingDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @since 2.2
	 *
	 * @param ConditionBuilder $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder ) {
		$this->conditionBuilder = $conditionBuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
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
