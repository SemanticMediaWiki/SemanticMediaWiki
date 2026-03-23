<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\Condition;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Query\Language\Conjunction;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ConjunctionInterpreter {

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly ConditionBuilder $conditionBuilder ) {
	}

	/**
	 * @since 3.0
	 *
	 * @param Conjunction $description
	 *
	 * @return Condition
	 */
	public function interpretDescription( Conjunction $description ): array|Condition {
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
