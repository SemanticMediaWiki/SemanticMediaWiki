<?php
/**
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
		if ( $value === '' ) {
			$this->addError( wfMessage( 'smw_emptystring' )->inContentLanguage()->text() );
		}

		$this->m_dataitem = new SMWDIBlob( $value, $this->m_typeid );
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

	public function getShortWikiText( $linked = null ) {
		if ( $this->m_caption !== false ) {
			return $this->m_caption;
		} else {
			return $this->getDisplayString(
					false,
					( $linked !== false ) && ( !is_null( $linked ) ),
					false
				);
		}
	}

	public function getShortHTMLText( $linker = null ) {
		if ( $this->m_caption !== false ) {
			return smwfXMLContentEncode( $this->m_caption );
		} else {
			return $this->getDisplayString(
					false,
					( $linker !== false ) && ( !is_null( $linker ) ),
					true
				);
		}
	}

	public function getLongWikiText( $linked = null ) {
		return $this->getDisplayString(
				true,
				( $linked !== false ) && ( !is_null( $linked ) ),
				false
			);
	}

	/**
	 * @todo Rather parse input to obtain properly formatted HTML.
	 */
	public function getLongHTMLText( $linker = null ) {
		return $this->getDisplayString(
				true,
				( $linker !== false ) && ( !is_null( $linker ) ),
				true
			);
	}

	public function getWikiValue() {
		return $this->isValid() ? $this->m_dataitem->getString() : 'error';
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

	/**
	 * Get the string that should be displayed for this value.
	 * The result is only escaped to be HTML-safe if this is requested
	 * explicitly. The result will contain mark-up that must not be escaped
	 * again.
	 *
	 * @since 1.8
	 * @param boolean $abbreviate
	 * @param boolean $linked set to false to disable tooltips
	 * @param boolean $forHtml should the result be escaped to be HTML-safe?
	 * @return string
	 */
	protected function getDisplayString( $abbreviate, $linked, $forHtml ) {
		if ( !$this->isValid() ) {
			return '';
		} elseif ( $this->m_typeid == '_cod' ) {
			return $this->getCodeDisplay( $this->m_dataitem->getString(), $abbreviate );
		} else {
			return $this->getTextDisplay( $this->m_dataitem->getString(), $abbreviate, $linked, $forHtml );
		}
	}

	/**
	 * Make a possibly shortened printout string for displaying the value.
	 * The result is only escaped to be HTML-safe if this is requested
	 * explicitly. The result will contain mark-up that must not be escaped
	 * again.
	 *
	 * @todo The method abbreviates very long strings for display by simply
	 * taking substrings. This is not in all cases a good idea, since it may
	 * break XML entities and mark-up.
	 *
	 * @since 1.8
	 * @param string $value
	 * @param boolean $abbreviate limit overall display length?
	 * @param boolean $linked should abbreviated values use tooltips?
	 * @param boolean $forHtml should the result be escaped to be HTML-safe?
	 * @return string
	 */
	protected function getTextDisplay( $value, $abbreviate, $linked, $forHtml ) {
		if ( $forHtml ) {
			$value = smwfXMLContentEncode( $value );
		}

		$length = mb_strlen( $value );
		if ( $abbreviate && $length > 255 ) {
			if ( !$linked ) {
				$ellipsis = ' <span class="smwwarning">…</span> ';
			} else {
				$highlighter = SMW\Highlighter::factory( SMW\Highlighter::TYPE_TEXT );
				$highlighter->setContent( array (
					'caption' => ' … ',
					'content' => $value
				) );

				$ellipsis = $highlighter->getHtml();
			}

			return mb_substr( $value, 0, 42 ) . $ellipsis . mb_substr( $value, $length - 42 );
		} else {
			return $value;
		}
	}

	/**
	 * Escape and wrap values of type Code. The result is escaped to be
	 * HTML-safe (it will also work in wiki context). The result will
	 * contain mark-up that must not be escaped again.
	 *
	 * @param string $value
	 * @param boolean $abbreviate should the code box be limited vertically?
	 * @return string
	 */
	protected function getCodeDisplay( $value, $abbreviate ) {
		SMWOutputs::requireResource( 'ext.smw.style' );
		// This disables all active wiki and HTML markup:
		$result = str_replace(
			array( '<', '>', ' ', '[', '{', '=', "'", ':', "\n" ),
			array( '&lt;', '&gt;', '&#160;', '&#x005B;', '&#x007B;', '&#x003D;', '&#x0027;', '&#58;', "<br />" ),
			$value );

		if ( $abbreviate ) {
			$result = "<div style=\"height:5em; overflow:auto;\">$result</div>";
		}

		return "<div class=\"smwpre\">$result</div>";
	}

}
