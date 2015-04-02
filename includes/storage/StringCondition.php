<?php

/**
 * Small data container class for describing filtering conditions on the string
 * label of some entity. States that a given string should either be prefix,
 * postfix, or some arbitrary part of labels.
 *
 * @ingroup SMWStore
 *
 * @author Markus Krötzsch
 */
class SMWStringCondition {

	const STRCOND_PRE = 0;
	const STRCOND_POST = 1;
	const STRCOND_MID = 2;

	/**
	 * String to match.
	 */
	public $string;

	/**
	 * Condition. One of STRCOND_PRE (string matches prefix),
	 * STRCOND_POST (string matches postfix), STRCOND_MID
	 * (string matches to some inner part).
	 */
	public $condition;

	public function __construct( $string, $condition ) {
		$this->string = $string;
		$this->condition = $condition;
	}

}
