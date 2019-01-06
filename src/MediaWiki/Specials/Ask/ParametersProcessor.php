<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMWInfolink as Infolink;
use SMWQueryProcessor as QueryProcessor;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class ParametersProcessor {

	/**
	 * @var integer
	 */
	private static $defaultLimit = 50;

	/**
	 * @var integer
	 */
	private static $maxInlineLimit = 500;

	/**
	 * @since 3.0
	 *
	 * @param integer $defaultLimit
	 */
	public static function setDefaultLimit( $defaultLimit ) {
		self::$defaultLimit = $defaultLimit;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $maxInlineLimit
	 */
	public static function setMaxInlineLimit( $maxInlineLimit ) {
		self::$maxInlineLimit = $maxInlineLimit;
	}

	/**
	 * @since 3.0
	 *
	 * @param WebRequest $request
	 * @param array|null $params
	 *
	 * @return string
	 */
	public static function process( WebRequest $request, $params ) {

		// First make all inputs into a simple parameter list that can again be
		// parsed into components later.
		$parameterList = self::getParameterList( $request, $params );
		$printouts = [];

		// Check for q= query string, used whenever this special page calls
		// itself (via submit or plain link):
		if ( ( $q = $request->getText( 'q' ) ) !== '' ) {
			$parameterList[] = $q;
		}

		// Parameters separated by newlines here (compatible with text-input for
		// printouts)
		if ( ( $po = $request->getText( 'po' ) ) !== '' ) {
			$printouts = explode( "\n", $po );
		}

		// Check for param strings in po (printouts), appears in some links
		// and in submits:
		$parameterList = self::checkParameterList(
			$request,
			$parameterList,
			$printouts
		);

		list( $queryString, $parameters, $printouts ) =  QueryProcessor::getComponentsFromFunctionParams(
			$parameterList,
			false
		);

		unset( $parameters['cl'] );

		// Try to complete undefined parameter values from dedicated URL params.
		if ( !array_key_exists( 'format', $parameters ) ) {
			$parameters['format'] = 'broadtable';
		}

		$sort_count = 0;
		$empty_first_sort = false;

		// First check whether the sorting options input send an
		// request data as array
		if ( ( $sort_values = $request->getArray( 'sort_num', [] ) ) !== [] ) {

			// Find out whether something like `|?sort=,Has text` was used
			if ( $sort_values[0] === '' ) {
				$empty_first_sort = true;
			}

			if ( is_array( $sort_values ) ) {

				// Filter all empty values
				$sort = array_filter( $sort_values );
				$sort_count = count( $sort );

				// Add an empty element on the first position which got filter
				// and was to prevent countless empty elements when no other sort
				// was metioned
				if ( $sort_count > 0 && $empty_first_sort ) {
					array_unshift( $sort, '' );
					$sort_count++;
				}

				$parameters['sort'] = implode( ',', $sort );
			}
		} elseif ( $request->getCheck( 'sort' ) ) {
			$parameters['sort'] = $request->getVal( 'sort', '' );
		}

		// First check whether the order options input send an
		// request data as array
		if ( ( $order_values = $request->getArray( 'order_num', [] ) ) !== [] ) {

			// Count doesn't match means we have a order from an
			// empty (#subject) carrying around which we don't permit when
			// sorting via columns
			if ( is_array( $order_values ) && count( $order_values ) != $sort_count ) {
				array_pop( $order_values );
			}

			if ( is_array( $order_values ) ) {
				$order = array_filter( $order_values );
				$parameters['order'] = implode( ',', $order );
			}

		} elseif ( $request->getCheck( 'order' ) ) {
			$parameters['order'] = $request->getVal( 'order', '' );
		} elseif ( !array_key_exists( 'order', $parameters ) ) {
			$parameters['order'] = 'asc';
			$parameters['sort'] = '';
		}

		if ( !array_key_exists( 'offset', $parameters ) ) {
			$parameters['offset'] = $request->getVal( 'offset', 0 );
		}

		if ( !array_key_exists( 'limit', $parameters ) ) {
			$parameters['limit'] = $request->getVal( 'limit', self::$defaultLimit );
		}

		$parameters['limit'] = min( $parameters['limit'], self::$maxInlineLimit );

		return [ $queryString, $parameters, $printouts ];
	}

	private static function getParameterList( $request, $params ) {

		// Called from wiki, get all parameters
		if ( !$request->getCheck( 'q' ) ) {
			return Infolink::decodeParameters( $params, true );
		}

		// Called by own Special, ignore full param string in that case
		$query_val = $request->getVal( 'p' );

		if ( !empty( $query_val ) ) {
			// p is used for any additional parameters in certain links.
			$parameterList = Infolink::decodeParameters( $query_val, false );
		} else {
			$query_values = $request->getArray( 'p' );

			if ( is_array( $query_values ) ) {
				foreach ( $query_values as $key => $val ) {
					if ( empty( $val ) ) {
						unset( $query_values[$key] );
					}
				}
			}

			// p is used for any additional parameters in certain links.
			$parameterList = Infolink::decodeParameters( $query_values, false );
		}

		foreach ( $parameterList as $key => $value ) {
			// Concatenate checkbox values into a simple comma separated list
			if ( is_array( $value ) ) {
				$parameterList[$key] = implode( ',', $value );
			}
		}

		return $parameterList;
	}

	private static function checkParameterList( $request, $parameterList, $printouts ) {

		// Add initial ? if omitted (all params considered as printouts)
		foreach ( $printouts as $param ) {
			$param = trim( $param );

			if ( ( $param !== '' ) && ( $param { 0 } != '?' ) ) {
				$param = '?' . $param;
			}

			$parameterList[] = $param;
		}

		$parameters = [];
		unset( $parameterList['title'] );

		// MW's internal token
		unset( $parameterList['wpEditToken'] );

		foreach ( $parameterList as $key => $value ) {
			if ( self::hasPipe( $key, $value ) ) {

				// #3523 `?TestAsk=[[Foo|Bar]]` replace `|`
				if ( self::hasLink( $value ) ) {
					$value = self::replace( '|', '0x7C', $value );
				}

				// #1407 Split: `?Has property=Foo|+index=1` into a [ '?Has property=Foo', '+index=1' ])
				foreach ( explode( '|', $value ) as $k => $val ) {

					// #3523 `?TestAsk=[[Foo|Bar]]|+index=1` decode
					// the part that contains `0x7C`
					if ( strpos( $val, '0x7C' ) !== false ) {
						$val = self::replace( '0x7C', '|', $val );
					}

					$parameters[] = $k == 0 && $key{0} == '?' ? $key . '=' . $val : $val;
				}
			} elseif ( is_string( $key ) ) {
				$parameters[$key] = $value;
			} else {
				$parameters[] = $value;
			}
		}

		return $parameters;
	}

	private static function hasPipe( $key, $value ) {

		if ( $key !== '' && $key{0} == '?' && strpos( $value, '|' ) !== false ) {
			return true;
		}

		if ( is_string( $value ) && $value !== '' && $value{0} == '?' && strpos( $value, '|' ) !== false ) {
			return true;
		}

		return false;
	}

	private static function hasLink( $value ) {
		return strpos( $value, '[[' ) !== false && strpos( $value, ']]' ) !== false ;
	}

	private static function replace( $source, $target, $value ) {
		return preg_replace_callback(
			'/\[\[([^\[\]]*)\]\]/xu',
			function( array $matches ) use ( $source, $target ) {
				return str_replace( [ $source ], [ $target ], $matches[0] );
			},
			$value
		);
	}

}
