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
	 * Used to identify a constraint conition set forth by the QueryResult
	 * process which doesn't modify the `limit`.
	 */
	const CONDITION_CONSTRAINT_RESULT = 'condition.constraint.result';

	/**
	 * Used to identify a constraint conidtion set forth by any other query
	 * process.
	 */
	const CONDITION_CONSTRAINT = 'conditon.constraint';

	/**
	 * Defines a prefetch fingerprint
	 */
	const PREFETCH_FINGERPRINT = 'prefetch.fingerprint';

	const SEARCH_FIELD = 'search_field';

	/**
	 * The maximum number of results that should be returned.
	 */
	public $limit = -1;

	/**
	 * For certain queries (prefetch using WHERE IN) using the limit will cause
	 * the whole set to be restricted on a bulk instead of only applied to a subset
	 * therefore allow the exclude the limit and apply an restriction during the
	 * post-processing.
	 */
	public $exclude_limit = false;

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
	 *
	 * @var StringCondition[]
	 */
	private $stringConditions = [];

	/**
	 * Contains extra conditions which a consumer is being allowed to interpret
	 * freely to modify a search condition.
	 *
	 * @var array
	 */
	private $extraConditions = [];

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @var String|null
	 */
	private $caller;

	/**
	 * @since 3.1
	 *
	 * @param string $caller
	 */
	public function setCaller( $caller ) {
		$this->caller = $caller;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getCaller() {
		return $this->caller;
	}

	/**
	 * @since 1.0
	 *
	 * @param string $string to match
	 * @param integer $condition one of STRCOND_PRE, STRCOND_POST, STRCOND_MID
	 * @param boolean $isOr
	 * @param boolean $isNot
	 */
	public function addStringCondition( $string, $condition, $isOr = false, $isNot = false ) {
		$this->stringConditions[] = new StringCondition( $string, $condition, $isOr, $isNot );
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
	 * @since 2.5
	 *
	 * @param mixed $extraCondition
	 */
	public function addExtraCondition( $extraCondition ) {
		$this->extraConditions[] = $extraCondition;
	}

	/**
	 * @since 2.5
	 *
	 * @param array
	 */
	public function getExtraConditions() {
		return $this->extraConditions;
	}

	/**
	 * @since 3.1
	 */
	public function emptyExtraConditions() {
		$this->extraConditions = [];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setOption( $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function deleteOption( $key ) {
		unset( $this->options[$key] );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = null ) {

		if ( isset( $this->options[$key] ) ) {
			return $this->options[$key];
		}

		return $default;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $limit
	 */
	public function setLimit( $limit ) {
		$this->limit = (int)$limit;
	}

	/**
	 * @since 2.5
	 *
	 * @return integer
	 */
	public function getLimit() {
		return (int)$this->limit;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $offset
	 */
	public function setOffset( $offset ) {
		$this->offset = (int)$offset;
	}

	/**
	 * @since 2.5
	 *
	 * @return integer
	 */
	public function getOffset() {
		return (int)$this->offset;
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

		return json_encode( [
			$this->limit,
			$this->offset,
			$this->sort,
			$this->ascending,
			$this->boundary,
			$this->include_boundary,
			$this->exclude_limit,
			$stringConditions,
			$this->extraConditions,
			$this->options,
		] );
	}

}
