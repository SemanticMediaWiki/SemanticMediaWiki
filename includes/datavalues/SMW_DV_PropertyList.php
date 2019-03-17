<?php

use SMW\Localizer;

/**
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements special processing suitable for defining the list
 * of properties that is required for SMWRecordValue objects. The input is a
 * plain semicolon-separated list of property names, optionally with the
 * namespace prefix.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWPropertyListValue extends SMWDataValue {
	/**
	 * List of properte data items that are stored.
	 * @var array of SMWDIProperty
	 */
	protected $m_diProperties;

	protected function parseUserValue( $value ) {

		$this->m_diProperties = [];
		$stringValue = '';

		$valueList = preg_split( '/[\s]*;[\s]*/u', trim( $value ) );
		$propertyNamespace = Localizer::getInstance()->getNamespaceTextById(
			SMW_NS_PROPERTY
		);

		foreach ( $valueList as $propertyName ) {
			$propertyNameParts = explode( ':', $propertyName, 2 );
			if ( count( $propertyNameParts ) > 1 ) {
				$namespace = smwfNormalTitleText( $propertyNameParts[0] );

				// Is it a registered namespace? Or just a property with a `:`
				// divider such as `foaf:name`?
				if ( Localizer::getInstance()->getNamespaceIndexByName( $namespace ) ) {
					$propertyName = $propertyNameParts[1];

					if ( $namespace != $propertyNamespace ) {
						$this->addErrorMsg( [ 'smw_wrong_namespace', $propertyNamespace ] );
					}
				}
			}

			$propertyName = smwfNormalTitleText( $propertyName );

			try {
				$diProperty = SMW\DIProperty::newFromUserLabel( $propertyName );
			} catch ( SMWDataItemException $e ) {
				$diProperty = new SMW\DIProperty( 'Error' );
				$this->addErrorMsg( [ 'smw_noproperty', $propertyName ] );
			}

			$this->m_diProperties[] = $diProperty;
			$stringValue .= ( $stringValue ? ';' : '' ) . $diProperty->getKey();
		}

		$this->m_dataitem = new SMWDIBlob( $stringValue );
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 *
	 * @param $dataitem SMWDataItem
	 *
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {

		if ( !$dataItem instanceof SMWDIBlob ) {
			return false;
		}

		$this->m_dataitem = $dataItem;
		$this->m_diProperties = [];

		foreach ( explode( ';', $dataItem->getString() ) as $propertyKey ) {
			$property = null;

			try {
				$property = new SMW\DIProperty( $propertyKey );
			} catch ( SMWDataItemException $e ) {
				$property = new SMW\DIProperty( 'Error' );
				$this->addErrorMsg( [ 'smw-datavalue-propertylist-invalid-property-key', $dataItem->getString(), $propertyKey ] );
			}

			if ( $property instanceof SMWDIProperty ) {
				 // Find a possible redirect
				$this->m_diProperties[] = $property->getRedirectTarget();
			}
		}

		$this->m_caption = false;

		return true;
	}

	public function getShortWikiText( $linked = null ) {
		return ( $this->m_caption !== false ) ?  $this->m_caption : $this->makeOutputText( 2, $linked );
	}

	public function getShortHTMLText( $linker = null ) {
		return ( $this->m_caption !== false ) ? $this->m_caption : $this->makeOutputText( 3, $linker );
	}

	public function getLongWikiText( $linked = null ) {
		return $this->makeOutputText( 2, $linked );
	}

	public function getLongHTMLText( $linker = null ) {
		return $this->makeOutputText( 3, $linker );
	}

	public function getWikiValue() {
		return $this->makeOutputText( 4 );
	}

	public function getPropertyDataItems() {
		return $this->m_diProperties;
	}

////// Internal helper functions

	protected function makeOutputText( $type, $linker = null ) {
		if ( !$this->isValid() ) {
			return ( ( $type == 0 ) || ( $type == 1 ) ) ? '' : $this->getErrorText();
		}
		$result = '';
		$sep = ( $type == 4 ) ? '; ' : ', ';
		foreach ( $this->m_diProperties as $diProperty ) {
			if ( $result !== '' ) {
				$result .= $sep;
			}
			$propertyValue = \SMW\DataValueFactory::getInstance()->newDataValueByItem( $diProperty, null );
			$result .= $this->makeValueOutputText( $type, $propertyValue, $linker );
		}
		return $result;
	}

	protected function makeValueOutputText( $type, $propertyValue, $linker ) {
		switch ( $type ) {
			case 0:
			return $propertyValue->getShortWikiText( $linker );
			case 1:
			return $propertyValue->getShortHTMLText( $linker );
			case 2:
			return $propertyValue->getLongWikiText( $linker );
			case 3:
			return $propertyValue->getLongHTMLText( $linker );
			case 4:
			return $propertyValue->getWikiValue();
		}
	}
}
