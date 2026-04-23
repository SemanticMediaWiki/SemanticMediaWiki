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
 * @license GPL-2.0-or-later
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

	/**
	 * Defines an individual search field
	 */
	const SEARCH_FIELD = 'search_field';

	/**
	 * The maximum number of results that should be returned.
	 *
	 * @var int
	 */
	public $limit = -1;

	/**
	 * For certain queries (prefetch using WHERE IN) using the limit will cause
	 * the whole set to be restricted on a bulk instead of only applied to a subset
	 * therefore allow the exclude the limit and apply an restriction during the
	 * post-processing.
	 *
	 * @var bool
	 */
	public $exclude_limit = false;

	/**
	 * A numerical offset. The first $offset results are skipped.
	 * Note that this does not imply a defined order of results
	 * (see RequestOptions->$sort below).
	 *
	 * @var int
	 */
	public $offset = 0;

	/**
	 * A numerical size to indicate a "look ahead" beyond the defined
	 * limit.
	 *
	 * @var int
	 */
	public $lookahead = 0;

	/**
	 * Should the result be ordered? The employed order is defined
	 * by the type of result that are requested: wiki pages and strings
	 * are ordered alphabetically, whereas other data is ordered
	 * numerically. Usually, the order should be fairly "natural".
	 *
	 * @var string|false
	 */
	public $sort = false;

	/**
	 * If RequestOptions->$sort is true, this parameter defines whether
	 * the results are ordered in ascending or descending order.
	 *
	 * @var bool
	 */
	public $ascending = true;

	/**
	 * Specifies a lower or upper bound for the values returned by the query.
	 * Whether it is lower or upper is specified by the parameter "ascending"
	 * (true->lower, false->upper).
	 *
	 * @var bool|int|string|null
	 */
	public $boundary = null;

	/**
	 * Specifies whether or not the requested boundary should be returned
	 * as a result.
	 *
	 * @var bool
	 */
	public $include_boundary = true;

	/**
	 * An array of string conditions that are applied if the result has a
	 * string label that can be subject to those patterns.
	 *
	 * @var StringCondition[]
	 */
	private array $stringConditions = [];

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
	 * @var string|null
	 */
	private $caller;

	public ?bool $conditionConstraint = null;

	public ?bool $isChain = null;

	public ?bool $isFirstChain = null;

	public ?bool $natural = null;

	private ?int $cursorAfter = null;
	private ?int $cursorBefore = null;
	private ?int $firstCursor = null;
	private ?int $lastCursor = null;
	private bool $cursorHasMore = false;

	public function setCursorAfter( int $id ): void {
		$this->cursorAfter = $id;
	}

	public function getCursorAfter(): ?int {
		return $this->cursorAfter;
	}

	public function setCursorBefore( int $id ): void {
		$this->cursorBefore = $id;
	}

	public function getCursorBefore(): ?int {
		return $this->cursorBefore;
	}

	public function setFirstCursor( int $id ): void {
		$this->firstCursor = $id;
	}

	public function getFirstCursor(): ?int {
		return $this->firstCursor;
	}

	public function setLastCursor( int $id ): void {
		$this->lastCursor = $id;
	}

	public function getLastCursor(): ?int {
		return $this->lastCursor;
	}

	public function hasCursor(): bool {
		return $this->cursorAfter !== null || $this->cursorBefore !== null;
	}

	public function setCursorHasMore( bool $hasMore ): void {
		$this->cursorHasMore = $hasMore;
	}

	public function getCursorHasMore(): bool {
		return $this->cursorHasMore;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $caller
	 */
	public function setCaller( $caller ): void {
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
	 * @param int $condition one of STRCOND_PRE, STRCOND_POST, STRCOND_MID
	 * @param bool $isOr
	 * @param bool $isNot
	 */
	public function addStringCondition( $string, $condition, $isOr = false, $isNot = false ): void {
		$this->stringConditions[] = new StringCondition( $string, $condition, $isOr, $isNot );
	}

	/**
	 * Return the specified array of SMWStringCondition objects.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function getStringConditions(): array {
		return $this->stringConditions;
	}

	/**
	 * @since 2.5
	 *
	 * @param mixed $extraCondition
	 *
	 * @return void
	 */
	public function addExtraCondition( $extraCondition ): void {
		$this->extraConditions[] = $extraCondition;
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getExtraConditions(): array {
		return $this->extraConditions;
	}

	/**
	 * @since 3.1
	 *
	 * @return void
	 */
	public function emptyExtraConditions(): void {
		$this->extraConditions = [];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function setOption( $key, $value ): void {
		$this->options[$key] = $value;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return void
	 */
	public function deleteOption( $key ): void {
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
	 * @param int $limit
	 *
	 * @return void
	 */
	public function setLimit( $limit ): void {
		$this->limit = (int)$limit;
	}

	/**
	 * @since 2.5
	 *
	 * @return int
	 */
	public function getLimit(): int {
		return (int)$this->limit;
	}

	/**
	 * @since 2.5
	 *
	 * @param int $offset
	 *
	 * @return void
	 */
	public function setOffset( $offset ): void {
		$this->offset = (int)$offset;
	}

	/**
	 * @since 2.5
	 *
	 * @return int
	 */
	public function getOffset(): int {
		return (int)$this->offset;
	}

	/**
	 * @since 3.2
	 *
	 * @param int $lookahead
	 *
	 * @return void
	 */
	public function setLookahead( int $lookahead ): void {
		$this->lookahead = $lookahead;
	}

	/**
	 * @since 3.2
	 *
	 * @return int
	 */
	public function getLookahead(): int {
		return $this->lookahead;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getHash(): string|false {
		$stringConditions = '';

		foreach ( $this->stringConditions as $stringCondition ) {
			$stringConditions .= $stringCondition->getHash();
		}

		return json_encode( [
			$this->limit,
			$this->offset,
			$this->lookahead,
			$this->sort,
			$this->ascending,
			$this->boundary,
			$this->include_boundary,
			$this->exclude_limit,
			$stringConditions,
			$this->extraConditions,
			$this->options,
			$this->cursorAfter,
			$this->cursorBefore,
		] );
	}

}
