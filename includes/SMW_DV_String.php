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

	/// Wiki-compatible value representation, possibly unsafe for plain HTML.
	protected $m_value = '';

	protected function parseUserValue($value) {
		wfLoadExtensionMessages('SemanticMediaWiki');
		if ($value!='') {
			$this->m_value = $value;
			if ( (strlen($this->m_value) > 255) && ($this->m_typeid != '_txt') && ($this->m_typeid != '_cod') ) { // limit size (for DB indexing)
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

	protected function parseDBkeys($args) {
		$this->parseUserValue($args[0]);
		$this->m_caption = $this->m_value; // this is our output text
	}

	public function getShortWikiText($linked = NULL) {
		$this->unstub();
		//TODO: Support linking?
		return $this->m_caption;
	}

	/**
	 * @todo Rather parse input to obtain properly formatted HTML.
	 */
	public function getShortHTMLText($linker = NULL) {
		return smwfXMLContentEncode($this->getShortWikiText($linker));
	}

	public function getLongWikiText($linked = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->getAbbValue($linked,$this->m_value);
		}
	}

	/**
	 * @todo Rather parse input to obtain properly formatted HTML.
	 */
	public function getLongHTMLText($linker = NULL) {
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->getAbbValue($linker,smwfXMLContentEncode($this->m_value));
		}
	}

	public function getDBkeys() {
		$this->unstub();
		return array($this->m_value);
	}

	public function getWikiValue(){
		$this->unstub();
		return $this->m_value;
	}

	public function getInfolinks() {
		$this->unstub();
		if ( ($this->m_typeid != '_txt') && ($this->m_typeid != '_cod') ) {
			return SMWDataValue::getInfolinks();
		}
		return $this->m_infolinks;
	}

	protected function getServiceLinkParams() {
		$this->unstub();
		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: urlencoded string
		if ( ($this->m_typeid != '_txt') && ($this->m_typeid != '_cod') ){
			return array(rawurlencode($this->m_value));
		} else {
			return false; // no services for Type:Text and Type:Code
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
	 * The value must be specified as an input since necessary HTML escaping
	 * must be applied to it first, if desired. The result of getAbbValue()
	 * may contain wiki-compatible HTML mark-up that should not be escaped.
	 * @todo The method abbreviates very long strings for display by simply
	 * taking substrings. This is not in all cases a good idea, since it may
	 * break XML entities and mark-up.
	 */
	protected function getAbbValue($linked, $value) {
		$len = mb_strlen($value);
		if ( ($len > 255) && ($this->m_typeid != '_cod') ) {
			if ( ($linked === NULL)||($linked === false) ) {
				return mb_substr($value, 0, 42) . ' <span class="smwwarning">&hellip;</span> ' . mb_substr($value, $len - 42);
			} else {
				SMWOutputs::requireHeadItem(SMW_HEADER_TOOLTIP);
				return mb_substr($value, 0, 42) . ' <span class="smwttpersist"> &hellip; <span class="smwttcontent">' . $value . '</span></span> ' . mb_substr($value, $len - 42);
			}
		} elseif ($this->m_typeid == '_cod') {
			return $this->getCodeDisplay($value,true);
		} else {
			return $value;
		}
	}

	/**
	 * Special features for Type:Code formating.
	 */
	protected function getCodeDisplay($value, $scroll = false) {
		SMWOutputs::requireHeadItem(SMW_HEADER_STYLE);
		$result = str_replace( array('<', '>', ' ', '=', "'", ':',"\n"), array('&lt;', '&gt;', '&nbsp;', '&#x003D;', '&#x0027;', '&#58;',"<br />"), $value);
		if ($scroll) {
			$result = "<div style=\"height:5em; overflow:auto;\">$result</div>";
		}
		return "<div class=\"smwpre\">$result</div>";
	}

}
