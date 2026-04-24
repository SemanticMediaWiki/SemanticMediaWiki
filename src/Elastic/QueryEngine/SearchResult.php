<?php

namespace SMW\Elastic\QueryEngine;

use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SearchResult {

	private array $errors = [];

	private ?array $results = null;

	private string $filterField = '_id';

	private array $container = [
		'info' => [],
		'scores' => [],
		'excerpts' => [],
		'count' => 0,
		'continue' => false
	];

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly array $raw = [] ) {
	}

	/**
	 * @since 3.0
	 */
	public function setErrors( array $errors ): void {
		$this->errors = $errors;
	}

	/**
	 * @since 3.0
	 */
	public function setFilterField( string $filterField ): void {
		$this->filterField = $filterField;
	}

	/**
	 * @since 3.0
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since 3.0
	 */
	public function getResults( ?int $cutoff = null ): ?array {
		if ( $this->results === null ) {
			$this->doFilterResults( $this->raw, $cutoff );
		}

		return $this->results;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function get( $key ) {
		if ( isset( $this->container[$key] ) ) {
			return $this->container[$key];
		}

		throw new InvalidArgumentException( "`$key` is an unkown key, or is not registered." );
	}

	/**
	 * @since 3.0
	 *
	 * @return int[]|string[]
	 */
	public function doFilterResults( array $results, ?int $cutoff = null ): array {
		$this->results = [];

		$this->container = [
			'info' => [],
			'scores' => [],
			'excerpts' => [],
			'count' => 0,
			'continue' => false
		];

		if ( $results === [] ) {
			return [];
		}

		$info = $results;
		$res = $this->filterByField( $results, $cutoff, $this->filterField );

		unset( $info['hits'] );
		unset( $info['_shards'] );

		$this->results = array_keys( $res );
		$info['max_score'] = $results['hits']['max_score'];
		$info['total'] = count( $res );

		$this->container['info'] = $info;
		$this->container['count'] = $info['total'];

		return $this->results;
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/6.0/_search_operations.html
	 * @return true[]
	 */
	private function filterByField( array $results, $cutoff, $field ): array {
		$res = [];
		$continue = false;

		$scores = [];
		$excerpts = [];

		$i = 0;
		$pid = null;

		if ( strpos( $field, '.' ) !== false ) {
			[ $pid, $field ] = explode( '.', $field );
		}

		foreach ( $results as $key => $value ) {

			if ( !isset( $value['hits'] ) ) {
				continue;
			}

			foreach ( $value['hits'] as $k => $v ) {

				if ( $cutoff !== null && $i >= $cutoff ) {
					$continue = true;
					break;
				}

				$ids = [];

				if ( $pid !== null && isset( $v['_source'][$pid][$field] ) ) {
					$ids = $v['_source'][$pid][$field];
				} elseif ( isset( $v['_source'][$field] ) ) {
					$ids = $v['_source'][$field];
				} elseif ( isset( $v[$field] ) ) {
					$ids = $v[$field];
				}

				$ids = (array)$ids;

				foreach ( $ids as $id ) {
					$res[$id] = true;

					if ( isset( $v['_score'] ) ) {
						$scores[$id] = $v['_score'];
					}

					if ( isset( $v['highlight'] ) ) {
						$excerpts[$id] = $v['highlight'];
					}

					$i++;
				}
			}
		}

		$this->container['scores'] = $scores;
		$this->container['count'] = 0;
		$this->container['excerpts'] = $excerpts;
		$this->container['continue'] = $continue;

		return $res;
	}

}
