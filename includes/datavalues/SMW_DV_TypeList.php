<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements special processing suitable for defining the list
 * of types that is required for SMWRecordValue objects. The input is a plain
 * semicolon-separated list of type labels.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTypeListValue extends SMWDataValue {

	/**
	 * List of type data value objects corresponding to the stored data.
	 * @var array of SMWTypesValue
	 */
	protected $m_typevalues;

	protected function parseUserValue( $value ) {
		$this->m_typevalues = array();
		$types = explode( ';', $value );
		foreach ( $types as $type ) {
			$tval = SMWDataValueFactory::newTypeIDValue( '__typ', $type );
			$this->m_typevalues[] = $tval;
		}
		$this->setDataItemFromTypeValues();
	}

	protected function parseDBkeys( $args ) {
		$this->m_typevalues = array();
		$ids = explode( ';', $args[0] );
		foreach ( $ids as $id ) {
			$this->m_typevalues[] = SMWDataValueFactory::newTypeIDValue( '__typ', SMWDataValueFactory::findTypeLabel( $id ) );
		}
		$this->m_caption = false;
		$this->setDataItemFromTypeValues();
	}

	/**
	 * @see SMWDataValue::setDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	public function setDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_STRING ) {
			$this->m_dataitem = $dataItem;
			$this->m_typevalues = array();
			$ids = explode( ';', $dataItem->getString() );
			foreach ( $ids as $id ) {
				$this->m_typevalues[] = SMWDataValueFactory::newTypeIDValue( '__typ', SMWDataValueFactory::findTypeLabel( $id ) );
			}
			$this->m_caption = false;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * The special feature of this implementation of getDBkeys is that it uses
	 * internal type ids to obtain a short internal value for the type. Note
	 * that this also given language independence but that this is of little
	 * use: if the value is given in another language in the wiki, then either
	 * the value is still understood, or the language-independent database
	 * entry is only of temporary use until someone edits the respective page.
	 */
	protected function setDataItemFromTypeValues() {
		$stringvalue = '';
		foreach ( $this->m_typevalues as $tv ) {
			if ( $stringvalue != '' ) $stringvalue .= ';';
			$stringvalue .= $tv->getDBkey();
		}
		try {
			$this->m_dataitem = new SMWDIString( $stringvalue, $this->m_typeid );
		} catch ( SMWStringLengthException $e ) {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError( wfMsgForContent( 'smw_maxstring', '"' . $stringvalue . '"' ) );
			$this->m_dataitem = new SMWDIString( 'ERROR', $this->m_typeid );
		}
	}

	public function getDBkeys() {
		return array( $this->m_dataitem->getString() );
	}

	public function getSignature() {
		return 't';
	}

	public function getValueIndex() {
		return 0;
	}

	public function getLabelIndex() {
		return 0;
	}

	public function getShortWikiText( $linked = null ) {
		return  ( $this->m_caption !== false ) ?  $this->m_caption : $this->makeOutputText( 0, $linked );
	}

	public function getShortHTMLText( $linker = null ) {
		return ( $this->m_caption !== false ) ? $this->m_caption : $this->makeOutputText( 1, $linker );
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

	public function getTypeValues() {
		$this->unstub();
		return $this->m_typevalues;
	}

////// Internal helper functions

	protected function makeOutputText( $type = 0, $linker = null ) {
		if ( !$this->isValid() ) {
			return ( ( $type == 0 ) || ( $type == 1 ) ) ? '' : $this->getErrorText();
		}
		$result = '';
		$sep = ( $type == 4 ) ? '; ' : ', ';
		foreach ( $this->m_typevalues as $tv ) {
			if ( $result != '' ) $result .= $sep;
			$result .= $this->makeValueOutputText( $type, $tv, $linker );
		}
		return $result;
	}

	protected function makeValueOutputText( $type, $datavalue, $linker ) {
		switch ( $type ) {
			case 0: return $datavalue->getShortWikiText( $linker );
			case 1: return $datavalue->getShortHTMLText( $linker );
			case 2: return $datavalue->getLongWikiText( $linker );
			case 3: return $datavalue->getLongHTMLText( $linker );
			case 4: return $datavalue->getWikiValue();
		}
	}

}