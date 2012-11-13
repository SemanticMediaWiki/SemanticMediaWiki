<?php

namespace SMW;

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
class Subobject {

	protected static $m_errors;

	/**
	 * Method for handling the subobject parser function.
	 *
	 * @since 1.7
	 *
	 * @param Parser $parser
	 */
	public static function render( \Parser &$parser ) {
		self::$m_errors = array();

		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		$subobjectName = str_replace( ' ', '_', trim( array_shift( $params ) ) );

		// For objects that don't come with there own idenifier, use a value
		// dependant md4 hash key
		if ( $subobjectName === '' || $subobjectName === '-' ){
			$subobjectName = '_' . hash( 'md4', implode( '|', $params ) , false );
		}

		$mainSemanticData = \SMWParseData::getSMWData( $parser );
		$subject = $mainSemanticData->getSubject();

		$diSubWikiPage = new \SMWDIWikiPage( $subject->getDBkey(),
				$subject->getNamespace(), $subject->getInterwiki(),
				$subobjectName );

		$semanticData = new \SMWContainerSemanticData( $diSubWikiPage );

		// Get property/value mapping from the parser parameter class
		foreach ( ParserParameter::singleton()->getParameters( $params ) as $property => $values ){
			foreach ( $values as $value ) {
				self::addPropertyValueToSemanticData( $property, $value, $semanticData );
			}
		}

		$propertyDi = new \SMWDIProperty('_SOBJ');
		$subObjectDi = new \SMWDIContainer( $semanticData );
		\SMWParseData::getSMWData( $parser )->addPropertyObjectValue( $propertyDi, $subObjectDi );

		return smwfEncodeMessages( self::$m_errors );
	}

	/**
	 * Add property/value to the semantic data container
	 *
	 * @since 1.7
	 *
	 * @param string $propertyName
	 * @param string $valueString
	 * @param SMWContainerSemanticData $semanticData
	 */
	protected static function addPropertyValueToSemanticData( $propertyName, $valueString, $semanticData ) {
		$propertyDv = \SMWPropertyValue::makeUserProperty( $propertyName );
		$propertyDi = $propertyDv->getDataItem();

		if ( !$propertyDi->isInverse() ) {
			$valueDv = \SMWDataValueFactory::newPropertyObjectValue( $propertyDi, $valueString,
				false, $semanticData->getSubject() );
			$semanticData->addPropertyObjectValue( $propertyDi, $valueDv->getDataItem() );

			// Take note of the error for storage (do this here and not in storage, thus avoiding duplicates).
			if ( !$valueDv->isValid() ) {
				$semanticData->addPropertyObjectValue( new \SMWDIProperty( '_ERRP' ),
					$propertyDi->getDiWikiPage() );
				self::$m_errors = array_merge( self::$m_errors, $valueDv->getErrors() );
			}
		} else {
			self::$m_errors[] = wfMessage( 'smw_noinvannot' )->inContentLanguage()->text();
		}
	}
}