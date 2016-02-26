<?php

namespace SMW;

/**
 * Container object for various options that can be used when retrieving
 * data from the store. These options are mostly relevant for simple,
 * direct requests -- inline queries may require more complex options due
 * to their more complex structure.
 * Options that should not be used or where default values should be used
 * can be left as initialised.
 *
 * @license GNU GPL v2+
 * @since 1.0
 *
 * @author Markus Krötzsch
 */
class RequestOptions {

	/**
	 * The maximum number of results that should be returned.
	 */
	public $limit = -1;

	/**
	 * A numerical offset. The first $offset results are skipped.
	 * Note that this does not imply a defined order of results
	 * (see SMWRequestOptions->$sort below).
	 */
	public $offset = 0;

	/**
	 * Should the result be ordered? The employed order is defined
	 * by the type of result that are requested: wiki pages and strings
	 * are ordered alphabetically, whereas other data is ordered
	 * numerically. Usually, the order should be fairly "natural".
	 */
	public $sort = false;

	/**
	 * If SMWRequestOptions->$sort is true, this parameter defines whether
	 * the results are ordered in ascending or descending order.
	 */
	public $ascending = true;

	/**
	 * Specifies a lower or upper bound for the values returned by the query.
	 * Whether it is lower or upper is specified by the parameter "ascending"
	 * (true->lower, false->upper).
	 */
	public $boundary = null;

	/**
	 * Specifies whether or not the requested boundary should be returned
	 * as a result.
	 */
	public $include_boundary = true;

	/**
	 * An array of string conditions that are applied if the result has a
	 * string label that can be subject to those patterns.
	 */
	private $stringConditions = array();

	/**
	 * @since 1.0
	 *
	 * @param string $string to match
	 * @param integer $condition one of STRCOND_PRE, STRCOND_POST, STRCOND_MID
	 * @param boolean $asDisjunctiveCondition
	 */
	public function addStringCondition( $string, $condition, $asDisjunctiveCondition = false ) {
		$this->stringConditions[] = new StringCondition( $string, $condition, $asDisjunctiveCondition );
	}

	/**
	 * Return the specified array of SMWStringCondition objects.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function getStringConditions() {
		return $this->stringConditions;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getHash() {

		$stringConditions = '';

		foreach ( $this->stringConditions as $stringCondition ) {
			$stringConditions .= $stringCondition->getHash();
		}

		return $this->limit . '#' .
			$this->offset . '#' .
			$this->sort . '#' .
			$this->ascending . '#' .
			$this->boundary . '#' .
			$this->include_boundary .
			( $stringConditions !== '' ? '|' . $stringConditions : '' );
	}

}
