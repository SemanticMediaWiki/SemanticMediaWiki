<?php

namespace SMW\Query\Result;

use SMW\Query\Query;
use SMW\Query\QueryResult;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class StringResult extends QueryResult {

	/**
	 * @var callable
	 */
	private $preOutputCallback;

	/**
	 * @var int
	 */
	private $count = 0;

	/**
	 * @var array
	 */
	private $options = [
		'noparse' => true,
		'isHTML'  => true
	];

	/**
	 * @since 3.0
	 */
	public function __construct(
		private $result,
		private readonly Query $query,
		private $hasFurtherResults = false,
	) {
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ): void {
		$this->options[$key] = $value;
	}

	/**
	 * @since 3.1
	 *
	 * @param int $count
	 */
	public function setCount( $count ): void {
		$this->count = $count;
	}

	/**
	 * @since 3.1
	 *
	 * @return int
	 */
	public function getCount(): int {
		return $this->count;
	}

	/**
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function hasFurtherResults() {
		return $this->hasFurtherResults;
	}

	/**
	 * Manipulate or transform the result before the actual output.
	 *
	 * @since 3.0
	 *
	 * @param callable $preOutputCallback
	 */
	public function setPreOutputCallback( callable $preOutputCallback ): void {
		$this->preOutputCallback = $preOutputCallback;
	}

	/**
	 * @since 7.0
	 */
	public function getFormattedResult(): string|array {
		$result = $this->result;

		if ( is_callable( $this->preOutputCallback ) ) {
			$result = call_user_func_array( $this->preOutputCallback, [ $result, $this->options ] );
		}

		// Inline representation requires a different handling for results already
		// being parsed by for example a remote request.
		if ( $this->query->isEmbedded() ) {
			return [ $result, 'noparse' => $this->options['noparse'], 'isHTML' => $this->options['isHTML'] ];
		}

		return $result;
	}

	/**
	 * @since 3.0
	 *
	 * This override previously returned string|array, violating the parent's
	 * array return type contract. Use getFormattedResult() for the original
	 * string|array behavior.
	 */
	public function getResults(): array {
		$result = $this->getFormattedResult();

		return is_array( $result ) ? $result : [ $result ];
	}

}
