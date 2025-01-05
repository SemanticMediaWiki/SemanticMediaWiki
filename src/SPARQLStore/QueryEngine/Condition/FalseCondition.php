<?php

namespace SMW\SPARQLStore\QueryEngine\Condition;

/**
 * Represents a condition that cannot match anything.
 * Ordering is not relevant, as there is nothing to order.
 *
 * @ingroup SMWStore
 *
 * @license GPL-2.0-or-later
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class FalseCondition extends Condition {

	public function getCondition() {
		return "<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .\n";
	}

	public function isSafe() {
		return true;
	}
}
