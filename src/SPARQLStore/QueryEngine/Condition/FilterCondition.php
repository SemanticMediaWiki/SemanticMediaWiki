<?php

namespace SMW\SPARQLStore\QueryEngine\Condition;

/**
 * A SPARQL condition that consists in a FILTER term only (possibly with some
 * weak conditions to introduce the variables that the filter acts on).
 *
 * @ingroup SMWStore
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class FilterCondition extends Condition {

	/**
	 * Additional filter condition, i.e. a string that could be placed in
	 * "FILTER( ... )".
	 * @var string
	 */
	public $filter;

	public function __construct( $filter, $namespaces = array() ) {
		$this->filter = $filter;
		$this->namespaces = $namespaces;
	}

	public function getCondition() {
		return "FILTER( {$this->filter} )\n";
	}

	public function isSafe() {
		return false;
	}

}
