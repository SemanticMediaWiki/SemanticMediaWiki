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

	protected $m_value = NULL; // true, false, or NULL (unset)
	protected $m_stdcaption = ''; // a localised standard label for that value (if value is not NULL)
	protected $m_truecaption = NULL; // a desired label for "true" if given
	protected $m_falsecaption = NULL; // a desired label for "false" if given

	protected function parseUserValue($value) {
		wfLoadExtensionMessages('SemanticMediaWiki');
		$value = trim($value);
		$lcv = strtolower($value);
		$this->m_value = NULL;
		if ($lcv === '1') { // note: if English "true" should be possible, specify in smw_true_words
			$this->m_value = true;
		} elseif ($lcv === '0') { // note: English "false" may be added to smw_true_words
			$this->m_value = false;
		} elseif (in_array($lcv, explode(',', wfMsgForContent('smw_true_words')), TRUE)) {
			$this->m_value = true;
		} elseif (in_array($lcv, explode(',', wfMsgForContent('smw_false_words')), TRUE)) {
			$this->m_value = false;
		} else {
			$this->addError(wfMsgForContent('smw_noboolean', $value));
		}

		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		if ($this->m_value === true) {
			if ($this->m_truecaption !== NULL) {
				$this->m_stdcaption = $this->m_truecaption;
			} else {
				$vals = explode(',', wfMsgForContent('smw_true_words'));
				$this->m_stdcaption = $vals[0];
			}
		} elseif ($this->m_value === false) {
			if ($this->m_falsecaption !== NULL) {
				$this->m_stdcaption = $this->m_falsecaption;
			} else {
				$vals = explode(',', wfMsgForContent('smw_false_words'));
				$this->m_stdcaption = $vals[0];
			}
		} else {
			$this->m_stdcaption = '';
		}
		return true;
	}

	protected function parseXSDValue($value, $unit) {
		$this->parseUserValue($value); // no units, XML compatible syntax
		$this->m_caption = $this->m_stdcaption; // use default for this language
	}

	public function setOutputFormat($formatstring) {
		if ($formatstring == '') {
			// ignore
		} elseif (strtolower($formatstring) == 'x') {
			$this->m_truecaption = '<span style="font-family: sans-serif; ">X</span>';
			$this->m_falsecaption = '';
		} else { // try format "truelabel, falselabel"
			$captions = explode(',', $formatstring, 2);
			if ( count($captions) == 2 ) { // note: escaping needed to be safe; MW-sanitising would be an alternative
				$this->m_truecaption = htmlspecialchars(trim($captions[0]));
				$this->m_falsecaption = htmlspecialchars(trim($captions[1]));
			} // else ignore
		}
		if ( ($formatstring != $this->m_outformat) && $this->isValid() && ($this->m_truecaption !== NULL) ) { // also adjust display
			$this->m_caption = $this->m_stdcaption = ($this->m_value?$this->m_truecaption:$this->m_falsecaption);
		}
		$this->m_outformat = $formatstring;
	}

	public function getShortWikiText($linked = NULL) {
		return $this->m_caption;
	}

	public function getShortHTMLText($linker = NULL) {
		return $this->m_caption;
	}

	public function getLongWikiText($linked = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->m_stdcaption;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->m_stdcaption;
		}
	}

	public function getXSDValue() {
		return $this->m_value?'1':'0';
	}

	public function getWikiValue(){
		return $this->m_stdcaption;
	}

	public function getNumericValue() {
		return $this->m_value?'1':'0';
	}

	public function isNumeric() {
		return true;
	}

	public function getExportData() {
		if ($this->isValid()) {
			$xsdvalue =  $this->m_value?'true':'false';
			$lit = new SMWExpLiteral($xsdvalue, $this, 'http://www.w3.org/2001/XMLSchema#boolean');
			return new SMWExpData($lit);
		} else {
			return NULL;
		}
	}

}
