<?php

namespace SMW;

/**
 * Small data container class for describing filtering conditions on the string
 * label of some entity. States that a given string should either be prefix,
 * postfix, or some arbitrary part of labels.
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author Markus Krötzsch
 */
class StringCondition {

	const STRCOND_PRE = 0;
	const STRCOND_POST = 1;
	const STRCOND_MID = 2;

	/**
	 * String to match.
	 *
	 * @var string
	 */
	public $string;

	/**
	 * Whether to match the strings as conjunction or
	 * disjunction.
	 *
	 * @var boolean
	 */
	public $asDisjunctiveCondition;

	/**
	 * Condition. One of STRCOND_PRE (string matches prefix),
	 * STRCOND_POST (string matches postfix), STRCOND_MID
	 * (string matches to some inner part).
	 *
	 * @var integer
	 */
	public $condition;

	/**
	 * @since 1.0
	 *
	 * @param srting $string
	 * @param integer $condition
	 * @param boolean $asDisjunctiveCondition
	 */
	public function __construct( $string, $condition, $asDisjunctiveCondition = false ) {
		$this->string = $string;
		$this->condition = $condition;
		$this->asDisjunctiveCondition = $asDisjunctiveCondition;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->string . '#' . $this->condition . '#' . $this->asDisjunctiveCondition;
	}

}
