<?php

namespace SMW\SPARQLStore\QueryEngine\Condition;

/**
 * A SPARQL condition that consists in a FILTER term only (possibly with some
 * weak conditions to introduce the variables that the filter acts on).
 *
 * @ingroup SMWStore
 *
 * @license GPL-2.0-or-later
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class FilterCondition extends Condition {

	public function __construct(
		public $filter,
		$namespaces = [],
	) {
		$this->namespaces = $namespaces;
	}

	public function getCondition() {
		return "FILTER( {$this->filter} )\n";
	}

	public function isSafe() {
		return false;
	}

}
