<?php

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

	protected function parseUserValue($value) {
		if ($value!='') {
			$this->m_value = smwfXMLContentEncode($value);
			if ( (strlen($this->m_value) > 255) && ($this->m_typeid !== '_txt') ) { // limit size (for DB indexing)
				$this->addError(wfMsgForContent('smw_maxstring', mb_substr($value, 0, 42) . ' <span class="smwwarning">[&hellip;]</span> ' . mb_substr($value, mb_strlen($this->m_value) - 42)));
			}
		} else {
			$this->addError(wfMsgForContent('smw_emptystring'));
		}
		if ($this->m_caption === false) {
			$this->m_caption = $value;
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
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			return $this->getAbbValue($linker); // should be save (based on xsdvalue)
		}
	}

	public function getXSDValue() {
		return $this->m_value;
	}

	public function getWikiValue(){
		return $this->m_value;
	}

	public function getInfolinks() {
		if ($this->m_typeid !== '_txt') {
			return SMWDataValue::getInfolinks();
		}
		return $this->m_infolinks;
	}

	protected function getServiceLinkParams() {
		// Create links to mapping services based on a wiki-editable message. The parameters 
		// available to the message are:
		// $1: urlencoded string
		if ($this->m_typeid === '_txt') {
			return false; // no services for Type:Text
		} else {
			return array(rawurlencode($this->m_value));
		}
	}

	/**
	 * Creates the export line for the RDF export
	 *
	 * @param string $QName The element name of this datavalue
	 * @param ExportRDF $exporter the exporter calling this function
	 * @return the line to be exported
	 */
	public function exportToRDF($QName, ExportRDF $exporter) {
		return "\t\t<$QName rdf:datatype=\"http://www.w3.org/2001/XMLSchema#string\">$this->m_value</$QName>\n";
	}

	/**
	 * Make a possibly shortened printout string for displaying the value.
	 */
	protected function getAbbValue($linked) {
		$len = mb_strlen($this->m_value);
		if ($len > 255) {
			if ( ($linked === NULL)||($linked === false) ) {
				return mb_substr($this->m_value, 0, 42) . ' <span class="smwwarning">&hellip;</span> ' . mb_substr($this->m_value, $len - 42);
			} else {
				smwfRequireHeadItem(SMW_HEADER_TOOLTIP);
				return mb_substr($this->m_value, 0, 42) . ' <span class="smwttpersist"> &hellip; <span class="smwttcontent">' . $this->m_value . '</span></span> ' . mb_substr($this->m_value, $len - 42);
			}
		} else {
			return $this->m_value;
		}
	}


}
