<?php

namespace SMW\Elastic\QueryEngine;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Aggregations {

	/**
	 * @var array
	 */
	private $parameters = [];

	/**
	 * @var array
	 */
	private $subAggregations = [];

	/**
	 * @var boolean
	 */
	private $plain = false;

	/**
	 * @since 3.0
	 *
	 * @param Aggregations|array $parameters
	 */
	public function __construct( $parameters = [] ) {
		$this->parameters = $parameters;
	}

	/**
	 * @since 3.0
	 *
	 * @param Aggregations $aggregations
	 */
	public function addSubAggregations( Aggregations $aggregations ) {
		$this->subAggregations[] = $aggregations;
	}

	/**
	 * @since 3.0
	 */
	public function plain() {
		$this->plain = true;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function toArray() {

		$params = $this->params( $this->parameters );

		foreach ( $this->subAggregations as $subAggregation ) {
			foreach ( $params as $key => $value) {
				$params[$key] += $subAggregation->toArray();
			}
		}

		if ( $params === [] || $this->plain ) {
			return $params;
		}

		return [ 'aggregations' => $params ];
	}

	private function params( &$params ) {

		$aggregation = $params;

		if ( $aggregation instanceof Aggregations ) {
			$aggregation->plain();
			$params = $aggregation->toArray();
		}

		if ( !is_array( $params ) ) {
			return $params;
		}

		$p = [];

		foreach ( $params as $k => $aggregation ) {
			if ( $aggregation instanceof Aggregations ) {
				$aggregation = $this->params( $aggregation );
			}

			if ( is_string( $k ) ) {
				$p[$k] = $aggregation;
			} else {
				$p = array_merge( $p, $aggregation );
			}
		}

		return $p;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function __toString() {
		return json_encode( $this->toArray() );
	}

}
