<?php

namespace SMW\Query\Result;

use SMWQueryResult as QueryResult;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
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
	public function __construct( $result = '', Query $query ) {
		$this->result = $result;
		$this->query = $query;
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

		if ( is_callable( $this->preOutputCallback )  ) {
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
