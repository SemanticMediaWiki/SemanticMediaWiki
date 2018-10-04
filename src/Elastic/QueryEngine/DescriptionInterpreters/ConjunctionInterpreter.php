<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\Condition;
use SMW\Query\Language\Conjunction;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConjunctionInterpreter {

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder ) {
		$this->conditionBuilder = $conditionBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param Conjunction $description
	 *
	 * @return Condition
	 */
	public function interpretDescription( Conjunction $description ) {

		$params = [];

		foreach ( $description->getDescriptions() as $desc ) {
			if ( ( $cond = $this->conditionBuilder->interpretDescription( $desc, true ) ) instanceof Condition ) {
				$params[] = $cond;
			}
		}

		if ( $params === [] ) {
			return [];
		}

		$condition = $this->conditionBuilder->newCondition( $params );
		$condition->type( Condition::TYPE_MUST );
		$condition->log( [ 'Conjunction' => $description->getQueryString() ] );

		return $condition;
	}

}
