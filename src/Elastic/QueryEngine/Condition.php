<?php

namespace SMW\Elastic\QueryEngine;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Condition {

	const TYPE_MUST = 'must';
	const TYPE_SHOULD = 'should';
	const TYPE_MUST_NOT = 'must_not';
	const TYPE_FILTER = 'filter';

	/**
	 * @var array
	 */
	private $parameters = [];

	/**
	 * @var array
	 */
	private $logs = [];

	/**
	 * @var string
	 */
	private $type = 'must';

	/**
	 * @since 3.0
	 *
	 * @param Condition|array $parameters
	 */
	public function __construct( $parameters = [] ) {
		$this->parameters = $parameters;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 */
	public function type( $type ) {
		$this->type = $type;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @since 3.0
	 *
	 * @param mixed $log
	 */
	public function log( $log ) {
		$this->logs[] = $log;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getLogs() {
		return $this->logs;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function toArray() {

		$params = $this->params( $this->parameters, $this->logs );

		if ( $this->type === '' || $this->type === null || $params === [] ) {
			return $params;
		}

		return [ 'bool' => [ $this->type => $params ] ];
	}

	private function params( $params, &$logs ) {

		$condition = $params;

		if ( $condition instanceof Condition ) {
			$params = $condition->toArray();

			if ( ( $rlogs = $condition->getLogs() ) !== [] ) {
				$logs[] = $rlogs;
			}
		}

		if ( !is_array( $params ) ) {
			return $params;
		}

		foreach ( $params as $k => $condition ) {
			$params[$k] = $this->params( $condition, $logs );
		}

		return $params;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function __toString() {
		return json_encode( $this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

}
