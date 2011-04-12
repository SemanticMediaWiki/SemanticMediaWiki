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
		if ( $errormsg != '' ) {
			$this->addError( $errormsg );
		}
	}

	protected function parseUserValue( $value ) {
		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$this->addError( wfMsgForContent( 'smw_parseerror' ) );
	}

	protected function parseDBkeys( $args ) {
		$this->setUserValue( strval( $args[0] ) ); // compatible syntax
		// Note that errors are never a proper result of reading data from the
		// store, so it is quite unlikely that the data we get here fits this
		// datatype. Normally, it will not be displayed either since this value
		// is not valid by default. So keeping the DB key here is rather
		// irrelevant.
	}

	/**
	 * @see SMWDataValue::setDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	public function setDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_ERROR ) {
			$this->addError( $dataItem->getErrors() );
			$this->m_caption = $this->getErrorText();
			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linked = null ) {
		$this->unstub();
		return $this->m_caption;
	}

	public function getShortHTMLText( $linker = null ) {
		return htmlspecialchars( $this->getShortWikiText( $linker ) );
	}

	public function getLongWikiText( $linked = null ) {
		$this->unstub();
		return $this->getErrorText();
	}

	public function getLongHTMLText( $linker = null ) {
		$this->unstub();
		return $this->getErrorText();
	}

	public function getDBkeys() {
		return array( $this->m_dataitem->getString() );
	}

	public function getWikiValue() {
		return $this->m_dataitem->getString();
	}

	public function isValid() {
		return false;
	}

}
