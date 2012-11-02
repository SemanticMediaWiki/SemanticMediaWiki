<?php
/**
 * @file
 * @since 1.5.3
 * @ingroup SMW
 * @ingroup ParserHooks
 */

/**
 * Class for the 'ask' parser functions.
 * @see http://semantic-mediawiki.org/wiki/Help:Inline_queries#Introduction_to_.23ask
 *
 * @since 1.5.3
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWAsk {

	/**
	 * Method for handling the ask parser function.
	 *
	 * @since 1.5.3
	 *
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		global $smwgQEnabled, $smwgIQRunningNumber, $wgTitle;

		if ( $smwgQEnabled ) {
			$smwgIQRunningNumber++;

			$params = func_get_args();
			array_shift( $params ); // We already know the $parser ...

			list( $query, $params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams( $params, SMW_OUTPUT_WIKI, SMWQueryProcessor::INLINE_QUERY, false );

			$result = SMWQueryProcessor::getResultFromQuery( $query, $params, SMW_OUTPUT_WIKI, SMWQueryProcessor::INLINE_QUERY );
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
