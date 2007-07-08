<?php

/**
 * This datavalue implements String-Datavalues suitable for defining
 * String-types of properties.
 * 
 * @author: Nikolas Iwan
 */
class SMWStringValue extends SMWDataValue {

	private $m_error = '';
	private $m_value = '';
	private $m_xsdvalue = '';
	private $m_infolinks = Array();

	protected function parseUserValue($value) {
		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		if ($value!='') {
			$this->m_xsdvalue = smwfXMLContentEncode($value);
			// 255 below matches smw_attributes.value_xsd definition in smwfMakeSemanticTables()
			if (strlen($this->m_xsdvalue) > 255) {
				$this->m_error = wfMsgForContent('smw_maxstring', $this->m_xsdvalue);
				$this->m_value = $this->m_xsdvalue;
			} else {
				$this->m_value = $this->m_xsdvalue;
				$this->m_infolinks[] = SMWInfolink::newAttributeSearchLink('+', $this->m_attribute, $this->m_value);
			}
		} else {
			$this->m_error = wfMsgForContent('smw_emptystring');
		}
		return true;
	}

	protected function parseXSDValue($value, $unit) {
		$this->parseUserValue($value); // no units, XML compatible syntax
		$this->m_caption = $this->m_value; // this is our output text
	}

	public function setOutputFormat($formatstring) {
		// no output formats
	}

	public function getShortWikiText($linked = NULL) {
		//TODO: Support linking?
		return $this->m_caption;
	}

	public function getShortHTMLText($linker = NULL) {
		return htmlspecialchars($this->getShortWikiText($linker));
	}

	public function getLongWikiText($linked = NULL) {
		if (! ($this->m_error === '')){
			return ('<span class="smwwarning">' . $this->m_error  . '</span>');
		} else {
			return $this->m_value;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		if (! ($this->m_error === '')){
			return ('<span class="smwwarning">' . $this->m_error  . '</span>');
		} else {
			return htmlspecialchars($this->m_value);
		}
	}

	public function getXSDValue() {
		return $this->getShortWikiText(); /// FIXME: not correct for special symbols like "Ü" and "&"?
	}
	
	public function getWikiValue(){
		return $this->getShortWikiText();  /// FIXME: wikivalue must not be influenced by the caption
	}
	
	public function getNumericValue() {
		return NULL;
	}

	public function getUnit() {
		return ''; // empty unit
	}

	public function getError() {
		return $this->m_error;
	}
	
	public function getTypeID(){
		return 'string';
	}

	public function getInfolinks() {
		return $this->m_infolinks;
	}

	public function getHash() {
		return $this->getLongWikiText(false) . $this->m_xsdvalue ;
	}

	public function isValid() {
		return (($this->m_error == '') && ($this->m_value !== '') );
	}

	public function isNumeric() {
		return false;
	}


}
