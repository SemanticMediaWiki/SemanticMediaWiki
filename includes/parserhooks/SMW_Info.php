<?php

/**
 * Class for the 'info' parser functions.
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

		$content = array_shift( $params ); // Use only first parameter, ignore the rest (may get meaning later).
		$result = smwfEncodeMessages( array( $content ), 'info' );

		SMWOutputs::commitToParser( $parser );
		return $result;		
	}
	
}