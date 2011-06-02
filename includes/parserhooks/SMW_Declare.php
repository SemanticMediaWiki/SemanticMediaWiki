<?php

/**
 * Class for the 'declare' parser functions.
 * @see http://semantic-mediawiki.org/wiki/Help:Argument_declaration_in_templates
 * 
 * @since 1.5.3
 * 
 * @file SMW_Declare.php
 * @ingroup SMW
 * @ingroup ParserHooks
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWDeclare {
	
	/**
	 * Method for handling the declare parser function.
	 * 
	 * @since 1.5.3
	 * 
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param array $args
	 */
	public static function render( Parser &$parser, PPFrame $frame, array $args ) {
		if ( $frame->isTemplate() ) {
			foreach ( $args as $arg )
				if ( trim( $arg ) != '' ) {
					$expanded = trim( $frame->expand( $arg ) );
					$parts = explode( '=', $expanded, 2 );

					if ( count( $parts ) == 1 ) {
						$propertystring = $expanded;
						$argumentname = $expanded;
					} else {
						$propertystring = $parts[0];
						$argumentname = $parts[1];
					}

					$property = SMWPropertyValue::makeUserProperty( $propertystring );
					$argument = $frame->getArgument( $argumentname );
					$valuestring = $frame->expand( $argument );

					if ( $property->isValid() ) {
						$type = $property->getPropertyTypeID();

						if ( $type == '_wpg' ) {
							$matches = array();
							preg_match_all( '/\[\[([^\[\]]*)\]\]/u', $valuestring, $matches );
							$objects = $matches[1];

							if ( count( $objects ) == 0 ) {
								if ( trim( $valuestring ) != '' ) {
									SMWParseData::addProperty( $propertystring, $valuestring, false, $parser, true );
								}
							} else {
								foreach ( $objects as $object ) {
									SMWParseData::addProperty( $propertystring, $object, false, $parser, true );
								}
							}
						} elseif ( trim( $valuestring ) != '' ) {
								SMWParseData::addProperty( $propertystring, $valuestring, false, $parser, true );
						}

						// $value = SMWDataValueFactory::newPropertyObjectValue( $property->getDataItem(), $valuestring );
						// if (!$value->isValid()) continue;
					}
				}
		} else {
			// @todo Save as metadata
		}

		// Starting from MW 1.16, there is a more suited method available: Title::isSpecialPage
		global $wgTitle;
		if ( !is_null( $wgTitle ) && $wgTitle->getNamespace() == NS_SPECIAL ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		}
		else {
			SMWOutputs::commitToParser( $parser );
		}
		
		return '';		
	}
	
}