<?php

namespace SMW;

use Parser;
use SMWParseData;
use SMWOutputs;

/**
 * Class that provides the {{#declare}} parser function
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @see http://semantic-mediawiki.org/wiki/Help:Argument_declaration_in_templates
 *
 * @since 1.5.3
 *
 * @file
 * @ingroup SMW
 * @ingroup ParserFunction
 *
 * @licence GNU GPL v2+
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */

/**
 * Class that provides the {{#declare}} parser function
 *
 * @ingroup SMW
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
									SMWParseData::addProperty( $propertystring, $valuestring, false, $parser, true );
								}
							} else {
								foreach ( $objects as $object ) {
									SMWParseData::addProperty( $propertystring, $object, false, $parser, true );
								}
							}
						} elseif ( trim( $valuestring ) !== '' ) {
								SMWParseData::addProperty( $propertystring, $valuestring, false, $parser, true );
						}

						// $value = SMWDataValueFactory::newPropertyObjectValue( $property->getDataItem(), $valuestring );
						// if (!$value->isValid()) continue;
					}
				}
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
