<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMW\Message;
use WebRequest;
use SMWQueryProcessor as QueryProcessor;
use SMWInfolink as Infolink;

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
		$reqParameters = self::getParameterList( $request, $params );
		$printouts = [];

		// Check for q= query string, used whenever this special page calls
		// itself (via submit or plain link):
		if ( ( $q = $request->getText( 'q' ) ) !== '' ) {
			$reqParameters[] = $q;
		}

		// Parameters separated by newlines here (compatible with text-input for
		// printouts)
		if ( ( $po = $request->getText( 'po' ) ) !== '' ) {
			$printouts = explode( "\n", $po );
		}

		// Check for param strings in po (printouts), appears in some links
		// and in submits:
		$reqParameters = self::checkReqParameters(
			$request,
			$reqParameters,
			$printouts
		);

		list( $queryString, $parameters, $printouts ) =  QueryProcessor::getComponentsFromFunctionParams(
			$reqParameters,
			false
		);

		// Try to complete undefined parameter values from dedicated URL params.
		if ( !array_key_exists( 'format', $parameters ) ) {
			$parameters['format'] = 'broadtable';
		}

		$sort_count = 0;

		// First check whether the sorting options input send an
		// request data as array
		if ( $request->getArray( 'sort_num', [] ) !== array() ) {
			$sort_values = $request->getArray( 'sort_num' );

			if ( is_array( $sort_values ) ) {
				$sort = array_filter( $sort_values );
				$sort_count = count( $sort );
				$parameters['sort'] = implode( ',', $sort );
			}
		} elseif ( $request->getCheck( 'sort' ) ) {
			$parameters['sort'] = $request->getVal( 'sort', '' );
		}

		// First check whether the order options input send an
		// request data as array
		if ( $request->getArray( 'order_num', [] ) !== array()  ) {
			$order_values = $request->getArray( 'order_num' );

			// Count doesn't match means we have a order from an
			// empty (#subject) carrying around which we don't permit when
			// sorting via columns
			if ( count( $order_values ) != $sort_count ) {
				array_pop( $order_values );
			}

			if ( is_array( $order_values ) ) {
				$order = array_filter( $order_values );
				$parameters['order'] = implode( ',', $order );
			}

		} elseif ( $request->getCheck( 'order' ) ) {
			$parameters['order'] = $request->getVal( 'order', '' );
		} elseif ( !array_key_exists( 'offset', $parameters ) ) {
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

		return array( $queryString, $parameters, $printouts );
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

		return $parameterList;
	}

	private static function checkReqParameters( $request, $reqParameters, $printouts ) {

		// Add initial ? if omitted (all params considered as printouts)
		foreach ( $printouts as $param ) {
			$param = trim( $param );

			if ( ( $param !== '' ) && ( $param { 0 } != '?' ) ) {
				$param = '?' . $param;
			}

			$reqParameters[] = $param;
		}

		$parameters = array();
		unset( $reqParameters['title'] );

		// Split ?Has property=Foo|+index=1 into a [ '?Has property=Foo', '+index=1' ]
		foreach ( $reqParameters as $key => $value ) {
			if (
				( $key !== '' && $key{0} == '?' && strpos( $value, '|' ) !== false ) ||
				( is_string( $value ) && $value !== '' && $value{0} == '?' && strpos( $value, '|' ) !== false ) ) {

				foreach ( explode( '|', $value ) as $k => $val ) {
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

}
