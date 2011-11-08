<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements Boolean datavalues.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWBoolValue extends SMWDataValue {

	/**
	 * The text to write for "true" if a custom output format was set.
	 * @var string
	 */
	protected $m_truecaption;

	/**
	 * The text to write for "false" if a custom output format was set.
	 * @var string
	 */
	protected $m_falsecaption;

	protected function parseUserValue( $value ) {
		$value = trim( $value );
		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}

		$lcv = strtolower( $value );
		$boolvalue = false;
		if ( $lcv === '1' ) {
			$boolvalue = true;
		} elseif ( $lcv === '0' ) {
			$boolvalue = false;
		} elseif ( in_array( $lcv, explode( ',', wfMsgForContent( 'smw_true_words' ) ), true ) ) {
			$boolvalue = true;
		} elseif ( in_array( $lcv, explode( ',', wfMsgForContent( 'smw_false_words' ) ), true ) ) {
			$boolvalue = false;
		} else {
			$this->addError( wfMsgForContent( 'smw_noboolean', $value ) );
		}
		$this->m_dataitem = new SMWDIBoolean( $boolvalue, $this->m_typeid );
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_BOOLEAN ) {
			$this->m_dataitem = $dataItem;
			$this->m_caption = $this->getStandardCaption( true ); // use default for this language
			return true;
		} else {
			return false;
		}
	}

	public function setOutputFormat( $formatstring ) {
		if ( $formatstring == $this->m_outformat ) return;
		unset( $this->m_truecaption );
		unset( $this->m_falsecaption );
		if ( $formatstring === '' ) { // no format
			// (unsetting the captions is exactly the right thing here)
		} elseif ( strtolower( $formatstring ) == '-' ) { // "plain" format
			$this->m_truecaption = 'true';
			$this->m_falsecaption = 'false';
		} elseif ( strtolower( $formatstring ) == 'x' ) { // X format
			$this->m_truecaption = '<span style="font-family: sans-serif; ">X</span>';
			$this->m_falsecaption = '';
		} else { // format "truelabel, falselabel" (hopefully)
			$captions = explode( ',', $formatstring, 2 );
			if ( count( $captions ) == 2 ) { // note: escaping needed to be safe; MW-sanitising would be an alternative
				$this->m_truecaption = htmlspecialchars( trim( $captions[0] ) );
				$this->m_falsecaption = htmlspecialchars( trim( $captions[1] ) );
			} // else: no format that is recognised, ignore
		}
		$this->m_caption = $this->getStandardCaption( true );
		$this->m_outformat = $formatstring;
	}

	public function getShortWikiText( $linked = null ) {
		return $this->m_caption;
	}

	public function getShortHTMLText( $linker = null ) {
		return $this->m_caption;
	}

	public function getLongWikiText( $linked = null ) {
		return $this->isValid() ? $this->getStandardCaption( true ) : $this->getErrorText();
	}

	public function getLongHTMLText( $linker = null ) {
		return $this->isValid() ? $this->getStandardCaption( true ) : $this->getErrorText();
	}

	public function getWikiValue() {
		return $this->getStandardCaption( false );
	}

	public function getBoolean() {
		return $this->m_dataitem->getBoolean();
	}

	/**
	 * Get text for displaying the value of this property, or false if not
	 * valid.
	 * @param $useformat bool, true if the output format should be used, false if the returned text should be parsable
	 * @return string
	 */
	protected function getStandardCaption( $useformat ) {
		if ( !$this->isValid() ) return false;
		if ( $useformat && ( isset( $this->m_truecaption ) ) ) {
			return $this->m_dataitem->getBoolean() ? $this->m_truecaption : $this->m_falsecaption;
		} else {
			$vals = explode( ',', wfMsgForContent( $this->m_dataitem->getBoolean() ? 'smw_true_words' : 'smw_false_words' ) );
			return reset( $vals );
		}
	}

}
