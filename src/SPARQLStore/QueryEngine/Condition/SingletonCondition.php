<?php

namespace SMW\SPARQLStore\QueryEngine\Condition;

use SMW\Exporter\Element\ExpElement;

/**
 * A SPARQL condition that can match only a single element, or nothing at all.
 *
 * @ingroup SMWStore
 *
 * @license GPL-2.0-or-later
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class SingletonCondition extends Condition {

	/**
	 * The single element that this condition may possibly match.
	 *
	 * @var ExpElement
	 */
	public $matchElement;

	public function __construct(
		ExpElement $matchElement,
		public $condition = '',
		public $isSafe = false,
		$namespaces = [],
	) {
		$this->matchElement = $matchElement;
		$this->namespaces = $namespaces;
	}

	public function getCondition() {
		return $this->condition;
	}

	public function isSafe() {
		return $this->isSafe;
	}

}
