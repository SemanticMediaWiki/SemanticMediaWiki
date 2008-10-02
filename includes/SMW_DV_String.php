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
 * @note AUTOLOADED
 */
class SMWStringValue extends SMWDataValue {

	protected $m_value = ''; // XML-safe, HTML-safe, Wiki-compatible value representation
	                         // however, this string might contain HTML entities such as &amp;

	protected function parseUserValue($value) {
		wfLoadExtensionMessages('SemanticMediaWiki');
		if ($value!='') {
			$this->m_value = smwfXMLContentEncode($value);
			if ( (strlen($this->m_value) > 255) && ($this->m_typeid == '_str') ) { // limit size (for DB indexing)
				$this->addError(wfMsgForContent('smw_maxstring', mb_substr($value, 0, 42) . ' <span class="smwwarning">[&hellip;]</span> ' . mb_substr($value, mb_strlen($this->m_value) - 42)));
			}
		} else {
			$this->addError(wfMsgForContent('smw_emptystring'));
		}
		if ($this->m_caption === false) {
			$this->m_caption = ($this->m_typeid=='_cod')?$this->getCodeDisplay($value):$value;
		}
		return true;
	}

	protected function parseXSDValue($value, $unit) {
		$this->parseUserValue($value); // no units, XML compatible syntax
		$this->m_caption = $this->m_value; // this is our output text
	}

	public function getShortWikiText($linked = NULL) {
		//TODO: Support linking?
		return $this->m_caption;
	}

	public function getShortHTMLText($linker = NULL) {
		return $this->getShortWikiText($linker); // should be save (based on xsdvalue)
	}

	public function getLongWikiText($linked = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->getAbbValue($linked);
		}
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->getLongWikiText($linker); // should be save (based on xsdvalue)
	}

	public function getXSDValue() {
		return $this->m_value;
	}

	public function getWikiValue(){
		return $this->m_value;
	}

	public function getInfolinks() {
		if ($this->m_typeid == '_str') {
			return SMWDataValue::getInfolinks();
		}
		return $this->m_infolinks;
	}

	protected function getServiceLinkParams() {
		// Create links to mapping services based on a wiki-editable message. The parameters 
		// available to the message are:
		// $1: urlencoded string
		if ($this->m_typeid != '_str') {
			return false; // no services for Type:Text and Type:Code
		} else {
			return array(rawurlencode($this->m_value));
		}
	}

	public function getExportData() {
		if ($this->isValid()) {
			$lit = new SMWExpLiteral(smwfHTMLtoUTF8($this->m_value), $this, 'http://www.w3.org/2001/XMLSchema#string');
			return new SMWExpData($lit);
		} else {
			return NULL;
		}
	}

	/**
	 * Make a possibly shortened printout string for displaying the value.
	 */
	protected function getAbbValue($linked) {
		$len = mb_strlen($this->m_value);
		if ( ($len > 255) && ($this->m_typeid != '_cod') ) {
			if ( ($linked === NULL)||($linked === false) ) {
				return mb_substr($this->m_value, 0, 42) . ' <span class="smwwarning">&hellip;</span> ' . mb_substr($this->m_value, $len - 42);
			} else {
				SMWOutputs::requireHeadItem(SMW_HEADER_TOOLTIP);
				return mb_substr($this->m_value, 0, 42) . ' <span class="smwttpersist"> &hellip; <span class="smwttcontent">' . $this->m_value . '</span></span> ' . mb_substr($this->m_value, $len - 42);
			}
		} elseif ($this->m_typeid == '_cod') {
			return $this->getCodeDisplay($this->m_value,true);
		} else {
			return $this->m_value;
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

}
