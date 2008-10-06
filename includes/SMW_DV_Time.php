<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

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
 * @todo Currently we cannot distinguish incomplete dates
 * such as "Oct 10 2007" from certain complete dates such
 * as "Oct 10 2007T00:00:00". This should change.
 *
 * @author Fabian Howahl
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTimeValue extends SMWDataValue {

	protected $m_time; // the actual time, currently in seconds from 1970
	protected $m_wikivalue; // a suitable wiki input value
	protected $m_xsdvalue = false; // cache for XSD value
	protected $m_printvalue = false; // cache for printout value
	protected $m_day;
	protected $m_month;
	protected $m_newtime;
	protected $m_year;
	protected $m_months = array("January", "February", "March", "April" , "May" , "June" , "Juli" , "August" , "September" , "October" , "November" , "December");
	protected $m_monthsshort = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
	protected $m_formats = array( SMW_Y => array('year'), SMW_YM => array('year','month'), SMW_MY => array('month','year'), SMW_YDM => array('year','day','month'), SMW_YMD => array('year','month','day'), SMW_DMY => array('day','month','year'), SMW_MDY => array('month','day','year'));

	protected function parseUserValue($value) {
		global $smwgContLang;

		$band = false; //group of bits storing information about the possible meaning of each digit of the entered date
		$this->m_day = false; 
		$this->m_month = false;
		$this->m_newtime = false;
		$this->m_year = false;
	
		$this->m_wikivalue = $value;
		$filteredvalue = $value; //value without time definition

		//browse string for time value
		if(preg_match("/[0-2]?[0-9]:[0-5][0-9](:[0-5][0-9])?([+\-][0-2]?[0-9](:(30|00))?)?/u", $value, $match)){
			$this->m_newtime = $match[0];
			$regexp = "/(\040|T){0,1}".str_replace("+", "\+", $match[0])."(\040){0,1}/u"; //delete time value and preceding and following chars
			$filteredvalue = preg_replace($regexp,'', $value); //value without time
		}

		//split array in order to separate the date digits
		$array = preg_split("/[\040|.|\-|\/]+/u", $filteredvalue, 3); //TODO: support &nbsp;

		//the following code segment creates a band by finding out wich role each digit of the entered date can take (date, year, month)
		//the band starts with 1 und for each digit of the entered date a binary code with three bits is attached
		//examples: 	111 states that the digit can be interpreted as a month, a day or a year
		//		100 digit can just be interpreted as a month
		//	 	010 digit can just be interpreted as a day
		//		001 digit can just be interpreted as a year  
		//		the remaining combinations are also possible (if reasonable) 
		//for instance a date consisting of three digits will have a 10 bit band 
		if (count($array) != 0) {
			$band = 1;
			foreach ($array as $tmp) {
				$band = $band << 3;
				$band = $band | $this->checkDigit($tmp);
			}
		} else {
			$this->addError(wfMsgForContent('smw_nodatetime',$value));
			return true;
		}

		$dateformats = $smwgContLang->getDateFormats(); //get the language dependent date formats

		$digitcount = count($array)-1; //number of digits - 1 is used as an array index for $dateformats
		$found = false;

		foreach ($dateformats[$digitcount] as $format) { //check whether created band matches dateformats
			if (!(~$band & $format)) { //check if $format => $band
				$i = 0;
				foreach ($this->m_formats[$format] as $globalvar) {
					$globalvar = 'm_'.$globalvar;
					if (!$this->$globalvar) $this->$globalvar = $array[$i];
					$i++;
				}
				$found = true;
				break;
			}
		}

		if (!$found) {
			$this->addError(wfMsgForContent('smw_nodatetime',$value));
			return true;
		}

		if (!$this->m_month) $this->m_month = 1;
		if (!$this->m_day) $this->m_day = 1;
		if (!$this->m_newtime) $this->m_newtime = '00:00';

		$this->m_time = strtotime($this->m_year."-".$this->m_month."-".$this->m_day." ".$this->m_newtime);
		if ( ($this->m_time == -1) || ($this->m_time === false) ) {
			$this->addError(wfMsgForContent('smw_nodatetime',$value));
		}
		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		return true;
	}

	protected function checkDigit($digit){
		global $smwgContLang;

		if(!is_numeric($digit)){ //check for alphanumeric month value
			$monthnumber = $smwgContLang->findMonth($digit);
			if ( $monthnumber !== false ) {
				$this->m_month = $monthnumber;
				return SMW_MONTH;
			}
			$monthnumber = array_search($digit, $this->m_months);
			if ( $monthnumber !== false ) {
				$this->m_month = $monthnumber + 1;
				return SMW_MONTH;
			}
			$monthnumber = array_search($digit, $this->m_monthsshort);
			if ( $monthnumber !== false ) {
				$this->m_month = $monthnumber + 1;
				return SMW_MONTH;
			}
			return 0;
		} elseif ($digit >= 1 && $digit <= 12) { //number could be a month, a day or a year	(111)		
			return SMW_DAY_MONTH_YEAR;
		} elseif ($digit >= 1 && $digit <= 31) { //number could be a day or a year (011) 
			return SMW_DAY_YEAR;
		} elseif ($digit < 3000) { //number could just be a year (011)
			return SMW_YEAR;
		} else {
			return 0;
		}
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
	protected function makePrintoutValue() { //TODO: switch to UnixTime representation
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
