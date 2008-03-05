<?php

/**
 * This datavalue captures values of dates and times.
 * The implementation is dependent on PHP's strtotime(),
 * which in turn uses GNU get_date(), and thus supports
 * only a limited range of possible times, typically
 * Fri, 13 Dec 1901 20:45:54 GMT to Tue, 19 Jan 2038 03:14:07 
 * GMT on Linux servers. Some operating systems hay have further
 * restrictions.
 * Times internally are stored as XSD-conformant strings, see 
 * http://www.w3.org/TR/xmlschema-2/#dateTime
 *
 * This implementation might change in the future. For maximum
 * compatibility, use no relative dates ("... + 10 days"),
 * no dates referring to current time ("next Tuesday"), no
 * weekdays in your values, and use ":" between hours, minutes,
 * and seconds (i.e. no "1230" as 12:30).
 *
 * @TODO currently we cannot distinguish incomplete dates
 * such as "Oct 10 2007" from certain complete dates such
 * as "Oct 10 2007T00:00:00". This should change.
 *
 * @author Markus Krötzsch
 * @note AUTOLOADED
 */
class SMWTimeValue extends SMWDataValue {

	protected $m_time; // the actual time, currently in seconds from 1970
	protected $m_wikivalue; // a suitable wiki input value
	protected $m_xsdvalue = false; // cache for XSD value
	protected $m_printvalue = false; // cache for printout value

	protected function parseUserValue($value) {
		$this->m_wikivalue = $value;
		$this->m_time = strtotime(trim($value));
		if ( ($this->m_time == -1) || ($this->m_time === false) ) {
			$this->addError(wfMsgForContent('smw_nodatetime',$value));
		}
		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		return true;
	}

	protected function parseXSDValue($value, $unit) {
		$this->parseUserValue($value);
		$this->makePrintoutValue();
		$this->m_caption = $this->m_printvalue;
		$this->m_wikivalue = $value;
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
			$this->makePrintoutValue();
			return $this->m_printvalue;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->getLongWikiText($linker);
	}

	public function getXSDValue() {
		if ($this->m_xsdvalue === false) {
			$this->m_xsdvalue = date("Y-m-d\TH:i:s",$this->m_time);
		}
		return $this->m_xsdvalue;
	}

	public function getNumericValue() {
		return $this->m_time;
	}

	public function getWikiValue(){
		return $this->m_wikivalue;
	}

	public function getHash() {
		if ($this->isValid()) {
			return $this->m_time;
		} else {
			return implode("\t", $this->m_errors);
		}
	}

	public function isNumeric() {
		return true;
	}

	public function getExportData() {
		if ($this->isValid()) {
			$lit = new SMWExpLiteral($this->getXSDValue(), $this, 'http://www.w3.org/2001/XMLSchema#dateTime');
			return new SMWExpData($lit);
		} else {
			return NULL;
		}
	}

	/**
	 * Build a preferred value for printout, also used as a caption when setting up values
	 * from the store.
	 */
	protected function makePrintoutValue() {
		if ($this->m_printvalue === false) {
			global $wgContLang;
			$date = date('Ymd', $this->m_time);
			$time = date('His', $this->m_time);
			$this->m_printvalue = $wgContLang->date($date . $time, false, false);
			if ($time !== '000000') {
				$this->m_printvalue .= '&nbsp;' . $wgContLang->time($date . $time, false, false);
			}
		}
	}

}
