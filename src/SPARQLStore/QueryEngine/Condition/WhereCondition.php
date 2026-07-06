<?php

namespace SMW\SPARQLStore\QueryEngine\Condition;

/**
 * Container class that represents a SPARQL (sub-)pattern and relevant pieces
 * of associated information for using it in query building.
 *
 * @ingroup SMWStore
 *
 * @license GPL-2.0-or-later
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class WhereCondition extends Condition {

	public function __construct(
		public $condition,
		public $isSafe,
		$namespaces = [],
	) {
		$this->namespaces = $namespaces;
	}

	public function getCondition() {
		return $this->condition;
	}

	public function isSafe() {
		return $this->isSafe;
	}
}
