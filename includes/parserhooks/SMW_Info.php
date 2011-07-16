<?php

/**
 * Class for the 'info' parser functions.
 * @see ...TODO...
 * 
 * @since 1.5.3
 * 
 * @file SMW_Info.php
 * @ingroup SMW
 * @ingroup ParserHooks
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWInfo {
	
	/**
	 * Method for handling the info parser function.
	 * 
	 * @since 1.5.3
	 * 
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		$content = array_shift( $params ); // First parameter is the info message.
		$icon = array_shift( $params ); // Second parameter is icon to use or null when not provided.
		
		if ( is_null( $icon ) || $icon === '' || !in_array( $icon, array( 'info', 'warning' ) ) ) {
			$icon = 'info';
		}
		
		$result = smwfEncodeMessages( array( $content ), $icon );

		// Starting from MW 1.16, there is a more suited method available: Title::isSpecialPage
		global $wgTitle;
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