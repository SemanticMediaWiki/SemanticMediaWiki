<?php

namespace SMW\SPARQLStore\QueryEngine\Condition;

use SMWExpElement;

/**
 * A SPARQL condition that can match only a single element, or nothing at all.
 *
 * @ingroup SMWStore
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class SingletonCondition extends Condition {

	/**
	 * Pattern string. Anything that can be used as a WHERE condition
	 * when put between "{" and "}". Can be empty if the result
	 * unconditionally is the given element.
	 * @var string
	 */
	public $condition;

	/**
	 * The single element that this condition may possibly match.
	 * @var SMWExpElement
	 */
	public $matchElement;

	/**
	 * Whether this condition is safe.
	 * @see SMWSparqlCondition::isSafe().
	 * @var boolean
	 */
	public $isSafe;

	public function __construct( SMWExpElement $matchElement, $condition = '', $isSafe = false, $namespaces = array() ) {
		$this->matchElement = $matchElement;
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
