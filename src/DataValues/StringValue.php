<?php

namespace SMW\DataValues;

use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use SMWDIBlob as DIBlob;

/**
 * Implements a string/text based datavalue suitable for defining text properties.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 */
class StringValue extends DataValue {

	/**
	 * DV text identifier
	 */
	const TYPE_ID = '_txt';

	/**
	 * DV identifier
	 */
	const TYPE_LEGACY_ID = '_str';

	/**
	 * DV code identifier
	 */
	const TYPE_COD_ID = '_cod';

	/**
	 * @var ValueFormatter
	 */
	private $valueFormatter;

	/**
	 * @see DataValue::getShortWikiText
	 *
	 * {@inheritDoc}
	 */
	public function getShortWikiText( $linker = null ) {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::WIKI_SHORT, $linker ] );
	}

	/**
	 * @see DataValue::getShortHTMLText
	 *
	 * {@inheritDoc}
	 */
	public function getShortHTMLText( $linker = null ) {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::HTML_SHORT, $linker ] );
	}

	/**
	 * @see DataValue::getLongWikiText
	 *
	 * {@inheritDoc}
	 */
	public function getLongWikiText( $linker = null ) {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::WIKI_LONG, $linker ] );
	}

	/**
	 * @todo Rather parse input to obtain properly formatted HTML.
	 * @see DataValue::getLongHTMLText
	 *
	 * {@inheritDoc}
	 */
	public function getLongHTMLText( $linker = null ) {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::HTML_LONG, $linker ] );
	}

	/**
	 * @see DataValue::getWikiValue
	 *
	 * {@inheritDoc}
	 */
	public function getWikiValue() {

		if ( $this->valueFormatter === null ) {
			$this->valueFormatter = $this->dataValueServiceFactory->getValueFormatter( $this );
		}

		return $this->valueFormatter->format( $this, [ DataValueFormatter::VALUE, null ] );
	}

	/**
	 * @see DataValue::getInfolinks
	 *
	 * {@inheritDoc}
	 */
	public function getInfolinks() {

		if ( $this->m_typeid != '_cod' ) {
			return parent::getInfolinks();
		}

		return [];
	}

	/**
	 * @since 3.0
	 *
	 * @return integer
	 */
	public function getLength() {

		if ( !$this->isValid() ) {
			return 0;
		}

		return mb_strlen( $this->m_dataitem->getString() );
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * {@inheritDoc}
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
	 * {@inheritDoc}
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
	 * @see DataValue::getServiceLinkParams
	 *
	 * {@inheritDoc}
	 */
	protected function getServiceLinkParams() {

		if ( !$this->isValid() ) {
			return false;
		}

		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: urlencoded string
		return [ rawurlencode( $this->m_dataitem->getString() ) ];
	}

}
