<?php

namespace SMW;

use Parser;
use SMWOutputs;

/**
 * Class that provides the {{#declare}} parser function
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Argument_declaration_in_templates
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.5.3
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */

/**
 * Class that provides the {{#declare}} parser function
 *
 * @ingroup ParserFunction
 */
class DeclareParserFunction {

	/**
	 * Method for handling the declare parser function.
	 *
	 * @since 1.5.3
	 *
	 * @param Parser $parser
	 * @param \PPFrame $frame
	 * @param array $args
	 */
	public static function render( Parser &$parser, \PPFrame $frame, array $args ) {
		if ( $frame->isTemplate() ) {

			$parserData = new ParserData( $parser->getTitle(), $parser->getOutput() );
			$subject = $parserData->getSemanticData()->getSubject();

			foreach ( $args as $arg )
				if ( trim( $arg ) !== '' ) {
					$expanded = trim( $frame->expand( $arg ) );
					$parts = explode( '=', $expanded, 2 );

					if ( count( $parts ) == 1 ) {
						$propertystring = $expanded;
						$argumentname = $expanded;
					} else {
						$propertystring = $parts[0];
						$argumentname = $parts[1];
					}

					$property = \SMWPropertyValue::makeUserProperty( $propertystring );
					$argument = $frame->getArgument( $argumentname );
					$valuestring = $frame->expand( $argument );

					if ( $property->isValid() ) {
						$type = $property->getPropertyTypeID();

						if ( $type == '_wpg' ) {
							$matches = array();
							preg_match_all( '/\[\[([^\[\]]*)\]\]/u', $valuestring, $matches );
							$objects = $matches[1];

							if ( count( $objects ) == 0 ) {
								if ( trim( $valuestring ) !== '' ) {
									$dataValue = DataValueFactory::getInstance()->newPropertyValue(
										$propertystring,
										$valuestring,
										false,
										$subject
									);

									$parserData->addDataValue( $dataValue );
								}
							} else {
								foreach ( $objects as $object ) {
									$dataValue = DataValueFactory::getInstance()->newPropertyValue(
										$propertystring,
										$object,
										false,
										$subject
									);

									$parserData->addDataValue( $dataValue );
								}
							}
						} elseif ( trim( $valuestring ) !== '' ) {

							$dataValue = DataValueFactory::getInstance()->newPropertyValue(
								$propertystring,
								$valuestring,
								false,
								$subject
							);

							$parserData->addDataValue( $dataValue );
						}

						// $value = \SMW\DataValueFactory::getInstance()->newPropertyObjectValue( $property->getDataItem(), $valuestring );
						// if (!$value->isValid()) continue;
					}
				}

				$parserData->updateOutput();
		} else {
			// @todo Save as metadata
		}

		global $wgTitle;
		if ( !is_null( $wgTitle ) && $wgTitle->isSpecialPage() ) {
			global $wgOut;
			SMWOutputs::commitToOutputPage( $wgOut );
		}
		else {
			SMWOutputs::commitToParser( $parser );
		}

		return '';
	}
}
