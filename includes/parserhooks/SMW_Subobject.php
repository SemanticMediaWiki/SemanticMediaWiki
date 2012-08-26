<?php

/**
 * Class for the 'subobject' parser functions.
 *
 * @since 1.7
 *
 * @file SMW_Subobject.php
 * @ingroup SMW
 * @ingroup ParserHooks
 * 
 * @author Markus Krötzsch
 */
class SMWSubobject {

	protected static $m_errors;

	/**
	 * Method for handling the subobject parser function.
	 *
	 * @since 1.7
	 *
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		self::$m_errors = array();

		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		$subobjectName = str_replace( ' ', '_', trim( array_shift( $params ) ) );

		// For objects that don't come with there own idenifier, use a value
		// dependant md4 hash key
		if ( $subobjectName === '' || $subobjectName === '-' ){
			$subobjectName = '_' . hash( 'md4', implode( '|', $params ) , false );
		}

		$mainSemanticData = SMWParseData::getSMWData( $parser );
		$subject = $mainSemanticData->getSubject();

		$diSubWikiPage = new SMWDIWikiPage( $subject->getDBkey(),
				$subject->getNamespace(), $subject->getInterwiki(),
				$subobjectName );

		$semanticData = new SMWContainerSemanticData( $diSubWikiPage );

		$previousPropertyDi = null;

		foreach ( $params as $param ) {
			$parts = explode( '=', trim( $param ), 2 );

			// Only add the property when there is both a name 
			// and a non-empty value.
			if ( count( $parts ) == 2 && $parts[1] != '' ) {
				$previousPropertyDi = self::addPropertyValueToSemanticData( $parts[0], $parts[1], $semanticData );
			} elseif ( count( $parts ) == 1 && !is_null( $previousPropertyDi ) ) {
				self::addPropertyDiValueToSemanticData( $previousPropertyDi, $parts[0], $semanticData  );
			} else {
				//self::$m_errors[] = wfMessage( 'smw_noinvannot' )->inContentLanguage()->text();
			}
		}

		$propertyDi = new SMWDIProperty('_SOBJ');
		$subObjectDi = new SMWDIContainer( $semanticData );
		SMWParseData::getSMWData( $parser )->addPropertyObjectValue( $propertyDi, $subObjectDi );

		return smwfEncodeMessages( self::$m_errors );
	}

	protected static function addPropertyValueToSemanticData( $propertyName, $valueString, $semanticData ) {
		$propertyDv = SMWPropertyValue::makeUserProperty( $propertyName );
		$propertyDi = $propertyDv->getDataItem();
		self::addPropertyDiValueToSemanticData( $propertyDi, $valueString, $semanticData  );
		return $propertyDi;
	}

	protected static function addPropertyDiValueToSemanticData( $propertyDi, $valueString, $semanticData ) {
		if ( !$propertyDi->isInverse() ) {
			$valueDv = SMWDataValueFactory::newPropertyObjectValue( $propertyDi, $valueString,
				false, $semanticData->getSubject() );
			$semanticData->addPropertyObjectValue( $propertyDi, $valueDv->getDataItem() );

			// Take note of the error for storage (do this here and not in storage, thus avoiding duplicates).
			if ( !$valueDv->isValid() ) {
				$semanticData->addPropertyObjectValue( new SMWDIProperty( '_ERRP' ),
					$propertyDi->getDiWikiPage() );
				self::$m_errors = array_merge( self::$m_errors, $valueDv->getErrors() );
			}
		} else {
			self::$m_errors[] = wfMessage( 'smw_noinvannot' )->inContentLanguage()->text();
		}
	}
}
