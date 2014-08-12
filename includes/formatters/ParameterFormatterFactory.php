<?php

namespace SMW;

/**
 * Factory class handling parameter formatting instances
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Factory class handling parameter formatting instances
 *
 * @ingroup Formatter
 */
class ParameterFormatterFactory {

	/**
	 * Returns an ArrayFormatter instance
	 *
	 * @since 1.9
	 *
	 * @param array $rawParams
	 *
	 * @return ArrayFormatter
	 */
	public static function newFromArray( array $rawParams ) {

		if ( isset( $rawParams[0] ) && is_object( $rawParams[0] ) ) {
			array_shift( $rawParams );
		}

		//$formatter = JsonParameterFormatter::newFromArray( $rawParams );

		//if ( $formatter->isJson() ) {
		//	$instance = $formatter;
		//} else {
		//	$instance = new ParserParameterFormatter( $rawParams );
		//}

		$instance = new ParserParameterFormatter( $rawParams );

		return $instance;
	}
}
