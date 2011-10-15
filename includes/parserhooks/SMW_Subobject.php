<?php

/**
 * Class for the 'subobject' parser functions.
 *
 * @since 1.6.3
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
	 * @since 1.6.3
	 * 
	 * @param Parser $parser
	 */
	public static function render( Parser &$parser ) {
		self::$m_errors = array();

		$params = func_get_args();
		array_shift( $params ); // We already know the $parser ...

		$semanticData = new SMWContainerSemanticData();
		$propertyName = null;
		foreach ( $params as $param ) {
			if ( is_null( $propertyName ) ) {
				$propertyName = trim( $param );
			} else {
				$parts = explode( '=', trim( $param ), 2 );

				// Only add the property when there is both a name and a value.
				if ( count( $parts ) == 2 ) {
					self::addPropertyValueToSemanticData( $parts[0], $parts[1], $semanticData );
				}
			}
		}

		$propertyDv = SMWPropertyValue::makeUserProperty( $propertyName );
		$propertyDi = $propertyDv->getDataItem();

		if ( !$propertyDi->isInverse() ) {
			try {
				$subObjectDi = new SMWDIContainer( $semanticData );
				SMWParseData::getSMWData( $parser )->addPropertyObjectValue( $propertyDi, $subObjectDi );
			} catch ( SMWDataItemException $e ) {
				SMWParseData::getSMWData( $parser )->addPropertyObjectValue( new SMWDIProperty( '_ERRP' ), $propertyDi->getDiWikiPage() );
			}
		} else {
			self::$m_errors[] = wfMsgForContent( 'smw_noinvannot' );
		}

		return smwfEncodeMessages( self::$m_errors );
	}

	protected static function addPropertyValueToSemanticData( $propertyName, $valueString, $semanticData ) {
		$propertyDv = SMWPropertyValue::makeUserProperty( $propertyName );
		$propertyDi = $propertyDv->getDataItem();

		if ( !$propertyDi->isInverse() ) {
			$valueDv = SMWDataValueFactory::newPropertyObjectValue( $propertyDi, $valueString );
			$semanticData->addPropertyObjectValue( $propertyDi, $valueDv->getDataItem() );
			// Take note of the error for storage (do this here and not in storage, thus avoiding duplicates).
			if ( !$valueDv->isValid() ) {
				$semanticData->addPropertyObjectValue( new SMWDIProperty( '_ERRP' ), $propertyDi->getDiWikiPage() );
				self::$m_errors = array_merge( self::$m_errors, $valueDv->getErrors() );
			}
		} else {
			self::$m_errors[] = wfMsgForContent( 'smw_noinvannot' );
		}
	}
	
}