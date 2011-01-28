<?php

/**
 * Class for the 'ask' parser functions.
 * @see http://semantic-mediawiki.org/wiki/Help:Inline_queries#Introduction_to_.23ask
 * 
 * @since 1.5.3
 * 
 * @file SMW_Ask.php
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

			$result = SMWQueryProcessor::getResultFromFunctionParams( $params, SMW_OUTPUT_WIKI );
		} else {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$result = smwfEncodeMessages( array( wfMsgForContent( 'smw_iq_disabled' ) ) );
		}

		// Starting from MW 1.16, there is a more suited method available: Title::isSpecialPage
		if ( !is_null( $wgTitle ) && $wgTitle->getNamespace() == NS_SPECIAL ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		}
		else {
			SMWOutputs::commitToParser( $parser );
		}

		return $result;		
	}
	
}