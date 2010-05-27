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

	protected $m_value = null; // true, false, or NULL (unset)
	protected $m_stdcaption = ''; // a localised standard label for that value (if value is not NULL)
	protected $m_truecaption = null; // a desired label for "true" if given
	protected $m_falsecaption = null; // a desired label for "false" if given

	protected function parseUserValue( $value ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$value = trim( $value );
		$lcv = strtolower( $value );
		$this->m_value = null;
		if ( $lcv === '1' ) { // note: if English "true" should be possible, specify in smw_true_words
			$this->m_value = true;
		} elseif ( $lcv === '0' ) { // note: English "false" may be added to smw_true_words
			$this->m_value = false;
		} elseif ( in_array( $lcv, explode( ',', wfMsgForContent( 'smw_true_words' ) ), TRUE ) ) {
			$this->m_value = true;
		} elseif ( in_array( $lcv, explode( ',', wfMsgForContent( 'smw_false_words' ) ), TRUE ) ) {
			$this->m_value = false;
		} else {
			$this->addError( wfMsgForContent( 'smw_noboolean', $value ) );
		}

		if ( $this->m_caption === false ) {
			$this->m_caption = $value;
		}
		if ( $this->m_value === true ) {
			if ( $this->m_truecaption !== null ) {
				$this->m_stdcaption = $this->m_truecaption;
			} else {
				$vals = explode( ',', wfMsgForContent( 'smw_true_words' ) );
				$this->m_stdcaption = $vals[0];
			}
		} elseif ( $this->m_value === false ) {
			if ( $this->m_falsecaption !== null ) {
				$this->m_stdcaption = $this->m_falsecaption;
			} else {
				$vals = explode( ',', wfMsgForContent( 'smw_false_words' ) );
				$this->m_stdcaption = $vals[0];
			}
		} else {
			$this->m_stdcaption = '';
		}
		return true;
	}

	protected function parseDBkeys( $args ) {
		$this->parseUserValue( $args[0] );
		$this->m_caption = $this->m_stdcaption; // use default for this language
	}

	public function setOutputFormat( $formatstring ) {
		if ( $formatstring == '' ) {
			// ignore
		} elseif ( strtolower( $formatstring ) == 'x' ) {
			$this->m_truecaption = '<span style="font-family: sans-serif; ">X</span>';
			$this->m_falsecaption = '';
		} else { // try format "truelabel, falselabel"
			$captions = explode( ',', $formatstring, 2 );
			if ( count( $captions ) == 2 ) { // note: escaping needed to be safe; MW-sanitising would be an alternative
				$this->m_truecaption = htmlspecialchars( trim( $captions[0] ) );
				$this->m_falsecaption = htmlspecialchars( trim( $captions[1] ) );
			} // else ignore
		}
		if ( ( $formatstring != $this->m_outformat ) && $this->isValid() && ( $this->m_truecaption !== null ) ) { // also adjust display
			$this->m_caption = $this->m_stdcaption = ( $this->m_value ? $this->m_truecaption:$this->m_falsecaption );
		}
		$this->m_outformat = $formatstring;
	}

	public function getShortWikiText( $linked = null ) {
		$this->unstub();
		return $this->m_caption;
	}

	public function getShortHTMLText( $linker = null ) {
		$this->unstub();
		return $this->m_caption;
	}

	public function getLongWikiText( $linked = null ) {
		return $this->isValid() ? $this->m_stdcaption:$this->getErrorText();
	}

	public function getLongHTMLText( $linker = null ) {
		return $this->isValid() ? $this->m_stdcaption:$this->getErrorText();
	}

	public function getDBkeys() {
		$this->unstub();
		return $this->m_value ? array( '1', 1 ):array( '0', 0 );
	}

	public function getSignature() {
		return 'tn';
	}

	public function getValueIndex() {
		return 1;
	}

	public function getLabelIndex() {
		return 0;
	}

	public function getWikiValue() {
		$this->unstub();
		return $this->m_stdcaption;
	}

	public function getExportData() {
		if ( $this->isValid() ) {
			$xsdvalue =  $this->m_value ? 'true':'false';
			$lit = new SMWExpLiteral( $xsdvalue, $this, 'http://www.w3.org/2001/XMLSchema#boolean' );
			return new SMWExpData( $lit );
		} else {
			return null;
		}
	}

}
