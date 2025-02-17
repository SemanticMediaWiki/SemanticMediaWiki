<?php

namespace SMW\Query\Result;

use SMWQuery as Query;
use SMWQueryResult as QueryResult;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class StringResult extends QueryResult {

	/**
	 * @var array
	 */
	private $result = '';

	/**
	 * @var Query
	 */
	private $query;

	/**
	 * @var callable
	 */
	private $preOutputCallback;

	/**
	 * @var int
	 */
	private $count = 0;

	/**
	 * @var bool
	 */
	private $hasFurtherResults = false;

	/**
	 * @var array
	 */
	private $options = [
		'noparse' => true,
		'isHTML'  => true
	];

	/**
	 * @since 3.0
	 *
	 * @param string $result
	 */
	public function __construct( $result, Query $query, $hasFurtherResults = false ) {
		$this->result = $result;
		$this->query = $query;
		$this->hasFurtherResults = $hasFurtherResults;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {
		$this->options[$key] = $value;
	}

	/**
	 * @since 3.1
	 *
	 * @param int $count
	 */
	public function setCount( $count ) {
		$this->count = $count;
	}

	/**
	 * @since 3.1
	 *
	 * @return int
	 */
	public function getCount() {
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
	public function setPreOutputCallback( callable $preOutputCallback ) {
		$this->preOutputCallback = $preOutputCallback;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getResults() {
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

}
