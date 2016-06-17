<?php

use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIBlob as DIBlob;

/**
 * This datavalue implements String-Datavalues suitable for defining
 * String-types of properties.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 */
class SMWStringValue extends DataValue {

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
	 *
	 * @return string
	 */
	public function getShortWikiText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::WIKI_SHORT, $linker );
	}

	/**
	 * @see DataValue::getShortHTMLText
	 *
	 * @return string
	 */
	public function getShortHTMLText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::HTML_SHORT, $linker );
	}

	/**
	 * @see DataValue::getLongWikiText
	 *
	 * @return string
	 */
	public function getLongWikiText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::WIKI_LONG, $linker );
	}

	/**
	 * @todo Rather parse input to obtain properly formatted HTML.
	 * @see DataValue::getLongHTMLText
	 *
	 * @return string
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::HTML_LONG, $linker );
	}

	/**
	 * @see DataValue::getWikiValue
	 *
	 * @return string
	 */
	public function getWikiValue() {
		return $this->getDataValueFormatter()->format( DataValueFormatter::VALUE );
	}

	public function getWikiValueForLengthOf( $length ) {

		if ( mb_strlen( $this->getWikiValue() ) > $length ) {
			return mb_substr( $this->getWikiValue(), 0, $length );
		}

		return $this->getWikiValue();
	}

	public function getInfolinks() {

		if ( $this->m_typeid != '_cod' ) {
			return parent::getInfolinks();
		}

		return array();
	}

	protected function getServiceLinkParams() {

		if ( !$this->isValid() ) {
			return false;
		}

		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: urlencoded string
		return array( rawurlencode( $this->m_dataitem->getString() ) );
	}

}
