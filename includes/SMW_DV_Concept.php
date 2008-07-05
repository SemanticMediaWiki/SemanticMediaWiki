<?php

/**
 * This datavalue is used as a container for concept descriptions as used
 * on Concept pages with the #concept parserfunction. It has a somewhat
 * non-standard interface as compared to other datavalues, but this is not
 * an issue.
 *
 * @author Markus Krötzsch
 * @note AUTOLOADED
 */
class SMWConceptValue extends SMWDataValue {

	protected $m_concept = ''; // XML-safe, HTML-safe, Wiki-compatible concept expression (query string)
	protected $m_docu = '';    // text description of concept, can only be set by special function "setvalues"

	protected function parseUserValue($value) {
		// this function is normally not used for this class, not created from user input directly
		$this->m_concept = smwfXMLContentEncode($value);
		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		return true;
	}

	protected function parseXSDValue($value, $unit) {
		// normally not used, store should use setValues
		$this->m_concept = $value;
		$this->m_caption = $this->m_concept; // this is our output text
	}

	public function getShortWikiText($linked = NULL) {
		return $this->m_caption;
	}

	public function getShortHTMLText($linker = NULL) {
		return $this->getShortWikiText($linker); // should be save (based on xsdvalue)
	}

	public function getLongWikiText($linked = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->m_caption;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->m_caption; // should be save (based on xsdvalue)
		}
	}

	public function getXSDValue() {
		return $this->m_concept;
	}

	public function getWikiValue(){
		return $this->m_concept;
	}

	public function getExportData() {
		if ($this->isValid()) {
// 			$lit = new SMWExpLiteral(smwfHTMLtoUTF8($this->m_value), $this, 'http://www.w3.org/2001/XMLSchema#string');
// 			return new SMWExpData($lit);
			return NULL; /// TODO
		} else {
			return NULL;
		}
	}

	/**
	 * Special features for Type:Code formating.
	 */
	protected function getCodeDisplay($value, $scroll = false) {
		$result = str_replace( array('<', '>', ' ', '://', '=', "'"), array('&lt;', '&gt;', '&nbsp;', '<!-- -->://<!-- -->', '&#x003D;', '&#x0027;'), $value);
		if ($scroll) {
			$result = "<div style=\"height:5em; overflow:auto;\">$result</div>";
		}
		return "<pre>$result</pre>";
	}

	public function setValues($concept, $docu) {
		$this->setUserValue($concept); // must be called to make object valid (parent implementation)
		$this->m_docu = $docu?smwfXMLContentEncode($docu):'';
	}

	public function getDocu() {
		return $this->m_docu;
	}

}
