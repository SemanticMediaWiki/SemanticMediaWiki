<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue captures values of dates and times.
 * The implementation uses a format similiar to Julian Day Number to represent time values.
 * Times internally are stored as XSD-conformant strings, see 
 * http://www.w3.org/TR/xmlschema-2/#dateTime
 *
 * This implementation might change in the future. For maximum
 * compatibility, use no relative dates ("... + 10 days"),
 * no dates referring to current time ("next Tuesday"), no
 * weekdays in your values, and use ":" between hours, minutes,
 * and seconds (i.e. no "1230" as 12:30).
 *
 * @author Fabian Howahl
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTimeValue extends SMWDataValue {

	protected $m_wikivalue; // a suitable wiki input value
	protected $m_xsdvalue = false; // cache for XSD value
	protected $m_printvalue = false; // cache for printout value
	protected $m_day = false; //Gregorian day
	protected $m_month = false; //Gregorian month
	protected $m_year = false; //Gregorian year
	protected $m_time = "00:00:00"; //time
	protected $m_jdn = ''; //numerical time representation similiar to Julian Day Number
	protected $m_timeoffset; //contains offset (e.g. timezone) 
	protected $m_timeannotation; //contains am or pm
	protected $m_timeisset;
	protected $m_yearbc; //true if year is BC
	protected $m_months = array("January", "February", "March", "April" , "May" , "June" , "Juli" , "August" , "September" , "October" , "November" , "December");
	protected $m_monthsshort = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
	protected $m_formats = array( SMW_Y => array('year'), SMW_YM => array('year','month'), SMW_MY => array('month','year'), SMW_YDM => array('year','day','month'), SMW_YMD => array('year','month','day'), SMW_DMY => array('day','month','year'), SMW_MDY => array('month','day','year'));
	protected $m_daysofmonths = array ( 1 => 31, 2 => 28, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31 );

	protected function parseUserValue($value) {
		global $smwgContLang;

		$band = false; //group of bits storing information about the possible meaning of each digit of the entered date
		$this->m_day = false; 
		$this->m_month = false;
		$this->m_year = false;
		$this->m_jdn = false;
		$this->m_time = "00:00:00";
		$this->m_timeoffset = 0;
		$this->m_timepm = false;
		$this->m_timeisset = false;
		$this->m_timeannotation = false;
	
		$this->m_wikivalue = $value;
		$filteredvalue = $value; //value without time definition and further abbreviations like PM or BC

		//browse string for special abbreviations referring to time like am, pm
		if(preg_match("/([Aa]|[Pp])[Mm]/u", $filteredvalue, $match)){
		  $this->m_timeannotation = strtolower($match[0]);	
		  $regexp = "/(\040|T){0,1}".str_replace("+", "\+", $match[0])."(\040){0,1}/u"; //delete pm/am, preceding and following chars
		  $filteredvalue = preg_replace($regexp,'', $filteredvalue); //value without am/pm
		}

		//browse string for special abbreviations referring to year like AD, BC
		if(preg_match("/[ABab][DCdc]/u", $filteredvalue, $match)){
			if(!strcasecmp($match[0],'bc')){
				$this->m_yearbc = true;
			}
			$regexp = "/(\040|T){0,1}".str_replace("+", "\+", $match[0])."(\040){0,1}/u"; //delete ad/bc value and preceding and following chars
			$filteredvalue = preg_replace($regexp,'', $filteredvalue); //value without ad/bc
		}

		//browse string for time value
		if(preg_match("/[0-2]?[0-9]:[0-5][0-9](:[0-5][0-9])?([+\-][0-2]?[0-9](:(30|00))?)?/u", $filteredvalue, $match)){
			$time = $match[0];
			$this->m_timeisset = true;
			
			//timezone handling
			if(preg_match("/([+\-][0-2]?[0-9](:(30|00))?)/u", $time, $match2)){ //get timezone definition
			  $offset = $this->normalizeTimeValue($match2[0]);
			  $sign = 1;
			  if($offset[0] == '-') $sign = -1;
			  $offset = substr($offset,1);
			  list ($offhours, $offminutes, $offseconds) = explode(':',$offset,3);
			  $offset = $sign * (($offhours / 24) + ($offminutes / (60*24)) + ($offseconds / (3600*24)));
    			  $this->m_timeoffset = $this->m_timeoffset + $offset;
			  $time = str_replace($match2[0],'',$time);
			}

			list($hours,$minutes,$seconds) = explode(':',$this->normalizeTimeValue($time),3);

			//am/pm handling
			if($this->m_timeannotation != false){
			  if(!strcmp($this->m_timeannotation,'am') && $hours == 12) $hours = 0;
			  if(!strcmp($this->m_timeannotation,'pm') && $hours <= 11){
			    $this->m_timeoffset = $this->m_timeoffset +  0.5;
			  }
			}
						
			$this->m_time = $this->normalizeValue($hours).":".$this->normalizeValue($minutes).":".$this->normalizeValue($seconds);
			$regexp = "/(\040|T){0,1}".str_replace("+", "\+", $match[0])."(\040){0,1}/u"; //delete time value and preceding and following chars
			$filteredvalue = preg_replace($regexp,'', $filteredvalue); //value without time
		}

		//split array in order to separate the date digits
		$array = preg_split("/[\040|.|,|\-|\/]+/u", $filteredvalue, 3); //TODO: support &nbsp and - again;

		//the following code segment creates a band by finding out wich role each digit of the entered date can take (date, year, month)
		//the band starts with 1 und for each digit of the entered date a binary code with three bits is attached
		//examples:
		//		111 states that the digit can be interpreted as a month, a day or a year
		//		100 digit can just be interpreted as a month
		//		010 digit can just be interpreted as a day
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
			wfLoadExtensionMessages('SemanticMediaWiki');
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

		//error catching
		if (!$found) { //no band matches the entered date
			wfLoadExtensionMessages('SemanticMediaWiki');
			$this->addError(wfMsgForContent('smw_nodatetime',$value));
			return true;
		}
		elseif ($this->m_day > 0 && $this->m_day > $this->m_daysofmonths[$this->m_month]){ //date does not exist in Gregorian calendar
			wfLoadExtensionMessages('SemanticMediaWiki');
			$this->addError(wfMsgForContent('smw_nodatetime',$value));
			return true;
		}
		elseif ($this->m_year < -4713 && $this->m_timeoffset != 0) { //no support for time offsets if year < -4713
			wfLoadExtensionMessages('SemanticMediaWiki');
			$this->addError(wfMsgForContent('smw_nodatetime',$value));
			return true;
		}

		//prepare values for storing
		$this->m_day = $this->normalizeValue($this->m_day);
		$this->m_month = $this->normalizeValue($this->m_month);

		if($this->m_yearbc){
			$this->m_year = -$this->m_year;
		}

		//handle offset	
		if($this->m_timeoffset != 0){
			$this->createJDN();
			$this->m_jdn = $this->m_jdn + $this->m_timeoffset;
			$this->JDN2Date();
		}
		
		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		
		return true;
	}

	protected function checkDigit($digit){
		global $smwgContLang;
		if(!is_numeric($digit)){ //check for alphanumeric day or month value 				
			if(preg_match("/[0-3]?[0-9](st|nd|th)/u", $digit)){ //look for day value terminated by st/nd/th
				$this->m_day = substr($digit,0,strlen($digit)-2); //remove st/nd/th			
				return SMW_DAY;
			}
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
		} elseif (($digit >= 1 && $digit <= 31)) { //number could be a day or a year (011) 
			return SMW_DAY_YEAR;
		} elseif (is_numeric($digit)) { //number could just be a year (011)
			return SMW_YEAR;
		} else {
			return 0;
		}
	}

	protected function parseXSDValue($value, $unit) {
		list($date,$this->m_time) = explode('T',$value,2);
		list($this->m_year,$this->m_month,$this->m_day) = explode('/',$date,3);
		$this->makePrintoutValue();
		$this->m_caption = $this->m_printvalue;
		$this->m_wikivalue = $value;
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
			$this->makePrintoutValue();
			return $this->m_printvalue;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->getLongWikiText($linker);
	}

	public function getXSDValue() {
		if ($this->m_xsdvalue === false) {
			$this->m_xsdvalue = $this->m_year."/".$this->m_month."/".$this->m_day."T".$this->m_time;
		}
		return $this->m_xsdvalue;
	}

	public function getNumericValue() {
		$this->createJDN();
		return $this->m_jdn;
	}

	public function getWikiValue(){
		return $this->m_wikivalue;
	}

	public function getHash() {
		if ($this->isValid()) {
			$this->createJDN();
			return strval($this->m_jdn);
		} else {
			return implode("\t", $this->m_errors);
		}
	}

	public function isNumeric() {
		return true;
	}

	public function getExportData() {
		if ($this->isValid()) {
		  $xml = $this->m_year.'-'.$this->normalizeValue($this->m_month).'-'.$this->normalizeValue($this->m_day).'T'.$this->m_time;
		  $lit = new SMWExpLiteral($xml, $this, 'http://www.w3.org/2001/XMLSchema#dateTime');
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
		global $smwgContLang;
		if ($this->m_printvalue === false) {
			if ($this->m_timeisset || !(!$this->m_timeisset && $this->m_time=="00:00:00")) {
				$time = ' ' . $this->m_time;
			} else {
				$time = '';
			}
			if ((int)$this->m_day>0) {
				$day = (int)$this->m_day . ' ';
			} else {
				$day = '';
			}
			//MediaWiki date function is not applicable any more (no support for BC Dates)
			$this->m_printvalue = $day . $smwgContLang->getMonthLabel($this->m_month) . " " . $this->m_year . $time;
		}
	}

	protected function normalizeValue($value){
		if(strlen($value) == 1) {
			$value = "0".$value;
		}
		return $value; 
	}

	protected function normalizeTimeValue($value){
		$parts = explode(":",$value);	
		switch (count($parts)) {
			case 1: return $this->$parts[0].":00:00";
			case 2: return $parts[0].":".$parts[1].":00";
			default: return $value;
		}
	}

	//time representation:
	//if year >= -4713 date is represented as follows:
	//XXXX.YYYY where XXXX is the days having elapsed since 4713 BC and YYYY is the elapsed time of the day as fraction of 1
	//otherwise XXXX is the number of years BC and YYYY represents the elapsed days of the year as fraction of 1
	protected function createJDN(){
		if($this->m_year >= -4713){		
			$a = intval((14-$this->m_month)/12);
			$y = $this->m_year + 4800 - $a;
			$m = $this->m_month + 12 * $a - 3;
			list ($hours, $minutes, $seconds) = explode(':',$this->m_time,3);
			$time = ($hours/24) + ($minutes / (60*24)) + ($seconds / (3600*24));
			$this->m_jdn = $this->m_day + intval((153*$m+2)/5) + 365*$y + intval($y/4) - intval($y/100) + intval($y/400) - 32045 + $time;		
		}
		else{
			$time = 1 - (($this->m_month / 12) + ($this->m_day / 365));
			$this->m_jdn = $this->m_year - $time;
		}
	}

	/// Convert JDN back to Gregorian date.
	protected function JDN2Date() {
		$j = intval($this->m_jdn) + 32044;
		$g = intval($j / 146097);
		$dg = $j % 146097;
		$c = intval(((intval($dg / 36524) + 1) * 3) / 4);
		$dc = $dg - $c * 36524;
		$b = intval($dc / 1461);
		$db = $dc % 1461;
		$a = intval(  ( (intval($db / 365) + 1) * 3) / 4);
		$da = $db - ($a * 365);
		$y = $g * 400 + $c * 100 + $b * 4 + $a;
		$m = intval(($da * 5 + 308) / 153) - 2;
		$d = $da - intval((($m + 4) * 153) / 5) + 122;
		$this->m_year = $y - 4800 + intval(($m + 2) / 12);
		$this->m_month = ($m + 2) % 12 + 1;
		$this->m_day = $d + 1;

		$fraction = $this->m_jdn - intval($this->m_jdn);
		$time = round($fraction * 3600 * 24);
		$hours = intval($time / 3600);
		$time = $time - $hours * 3600;
		$minutes = intval($time / 60);
		$seconds = intval($time - $minutes * 60);

		$this->m_time = $this->normalizeValue($hours).":".$this->normalizeValue($minutes).":".$this->normalizeValue($seconds);		
	}

}
