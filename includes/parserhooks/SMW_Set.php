<?php

/**
 * Class for the 'set' parser functions.
 * @see http://semantic-mediawiki.org/wiki/Help:Properties_and_types#Silent_annotations_using_.23set
 * 
 * @since 1.5.3
 * 
 * @file SMW_Set.php
 * @ingroup SMW
 * @ingroup ParserHooks
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWSet {
	
	/**
	 * Method for handling the set parser function.
	 * 
	 * @since 1.5.3
	 * 
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		foreach ( $params as $param ) {
			$parts = explode( '=', trim( $param ), 2 );

			// Only add the property when there is both a name and a value.
			if ( count( $parts ) == 2 ) {
				SMWParseData::addProperty( $parts[0], $parts[1], false, $parser, true );
			}
		}

		return '';		
	}
	
}