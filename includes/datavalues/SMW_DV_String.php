<?php

use SMW\DataValues\ValueFormatters\DataValueFormatter;

/**
 * This datavalue implements String-Datavalues suitable for defining
 * String-types of properties.
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWStringValue extends SMWDataValue {

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $value
	 */
	protected function parseUserValue( $value ) {

		if ( $value === '' ) {
			$this->addErrorMsg( 'smw_emptystring' );
		}

		$this->m_dataitem = new SMWDIBlob( $value );
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem instanceof SMWDIBlob ) {
			$this->m_caption = false;
			$this->m_dataitem = $dataItem;
			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::WIKI_SHORT, $linker );
	}

	public function getShortHTMLText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::HTML_SHORT, $linker );
	}

	public function getLongWikiText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::WIKI_LONG, $linker );
	}

	/**
	 * @todo Rather parse input to obtain properly formatted HTML.
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->getDataValueFormatter()->format( DataValueFormatter::HTML_LONG, $linker );
	}

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
		} else {
			return $this->m_infolinks;
		}
	}

	protected function getServiceLinkParams() {
		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: urlencoded string
		if ( $this->isValid() ) {
			return array( rawurlencode( $this->m_dataitem->getString() ) );
		} else {
			return false;
		}
	}

}
