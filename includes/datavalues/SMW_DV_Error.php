<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements error datavalues, a kind of pseudo data value that
 * is used in places where a data value is expected but no more meaningful
 * value could be created. It is always invalid and never gets stored or
 * exported, but it can help to transport an error message.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWErrorValue extends SMWDataValue {

	public function __construct( $typeid, $errormsg = '', $uservalue = '', $caption = false ) {
		parent::__construct( $typeid );
		$this->m_caption = ( $caption !== false ) ? $caption : $uservalue;
		if ( $errormsg !== '' ) {
			$this->addError( $errormsg );
		}
	}

	protected function parseUserValue( $value ) {
		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}
		$this->addError( wfMsgForContent( 'smw_parseerror' ) );
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_ERROR ) {
			$this->addError( $dataItem->getErrors() );
			$this->m_caption = $this->getErrorText();
			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linked = null ) {
		return $this->m_caption;
	}

	public function getShortHTMLText( $linker = null ) {
		return htmlspecialchars( $this->getShortWikiText( $linker ) );
	}

	public function getLongWikiText( $linked = null ) {
		return $this->getErrorText();
	}

	public function getLongHTMLText( $linker = null ) {
		return $this->getErrorText();
	}

	public function getWikiValue() {
		return $this->m_dataitem->getString();
	}

	public function isValid() {
		return false;
	}

}
