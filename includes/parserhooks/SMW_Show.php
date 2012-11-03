<?php
/**
 * @file
 * @since 1.5.3
 * @ingroup SMW
 * @ingroup ParserHooks
 */

/**
 * Class for the 'show' parser functions.
 * @see http://semantic-mediawiki.org/wiki/Help:Inline_queries#The_.23show_parser_function
 *
 * @since 1.5.3
 *
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWShow {

	/**
	 * Method for handling the show parser function.
	 *
	 * @since 1.5.3
	 *
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		global $smwgQEnabled, $smwgIQRunningNumber, $wgTitle;

		if ( $smwgQEnabled ) {
			$smwgIQRunningNumber++;

			$rawParams = func_get_args();
			array_shift( $rawParams ); // We already know the $parser ...

			list( $query, $params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams( $rawParams, SMW_OUTPUT_WIKI, SMWQueryProcessor::INLINE_QUERY, true );

			$result = SMWQueryProcessor::getResultFromQuery( $query, $params, SMW_OUTPUT_WIKI, SMWQueryProcessor::INLINE_QUERY );

			$queryKey = hash( 'md4', implode( '|', $rawParams ) , false );
			SMWAsk::addQueryData( $queryKey, $query, $params, $parser );
		} else {
			$result = smwfEncodeMessages( array( wfMessage( 'smw_iq_disabled' )->inContentLanguage()->text() ) );
		}

		if ( !is_null( $wgTitle ) && $wgTitle->isSpecialPage() ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		} else {
			SMWOutputs::commitToParser( $parser );
		}

		return $result;
	}

}
