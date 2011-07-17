<?php
/**
 * @file
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
		global $wgContLang;

		$this->m_diProperties = array();
		$stringValue = '';
		$valueList = preg_split( '/[\s]*;[\s]*/u', trim( $value ) );
		foreach ( $valueList as $propertyName ) {
			$propertyNameParts = explode( ':', $propertyName, 2 );
			if ( count( $propertyNameParts ) > 1 ) {
				$namespace = smwfNormalTitleText( $propertyNameParts[0] );
				$propertyName = $propertyNameParts[1];
				$propertyNamespace = $wgContLang->getNsText( SMW_NS_PROPERTY );
				if ( $namespace != $propertyNamespace ) {
					smwfLoadExtensionMessages( 'SemanticMediaWiki' );
					$this->addError( wfMsgForContent( 'smw_wrong_namespace', $propertyNamespace ) );
				}
			}

			$propertyName = smwfNormalTitleText( $propertyName );

			try {
				$diProperty = SMWDIProperty::newFromUserLabel( $propertyName );
			} catch ( SMWDataItemException $e ) {
				$diProperty = new SMWDIProperty( 'Error' );
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$this->addError( wfMsgForContent( 'smw_noproperty', $propertyName ) );
			}

			$this->m_diProperties[] = $diProperty;
			$stringValue .= ( $stringValue ? ';' : '' ) . $diProperty->getKey();
		}

		try {
			$this->m_dataitem = new SMWDIString( $stringValue );
		} catch ( SMWStringLengthException $e ) {
			$this->m_dataitem = new SMWDIString( 'Error' );
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError( wfMsgForContent( 'smw_maxstring', $stringValue ) );
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * 
	 * @param $dataitem SMWDataItem
	 * 
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_STRING ) {
			$this->m_dataitem = $dataItem;
			$this->m_diProperties = array();

			foreach ( explode( ';', $dataItem->getString() ) as $propertyKey ) {
				try {
					$this->m_diProperties[] = new SMWDIProperty( $propertyKey );
				} catch ( SMWDataItemException $e ) {
					$this->m_diProperties[] = new SMWDIProperty( 'Error' );
					smwfLoadExtensionMessages( 'SemanticMediaWiki' );
					$this->addError( wfMsgForContent( 'smw_parseerror' ) );
				}
			}

			$this->m_caption = false;

			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linked = null ) {
		return  ( $this->m_caption !== false ) ?  $this->m_caption : $this->makeOutputText( 2, $linked );
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
			if ( $result != '' ) $result .= $sep;
			$propertyValue = SMWDataValueFactory::newDataItemValue( $diProperty, null );
			$result .= $this->makeValueOutputText( $type, $propertyValue, $linker );
		}
		return $result;
	}

	protected function makeValueOutputText( $type, $propertyValue, $linker ) {
		switch ( $type ) {
			case 0: return $propertyValue->getShortWikiText( $linker );
			case 1: return $propertyValue->getShortHTMLText( $linker );
			case 2: return $propertyValue->getLongWikiText( $linker );
			case 3: return $propertyValue->getLongHTMLText( $linker );
			case 4: return $propertyValue->getWikiValue();
		}
	}

}