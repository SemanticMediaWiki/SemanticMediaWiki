<?php

namespace SMW\SPARQLStore\QueryEngine\Condition;

/**
 * Container class that represents a SPARQL (sub-)pattern and relevant pieces
 * of associated information for using it in query building.
 *
 * @ingroup SMWStore
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class WhereCondition extends Condition {

	/**
	 * The pattern string. Anything that can be used as a WHERE condition
	 * when put between "{" and "}".
	 * @var string
	 */
	public $condition;

	/**
	 * Whether this condition is safe.
	 * @see SMWSparqlCondition::isSafe().
	 * @var boolean
	 */
	public $isSafe;

	public function __construct( $condition, $isSafe, $namespaces = array() ) {
		$this->condition  = $condition;
		$this->isSafe     = $isSafe;
		$this->namespaces = $namespaces;
	}

	public function getCondition() {
		return $this->condition;
	}

	public function isSafe() {
		return $this->isSafe;
	}
}
