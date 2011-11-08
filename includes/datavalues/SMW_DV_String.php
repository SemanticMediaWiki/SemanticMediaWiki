<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements String-Datavalues suitable for defining
 * String-types of properties.
 *
 * @author Nikolas Iwan
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWStringValue extends SMWDataValue {

	protected function parseUserValue( $value ) {
		if ( $this->m_caption === false ) {
			$this->m_caption = ( $this->m_typeid == '_cod' ) ? $this->getCodeDisplay( $value ) : $value;
		}
		if ( $value === '' ) {
			$this->addError( wfMsgForContent( 'smw_emptystring' ) );
		}

		if ( ( $this->m_typeid == '_txt' ) || ( $this->m_typeid == '_cod' ) ) {
			$this->m_dataitem = new SMWDIBlob( $value, $this->m_typeid );
		} else {
			try {
				$this->m_dataitem = new SMWDIString( $value, $this->m_typeid );
			} catch ( SMWStringLengthException $e ) {
				$this->addError( wfMsgForContent( 'smw_maxstring', '"' . mb_substr( $value, 0, 15 ) . ' … ' . mb_substr( $value, mb_strlen( $value ) - 15 ) . '"' ) );
				$this->m_dataitem = new SMWDIBlob( 'ERROR', $this->m_typeid ); // just to make sure that something is defined here
			}
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		$diType = ( ( $this->m_typeid == '_txt' ) || ( $this->m_typeid == '_cod' ) ) ? SMWDataItem::TYPE_BLOB : SMWDataItem::TYPE_STRING;
		if ( $dataItem->getDIType() == $diType ) {
			$this->m_dataitem = $dataItem;
			if ( $this->m_typeid == '_cod' ) {
				$this->m_caption = $this->getCodeDisplay( $this->m_dataitem->getString() );
			} else {
				$this->m_caption = $this->m_dataitem->getString();
			}
			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linked = null ) {
		return $this->m_caption;
	}

	/**
	 * @todo Rather parse input to obtain properly formatted HTML.
	 */
	public function getShortHTMLText( $linker = null ) {
		return smwfXMLContentEncode( $this->getShortWikiText( $linker ) );
	}

	public function getLongWikiText( $linked = null ) {
		return $this->isValid() ? $this->getAbbValue( $linked, $this->m_dataitem->getString() ) : $this->getErrorText();
	}

	/**
	 * @todo Rather parse input to obtain properly formatted HTML.
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->isValid() ? $this->getAbbValue( $linker, smwfXMLContentEncode( $this->m_dataitem->getString() ) ) : $this->getErrorText();
	}

	public function getWikiValue() {
		return $this->m_dataitem->getString();
	}

	public function getInfolinks() {
		if ( ( $this->m_typeid != '_txt' ) && ( $this->m_typeid != '_cod' ) ) {
			return parent::getInfolinks();
		} else {
			return $this->m_infolinks;
		}
	}

	protected function getServiceLinkParams() {
		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: urlencoded string
		if ( ( $this->m_typeid != '_txt' ) && ( $this->m_typeid != '_cod' ) ) {
			return array( rawurlencode( $this->m_dataitem->getString() ) );
		} else {
			return false; // no services for Type:Text and Type:Code
		}
	}

	/**
	 * Make a possibly shortened printout string for displaying the value.
	 * The value must be specified as an input since necessary HTML escaping
	 * must be applied to it first, if desired. The result of getAbbValue()
	 * may contain wiki-compatible HTML mark-up that should not be escaped.
	 * @todo The method abbreviates very long strings for display by simply
	 * taking substrings. This is not in all cases a good idea, since it may
	 * break XML entities and mark-up.
	 */
	protected function getAbbValue( $linked, $value ) {
		$len = mb_strlen( $value );
		if ( ( $len > 255 ) && ( $this->m_typeid != '_cod' ) ) {
			if ( is_null( $linked ) || ( $linked === false ) ) {
				return mb_substr( $value, 0, 42 ) . ' <span class="smwwarning">…</span> ' . mb_substr( $value, $len - 42 );
			} else {
				SMWOutputs::requireResource( 'ext.smw.tooltips' );
				return mb_substr( $value, 0, 42 ) . ' <span class="smwttpersist"> … <span class="smwttcontent">' . $value . '</span></span> ' . mb_substr( $value, $len - 42 );
			}
		} elseif ( $this->m_typeid == '_cod' ) {
			return $this->getCodeDisplay( $value, true );
		} else {
			return $value;
		}
	}

	/**
	 * Special features for Type:Code formatting.
	 */
	protected function getCodeDisplay( $value, $scroll = false ) {
		SMWOutputs::requireResource( 'ext.smw.style' );
		$result = str_replace( array( '<', '>', ' ', '=', "'", ':', "\n" ), array( '&lt;', '&gt;', '&#160;', '&#x003D;', '&#x0027;', '&#58;', "<br />" ), $value );
		if ( $scroll ) {
			$result = "<div style=\"height:5em; overflow:auto;\">$result</div>";
		}
		return "<div class=\"smwpre\">$result</div>";
	}

}
