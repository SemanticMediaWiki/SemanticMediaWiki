<?php

namespace SMW\Elastic\QueryEngine;

use SMW\DataTypeRegistry;
use SMW\DIProperty;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FieldMapper {

	const TYPE_MUST = 'must';
	const TYPE_SHOULD = 'should';
	const TYPE_MUST_NOT = 'must_not';
	const TYPE_FILTER = 'filter';

	/**
	 * @var boolean
	 */
	private $isCompatMode = true;

	/**
	 * @since 3.0
	 *
	 * @param boolean $isCompatMode
	 */
	public function isCompatMode( $isCompatMode ) {
		$this->isCompatMode = $isCompatMode;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 *
	 * @return string
	 */
	public static function getPID( $id ) {
		return "P:$id";
	}

	/**
	 * @since 3.0
	 *
	 * @param DIProperty $property
	 *
	 * @return string
	 */
	public static function getFieldType( DIProperty $property ) {
		return str_replace( [ '_' ], [ '' ], DataTypeRegistry::getInstance()->getFieldType( $property->findPropertyValueType() ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param DIProperty $property
	 * @param string $affix
	 *
	 * @return string
	 */
	public static function getField( DIProperty $property, $affix = 'Field' ) {
		return self::getFieldType( $property ) . $affix;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function isPhrase( $value = '' ) {
		return $value{0} === '"' && substr( $value, -1 ) === '"';
	}

	/**
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public static function hasWildcard( $value = '' ) {
		return strpos( $value, '*' ) !== false && strpos( $value, '\*' ) === false;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $value
	 *
	 * @return boolean
	 */
	public function containsReservedChar( $value ) {

		$reservedChars = [
			'+', '-', '=', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '\\', '//'
		];

		foreach ( $reservedChars as $char ) {
			if ( strpos( $value, $char ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @see https://stackoverflow.com/questions/9796470/random-order-pagination-elasticsearch
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html
	 *
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function function_score_random( $query, $boost = 5 ) {
		return [
			'function_score' => [
				'query' => $query,
				"boost" => $boost,
				"random_score" => new \stdClass(),
				"boost_mode"=> "multiply"
			]
		];
	}

	/**
	 * @since 3.0
	 *
	 * @param array $results
	 * @param array $params
	 *
	 * @return []
	 */
	public function field_filter( $field, $params ) {

		$idList = [];

		foreach ( $params as $key => $value ) {

			if ( $key === $field ) {
				return $value;
			}

			if ( !is_array( $value ) ) {
				return [];
			}

			return $this->field_filter( $field, $value );
		}

		return $idList;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 * @param array $params
	 *
	 * @return array
	 */
	public function bool( $type, $params ) {
		return [ 'bool' => [ $type => $params ] ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-dsl-constant-score-query.html
	 * @see https://www.elastic.co/guide/en/elasticsearch/guide/current/filter-caching.html
	 *
	 * @since 3.0
	 *
	 * @param string $type
	 * @param array $params
	 *
	 * @return array
	 */
	public function constant_score( $params ) {
		return [ 'constant_score' => [ 'filter' => $params ] ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-filter-context.html
	 *
	 * "... filter context, a query clause ... is a simple Yes or No — no scores
	 * are calculated. Filter context is mostly used for filtering structured
	 * data ...", " ... used filters will be cached automatically ..."
	 *
	 * @since 3.0
	 *
	 * @param string $type
	 * @param array $params
	 *
	 * @return array
	 */
	public function filter( $params ) {
		return [ 'filter' => $params ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-dsl-geo-distance-query.html
	 *
	 * @since 3.0
	 *
	 * @param string $type
	 * @param array $params
	 *
	 * @return array
	 */
	public function geo_distance( $field, $coordinates, $distance ) {
		return [ 'geo_distance' => [ 'distance' => $distance, $field => $coordinates ] ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-dsl-geo-bounding-box-query.html
	 *
	 * @since 3.0
	 *
	 * @param string $type
	 * @param array $params
	 *
	 * @return array
	 */
	public function geo_bounding_box( $field, $top, $left, $bottom, $right ) {
		return [ 'geo_bounding_box' => [ $field => [ 'top' => $top , 'left' => $left, 'bottom' => $bottom, 'right' => $right ] ] ];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function range( $field, $value, $comp = '' ) {

		$comparators = [
			SMW_CMP_LESS => 'lt',
			SMW_CMP_GRTR => 'gt',
			SMW_CMP_LEQ  => 'lte',
			SMW_CMP_GEQ  => 'gte'
		];

		return [
			[ 'range' => [ "$field" => [ $comparators[$comp] => $value ] ] ]
		];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function match( $field, $value, $operator = 'or' ) {

		if ( is_array( $field ) ) {
			return $this->multi_match( $field, $value );
		}

		// Is it a phrase match as in "Foo bar"?
		if ( $value !=='' && $value{0} === '"' && substr( $value, -1 ) === '"' ) {
			return $this->match_phrase( $field, trim( $value, '"' ) );
		}

		if ( $operator !== 'or' ) {
			return [
				[ 'match' => [ "$field" => [ 'query' => $value, 'operator' => $operator ] ] ]
			];
		}

		return [
			[ 'match' => [ $field => $value ] ]
		];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
	 *
	 * - `best_fields` Finds documents which match any field, but uses the _score
	 *   from the best field
	 * - `most_fields`  Finds documents which match any field and combines the
	 *   _score from each field
	 * - `cross_fields`  Treats fields with the same analyzer as though they were
	 *   one big field and looks for each word in any field
	 * - `phrase` Runs a match_phrase query on each field and combines the _score
	 *   from each field
	 * - `phrase_prefix` Runs a match_phrase_prefix query on each field and
	 *   combines the _score from each field
	 *
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 * @param array $params
	 *
	 * @return string
	 */
	public function multi_match( $fields, $value, array $params = [] ) {

		//return $this->multi_match( $field, trim( $value, '"' ) , [ "type" => "phrase" ] );

		if ( strpos( $value, '"' ) !== false ) {
			$value = trim( $value, '"' );
			$params = [ "type" => "phrase" ];

			if ( strpos( $value, '*' ) !== false ) {
				$value = trim( $value, '*' );
				$params = [ "type" => "phrase_prefix" ];
			}
		}

		return [
			[ 'multi_match' => [ 'fields' => $fields, 'query' => $value ] + $params ]
		];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 * @param array $params
	 *
	 * @return string
	 */
	public function match_phrase( $field, $value, array $params = [] ) {

		if ( strpos( $value, '*' ) !== false ) {
			return [
				'match_phrase_prefix' => [ "$field" => trim( $value, '*' ) ]
			];
		}

		if ( $params !== [] ) {
			return [
				[ 'match_phrase' => [ "$field" => [ 'query' => $value ] + $params ] ]
			];
		}

		return [
			[ 'match_phrase' => [ "$field" => $value ] ]
		];
	}

	/**
	 * In compat mode we try to guess and normalize the query string and hereby
	 * attempt to make the search execution to match closely the SMW SQL behaviour
	 * which comes at a cost that certain ES specific  constructs ((), {} etc.)
	 * cannot be used when the `compat.mode` is enabled.
	 *
	 * @since 3.0
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function query_string_compat( $value, array $params = [] ) {

		$wildcard = '';
	//	$params = [];

		// Reserved characters are: + - = && || > < ! ( ) { } [ ] ^ " ~ * ? : \ /
		// Failed the search with: {"error":{"root_cause":[{"type":"query_shard_exception","reason":"Failed to parse query [*{*]
		// Use case: TQ0102
		if ( $this->containsReservedChar( $value ) ) {
			$value = str_replace(
				[ '\\', '{', '}', '(', ')', '[', ']', '^', '=', '|', '/' , ':' ],
				[ "\\\\", "\{", "\}", "\(", "\)", "\[", "\]", "\^", "\=", "\|", "\/", "\:" ],
				$value
			);
		}

		// Intended phrase or a single " char?
		// Use case: TQ0102#13
		if ( strpos( $value, '"' ) !== false && substr_count( $value, '"' ) < 2 ) {
			$value = str_replace( '"' , '\"', $value );
		} elseif ( substr_count( $value, '"' ) == 2 && strpos( $value, '~' ) !== false ) {
			// https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#_fuzziness
			// [[Has page::phrase:some text~2]] as "some text"~2
			list( $value, $fuzziness ) = explode( '~', $value );
			$value = "$value\"~" . str_replace( '"', '', $fuzziness );
		}

		// In this section we add modifiers to closely emulate the "known" SMW
		// query behaviour of matching a string by SQL in terms of %foo% ...

		// Uses Boolean parameters? avoid further guessing on the behalf of the
		// query operation as in `[[Has text::~+MariaDB -database]]`
		if ( strpos( $value, '+' ) !== false || strpos( $value, '-' ) !== false || strpos( $value, '"' ) !== false ) {
			// Use case: `[[Has text::~sear*, -elas*]]`
			// The user added those parameters by themselves
			// Avoid comma separated strings
			$value = str_replace( ',' , '', $value );
		} elseif ( strpos( $value, ' ' ) !== false ) {
			// Use case: `[[Has text::~some tex*]]
			// Intention is to search for `some` AND `tex*`
			$value = str_replace( [ ' ', '/*' ], [ ' +', '/ *' ], $value );
		} elseif ( strpos( $value, '/' ) !== false ) {
			// Use case: [[~Example/0608/*]]
			// Somehow using the input as-is returns all sorts of matches mostly
			// due to `/` being reserved hence split the string and create a
			// conjunction using ES boolean expression `+` (AND) as in `Example`
			// AND `0608*`
			// T:Q0908 `http://example.org/some_title_with_a_value` becomes
			// `example.org +some +title +with +a +value`
			$value = str_replace( [ '\/', '/', ' +*', '_' ], [ '/',  ' +', '*',  ' +' ], $value );
		} else {

			// `_` in MediaWiki represents a space therefore replace it with an
			// `+` (AND)
			$value = str_replace( [ '_' ], [ ' ' ], $value );

			// Use case: `[[Has text::~foo*]]`, `[[Has text::~foo]]`
			// - add Boolean + which translates into "must be present"
			if ( $value{0} !== '*' ) {
				$value = "+$value";
			}

			// Use case: `[[Has text::~foo bar*]]`
			if ( strpos( $value, ' ' ) !== false && substr( $value, -1 ) === '*' ) {
			//	$value = substr( $value, 0, -1 );
				$wildcard = '*';
				$params[ 'analyze_wildcard'] = true;
			}

			// Use case: `[[Has text::~foo bar*]]
			// ... ( and ) signifies precedence
			// ... " wraps a number of tokens to signify a phrase for searching
			if ( strpos( $value, ' ' ) !== false && strpos( $value, '"' ) === false ) {
			//	$value = "\"($value)\"$wildcard";
			}
		}

		// Force all terms to be required by ...
		// $params[ 'default_operator'] = 'AND';

		// Disable fuzzy transpositions (ab → ba)
		// $params[ 'fuzzy_transpositions'] = false;

		return [ $value, $params ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
	 * @since 3.0
	 *
	 * @param string|array $fields
	 * @param mixed $value
	 * @param array $params
	 *
	 * @return string
	 */
	public function query_string( $fields, $value, array $params = [] ) {

		if ( $this->isCompatMode ) {
			list( $value, $params ) = $this->query_string_compat( $value, $params );
		}

		if ( !is_array( $fields ) ) {
			$fields = [ $fields ];
		}

		return [
			'query_string' => [ 'fields' => $fields, 'query' => $value ] + $params
		];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-ids-query.html
	 * @since 3.0
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function ids( $value ) {
		return [ 'ids' => [ "values" => $value ] ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-dsl-term-query.html
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function term( $field, $value ) {
		return [ 'term' => [ "$field" => $value ] ];
	}

	/**
	 * Filters documents that have fields that match any of the provided terms
	 * (not analyzed).
	 *
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-dsl-term-query.html
	 *
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function terms( $field, $value ) {

		if ( !is_array( $value ) ) {
			$value = [ $value ];
		}

		return [ 'terms' => [ "$field" => $value ] ];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function wildcard( $field, $value ) {
		return [ 'wildcard' => [ "$field" => $value ] ];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $field
	 *
	 * @return string
	 */
	public function exists( $field ) {
		return [ 'exists' => [ "field" => "$field" ] ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.2/search-aggregations.html
	 *
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function aggs( $name, $params ) {
		return [ 'aggregations' => [ "$name" => $params ] ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.2/search-aggregations-bucket-terms-aggregation.html
	 *
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function aggs_terms( $key, $field, $params = [] ) {
		return [ $key => [ 'terms' => [ "field" => $field ] + $params ] ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.2/search-aggregations-bucket-significantterms-aggregation.html
	 *
	 * Aggregation based on terms that have undergone a significant change in
	 * popularity measured between a foreground and background set.
	 *
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function aggs_significant_terms( $key, $field, $params = [] ) {
		return [ $key => [ 'significant_terms' => [ "field" => $field ] + $params ] ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.2/search-aggregations-bucket-histogram-aggregation.html
	 *
	 * A multi-bucket values source based aggregation that can be applied on
	 * numeric values extracted from the documents. It dynamically builds fixed
	 * size (a.k.a. interval) buckets over the values.
	 *
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function aggs_histogram( $key, $field, $interval ) {
		return [ $key => [ 'histogram' => [ "field" => $field, 'interval' => $interval ] ] ];
	}

	/**
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.2/search-aggregations-bucket-datehistogram-aggregation.html
	 *
	 * A multi-bucket aggregation similar to the histogram except it can only be
	 * applied on date values.
	 *
	 * @since 3.0
	 *
	 * @param string $field
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function aggs_date_histogram( $key, $field, $interval ) {
		return [ $key => [ 'date_histogram' => [ "field" => $field, 'interval' => $interval ] ] ];
	}

	/**
	 * @since 3.0
	 *
	 * @param Condition|array $params
	 * @param string $replacement
	 * @param array $hierarchy
	 *
	 * @return string
	 */
	public function hierarchy( $params, $replacement, $hierarchy = [] ) {

		if ( $hierarchy === [] ) {
			return $params;
		}

		$str = is_array( $params ) ? json_encode( $params ) : (string)$params;

		// P:, or iP:
		list( $prefix, $id ) = explode( ':', $replacement );

		$params = [];
		$params[] = json_decode( $str, true );

		foreach ( $hierarchy as $key ) {
			// Quick and dirty to avoid iterating over an array and find a
			// possible replacement without knowing the specific structure of
			// an array
			//
			// Adding . to make it less likely that we replace a user value that
			// appears as `P:42`
			$params[] = json_decode( str_replace( "$replacement.", "$prefix:$key.", $str ), true );
		}

		$condition = new Condition( $params );

		// Hierarchy as simple list of disjunctive (should) conditions where any
		// of the condition is allowed to return a result. For example, a hierarchy
		// defined as `Foo <- Foo1 <- Foo2` would be represented in terms of
		// `[[Foo::bar]] OR [[Foo1::bar]] OR [[Foo2::bar]]`
		$condition->type( 'should' );

		return new Condition( $condition );
	}

}
