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

	/**
	 * String matches prefix
	 */
	const COND_PRE = 0;
	const STRCOND_PRE = self::COND_PRE; // Deprecated

	/**
	 * String matches postfix
	 */
	const COND_POST = 1;
	const STRCOND_POST = self::COND_POST; // Deprecated

	/**
	 * String matches to some inner part
	 */
	const COND_MID = 2;
	const STRCOND_MID = self::COND_MID; // Deprecated

	/**
	 * String matches as equal
	 */
	const COND_EQ = 3;

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
	public $isOr;

	/**
	 * @var boolean
	 */
	public $isNot;

	/**
	 * @var integer
	 */
	public $condition;

	/**
	 * @since 1.0
	 *
	 * @param srting $string
	 * @param integer $condition
	 * @param boolean $isOr
	 */
	public function __construct( $string, $condition, $isOr = false, $isNot = false ) {
		$this->string = $string;
		$this->condition = $condition;
		$this->isOr = $isOr;
		$this->isNot = $isNot;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->string . '#' . $this->condition . '#' . $this->isOr . '#' . $this->isNot;
	}

}
