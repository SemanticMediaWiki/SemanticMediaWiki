<?php

namespace SMW\DataValues;

use SMW\Message;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIBlob as DIBlob;

/**
 * Handling of a language dependent error message encoded by Message::encode.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorMsgTextValue extends DataValue {

	/**
	 * @see DataValue::__construct
	 */
	public function __construct( $typeId = '' ) {
		parent::__construct( '__errt' );
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $value ) {

		if ( $value === '' ) {
			$this->addErrorMsg( 'smw_emptystring' );
		}

		$this->m_dataitem = new DIBlob( $value );
	}

	/**
	 * @see DataValue::loadDataItem
	 *
	 * @param SMWDataItem $dataitem
	 *
	 * @return boolean
	 */
	protected function loadDataItem( DataItem $dataItem ) {

		if ( !$dataItem instanceof DIBlob ) {
			return false;
		}

		$this->m_caption = false;
		$this->m_dataitem = $dataItem;

		return true;
	}

	/**
	 * @see DataValue::getShortWikiText
	 */
	public function getShortWikiText( $linker = null ) {
		return $this->constructErrorText( null );
	}

	/**
	 * @see DataValue::getShortHTMLText
	 */
	public function getShortHTMLText( $linker = null ) {
		return $this->constructErrorText( $linker );
	}

	/**
	 * @see DataValue::getLongWikiText
	 */
	public function getLongWikiText( $linker = null ) {
		return $this->constructErrorText( $linker );
	}

	/**
	 * @see DataValue::getLongHTMLText
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->constructErrorText( $linker );
	}

	/**
	 * @see DataValue::getWikiValue
	 */
	public function getWikiValue() {
		return $this->constructErrorText();
	}

	private function constructErrorText( $linker = null ) {

		if ( !$this->isValid() || $this->getDataItem() === [] ) {
			return '';
		}

		$string = $this->getDataItem()->getString();
		$type = $linker !== null ? Message::PARSE : Message::TEXT;

		if ( ( $message = Message::decode( $string, $type, $this->getOption( self::OPT_USER_LANGUAGE ) ) ) !== false ) {
			return $message;
		}

		return $string;
	}

}
