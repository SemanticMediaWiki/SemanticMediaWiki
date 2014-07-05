<?php

namespace SMW\SPARQLStore\QueryEngine\Condition;

/**
 * Represents a condition that matches everything. Weak conditions (see
 * SMWSparqlCondition::$weakConditions) might be still be included to
 * enable ordering (selecting sufficient data to order by).
 *
 * @ingroup SMWStore
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class TrueCondition extends Condition {

	public function getCondition() {
		return '';
	}

	public function isSafe() {
		return false;
	}
}
