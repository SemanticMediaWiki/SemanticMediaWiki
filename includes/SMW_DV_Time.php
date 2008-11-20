<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue captures values of dates and times. All dates and times refer to
 * the "local time" of the server (or the wiki). A wiki may define what timezone this
 * refers to by common conventions. For export, times are given without timezone
 * information. However, time offsets to that local time are supported (see below).
 *
 * It is able to handle dates accross history with full precision for storing, and
 * substantial precision for sorting and querying. The range of supported past dates
 * should encompass the Beginning of Time according to most of today's theories. The
 * range of supported future dates is limited more strictly, but it does also allow
 * year numbers in the order of 10^9.
 *
 * Years before common era (aka BC) can be denoted using "BC" in a date. The internal
 * nummeric date model supports the year 0, and considers it to be the same as "1 BC".
 * The year "0 BC" is accepted to refer to the same year, but its use is discouraged.
 * According to this convention, e.g., the year "-100" is the same as "101 BC". This
 * convention agrees with ISO 6801 and the remarks in XML Schema Datatypes 2nd Edition,
 * (the latter uses a different convention that disallows year 0, but it explicitly
 * endorses the ISO convention and announces the future use of this in XML).
 * Note that the implementation currently does not support the specification of negative
 * year numbers as input; negative numbers are only used internally.
 *
 * The implementation notices and stores whether parts of a date/time have been
 * omitted (as in "2008" or "May 2007"). For all exporting and sorting purposes,
 * incomplete dates are completed wiht defaults (usually using the earliest possible
 * time, i.e. interpreting "2008" as "Jan 1 2008 00:00:00"). But the information
 * on what was unspecified is kept internally for improving behaviour e.g. for
 * outputs (defaults are not printed when querying for a value). Functions are
 * provided to access the individual time components (getYear, getMonth, getDay,
 * getTimeString), and those can also be used to find out what was unspecified.
 *
 * Time offests are supported (e.g. "1 1 2008 12:00-2:00"). As explained above, those
 * refer to the local time.
 *
 * @author Fabian Howahl
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTimeValue extends SMWDataValue {

	protected $m_wikivalue; // a suitable wiki input value
	protected $m_xsdvalue = false; // cache for XSD value
	protected $m_printvalue = false; // cache for printout value
	protected $m_day = false; //Gregorian day, remains false if unspecified
	protected $m_month = false; //Gregorian month, remains false if unspecified
	protected $m_year = false; //Gregorian year, remains false if unspecified
	protected $m_time = false; //time, remains false if unspecified
	protected $m_jdn = ''; //numerical time representation similiar to Julian Day Number, for ancient times, a more compressed number is used (preserving ordering but not distance of time points)
	protected $m_timeoffset; //contains offset (e.g. timezone) 
	protected $m_timeannotation; //contains am or pm
	// The following are constant (array-valued constants are not supported, hence the decalration as variable):
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
		$this->m_time = false;
		$this->m_timeoffset = 0;
		$this->m_timepm = false;
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
		$is_yearbc = false;
		if(preg_match("/[ABab][DCdc]/u", $filteredvalue, $match)){
			if ( strtoupper($match[0]) == 'BC' ) {
				$is_yearbc = true;
			}
			$regexp = "/(\040|T){0,1}".str_replace("+", "\+", $match[0])."(\040){0,1}/u"; //delete ad/bc value and preceding and following chars
			$filteredvalue = preg_replace($regexp,'', $filteredvalue); //value without ad/bc
		}

		//browse string for time value
		if(preg_match("/[0-2]?[0-9]:[0-5][0-9](:[0-5][0-9])?([+\-][0-2]?[0-9](:(30|00))?)?/u", $filteredvalue, $match)){
			$time = $match[0];

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

		// The following code segment creates a band by finding out wich role each digit of the entered date can take
		// (date, year, month). The band starts with 1 and for each digit of the entered date a binary code with three
		// bits is attached. Examples:
		//		111 states that the digit can be interpreted as a month, a day or a year
		//		100 digit can just be interpreted as a month
		//		010 digit can just be interpreted as a day
		//		001 digit can just be interpreted as a year
		//		the remaining combinations are also possible (if reasonable)
		// A date consisting of three digits therefore will have a 10 bit band.
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
			if (!(~$band & $format)) { //check if $format => $band ("the detected band supports the current format")
				$i = 0;
				foreach ($this->m_formats[$format] as $globalvar) { // map format digits to internal variables
					$globalvar = 'm_'.$globalvar; // (for searching this file) this is one of: m_year, m_month, m_day
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
		} elseif ( ($this->m_day > 0) && ($this->m_day > $this->m_daysofmonths[$this->m_month]) ) { //date does not exist in Gregorian calendar
			wfLoadExtensionMessages('SemanticMediaWiki');
			$this->addError(wfMsgForContent('smw_nodatetime',$value));
			return true;
		} elseif ( ($this->m_year < -4713) && ($this->m_timeoffset != 0) ) { //no support for time offsets if year < -4713
			wfLoadExtensionMessages('SemanticMediaWiki');
			$this->addError(wfMsgForContent('smw_nodatetime',$value));
			return true;
		}

		if ($is_yearbc) {
			if ($this->m_year > 0) { // see class documentation on BC, "year 0", and ISO conformance ...
				$this->m_year = -($this->m_year-1);
			}
		}

		//handle offset
		if ($this->m_timeoffset != 0) {
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
			if(preg_match("/[0-3]?[0-9](st|nd|rd|th)/u", $digit)) { //look for day value terminated by st/nd/th
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
		} elseif ($digit >= 1 && $digit <= 12) { //number can be a month, a day or a year	(111)		
			return SMW_DAY_MONTH_YEAR;
		} elseif (($digit >= 1 && $digit <= 31)) { //number can be a day or a year (011) 
			return SMW_DAY_YEAR;
		} elseif (is_numeric($digit)) { //number can just be a year (011)
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
			$lit = new SMWExpLiteral($this->getXMLSchemaDate(), $this, 'http://www.w3.org/2001/XMLSchema#dateTime');
			return new SMWExpData($lit);
		} else {
			return NULL;
		}
	}

	/**
	 * Return the year as a number or false if the value is not set.
	 */
	public function getYear() {
		return ($this->isValid())?$this->m_year:false;
	}

	/**
	 * Return the month as a number (between 1 and 12) or false if the value is not set.
	 * The parameter $default optionally specifies the value returned
	 * if the date is valid but has no explicitly specified month. It can
	 * also be set to FALSE to detect this situation.
	 */
	public function getMonth($default = 1) {
		if (!$this->isValid()) return false;
		return ($this->m_month != false)?$this->m_month:$default;
	}

	/**
	 * Return the day as a number or false if the value is not set.
	 * The parameter $default optionally specifies the value returned
	 * if the date is valid but has no explicitly specified date. It can
	 * also be set to FALSE to detect this situation.
	 */
	public function getDay($default = 1) {
		if (!$this->isValid()) return false;
		return ($this->m_day != false)?$this->m_day:$default;
	}

	/**
	 * Return the time as a string or false if the value is not set.
	 * The time string has the format HH:MM:SS, without any timezone
	 * information.
	 * The parameter $default optionally specifies the value returned
	 * if the date is valid but has no explicitly specified time. It can
	 * also be set to FALSE to detect this situation.
	 */
	public function getTimeString($default = '00:00:00') {
		if (!$this->isValid()) return false;
		return ($this->m_time != false)?$this->normalizeTimeValue($this->m_time):$default;
	}

	/**
	 * Return a representation of this date in canonical dateTime format without timezone, as
	 * specified in XML Schema Part 2: Datatypes Second Edition (W3C Recommendation, 28 October 2004,
	 * http://www.w3.org/TR/xmlschema-2). An example would be "2008-01-02T14:30:10". BC(E) years
	 * are represented by a leading "-" as in "-123-01-02T14:30:10", the 2nd January of the year
	 * 123 BC(E) at 2:30pm and 10 seconds.
	 *
	 * If the date was not fully specified, then the function will use defaults for the omitted values.
	 * The boolean parameter $mindefault controls if those defaults are chosen minimally. If false, then
	 * the latest possible value will be chosen instead.
	 */
	public function getXMLSchemaDate($mindefault = true) {
		if ($this->isValid()) {
			if ($mindefault) {
				return $this->m_year.'-'.$this->normalizeValue($this->getMonth()).'-'.$this->normalizeValue($this->getDay()).'T'.$this->getTimeString();
			} else {
				return $this->m_year.'-'.$this->normalizeValue($this->getMonth(12)).'-'.$this->normalizeValue($this->getDay(31)).'T'.$this->getTimeString('23:59:59');
			}
		} else {
			return false;
		}
	}

	/**
	 * Build a preferred value for printout, also used as a caption when setting up values
	 * from the store.
	 */
	protected function makePrintoutValue() {
		global $smwgContLang;
		if ($this->m_printvalue === false) {
			//MediaWiki date function is not applicable any more (no support for BC Dates)
			if ($this->m_year > 0) {
				$this->m_printvalue = $this->m_year;
			} else {
				$this->m_printvalue = -($this->m_year-1) . ' BC';
			}
			if ($this->m_month) {
				$this->m_printvalue =  $smwgContLang->getMonthLabel($this->m_month) . " " . $this->m_printvalue;
			}
			if ($this->m_day) {
				$this->m_printvalue =  $this->m_day . " " . $this->m_printvalue;
			}
			if ($this->m_time) {
				$this->m_printvalue .= " " . $this->m_time;
			}
		}
	}

	protected function normalizeValue($value){
		if(strlen($value) == 1) {
			$value = "0".$value;
		}
		return $value;
	}

	protected function normalizeTimeValue($value){
		$value = $this->normalizeValue($value);
		$parts = explode(":",$value);
		switch (count($parts)) {
		case 1: return $parts[0].":00:00";
		case 2: return $parts[0].":".$parts[1].":00";
		default: return $value;
		}
	}

	//time representation:
	//if year >= -4713 date is represented as follows:
	//XXXX.YYYY where XXXX is the days having elapsed since 4713 BC and YYYY is the elapsed time of the day as fraction of 1
	//otherwise XXXX is the number of years BC and YYYY represents the elapsed days of the year as fraction of 1
	protected function createJDN(){
	  $this->m_jdn = 0;
	  if (!$this->isValid()) return;
	  if($this->m_year >= -4713){
		$a = intval((14-$this->getMonth())/12);
		$y = $this->m_year + 4800 - $a;
		$m = $this->getMonth() + 12 * $a - 3;

		if($this->m_time != false) {//just calculate fraction if time is set
			list ($hours, $minutes, $seconds) = explode(':',$this->getTimeString(),3);
			$time = ($hours/24) + ($minutes / (60*24)) + ($seconds / (3600*24));
			$this->m_jdn += $time;
	    }
	    $this->m_jdn += $this->getDay() + intval((153*$m+2)/5) + 365*$y + intval($y/4) - intval($y/100) + intval($y/400) - 32045;
	  }
	  else {
		  $time = 1 - (($this->getMonth() / 12) + ($this->getDay() / 365));
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
