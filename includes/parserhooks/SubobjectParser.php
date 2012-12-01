<?php

namespace SMW;
use Parser;
use SMWParseData;

/**
 * Class for the 'subobject' parser functions.
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
 * @since 1.7
 *
 * @file
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class SubobjectParser {

	/**
	 * Method for handling the subobject parser function.
	 *
	 * @since 1.7
	 *
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {

		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		$subobjectName = str_replace( ' ', '_', trim( array_shift( $params ) ) );

		// Objects that don't come with there own identifier, use a value
		// dependant md4 hash key
		if ( $subobjectName === '' || $subobjectName === '-' ){
			$subobjectName = '_' . hash( 'md4', implode( '|', $params ) , false );
		}

		$mainSemanticData = SMWParseData::getSMWData( $parser );
		$subject = $mainSemanticData->getSubject();

		$subobject = Subobject::Factory( $subject, $subobjectName );

		// Get property/value mapping from the parser parameter class
		foreach ( ParserParameter::singleton()->getParameters( $params ) as $property => $values ){
			foreach ( $values as $value ) {
				$subobject->addPropertyValue( $property, $value );
			}
		}

		SMWParseData::getSMWData( $parser )->addPropertyObjectValue( $subobject->getSubobjectProperty(), $subobject->getSubobjectContainer() );
		return smwfEncodeMessages( $subobject->getErrors() );
	}
}