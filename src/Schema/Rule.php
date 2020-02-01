<?php

namespace SMW\Schema;

use JsonSerializable;
use SMW\Utils\DotArray;
use IteratorAggregate;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class Rule extends Compartment {

	/**
	 * @var int
	 */
	public $filterScore = 0;

	/**
	 * When using a chained filter, the score allows to find the best filter rule
	 * by comparing the score on the matched filter condition.
	 *
	 * For example, if an entity is annotated with `[[Category:Lorem ipsum]]`
	 * both rules would apply but when the entity is part of the `SMW_NS_PROPERTY`
	 * namespace, the second filter rule would get a higher score as its matches
	 * both the `category` and `namespace` filter.
	 *
	 * ```
	 * "if": {
	 *		"category": "Lorem ipsum"
	 * }
	 * ```
	 *
	 * vs.
	 *
	 * ```
	 * "if": {
	 *		"namespace": "SMW_NS_PROPERTY",
	 *		"category": "Lorem ipsum"
	 * }
	 * ```
	 * @since 3.2
	 */
	public function incrFilterScore() {
		$this->filterScore++;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function if( string $key, $default = null ) {
		return $this->get( "if.$key", $default );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function then( string $key, $default = null ) {
		return $this->get( "then.$key", $default );
	}

}
