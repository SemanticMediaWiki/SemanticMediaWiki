<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue captures values of dates and times. All dates and times refer to
 * Coordinated Universal Time (UTC), or the local time of the wiki server.
 * A wiki may define what timezone this refers to by common conventions.
 * For export, times are given without timezone information. However, time offsets to
 * that local time, or to UTC, are supported (see below). The difference is arbitrary.
 *
 * Dates can be given in many formats, using numbers, month names, and abbreviated
 * month names. The preferred interpretation of ambiguous dates ("1 2 2008" or even
 * "1 2 3 BC") is controlled by the language file, as is the local naming of months.
 * English month names are always supported.
 *
 * Currently, the twelve-hour clock annotations "am" and "pm", and the calendar
 * symbols "BC", "AD", "Gr", "Jl", "JD", "MJD",  and "OS" are supported,
 * and localised to most SMW-supported languages. Civilian and military time-zone monikers
 * are also supported; these do not require internationalization, except perhaps into
 * Cyrillic. Cyrillic support will be added when deemed necessary.
 *
 * The standard calendar model is the Gregorian calendar, inaugurated October 15, 1582.
 * Tooltip outputs in the Gregorian or proleptic Gregorian calendar (for dates earlier
 * than the proclamation date) always appear. However, the printout for browsing or
 * queries will be governed by the expressed preference, so that annotation in certain
 * calendar models will be preserved at query or browse. All annotated dates will *sort*
 * by a common key, regardless of model. (See below for details.)
 *
 * By default, a date annotated without preference is always treated as a Gregorian
 * calendar date (if later than October 4, 1582), or a Julian calendar date (if October
 * 4, 1582 or earlier). But users may override the default interpretation by specifying
 * the symbols "Gr" or "Jl", as appropriate, for any date falling within the Common Era
 * (aka Anno Domini). The annotation of a proleptic Gregorian date *before* the common
 * era is not supported; all BC(E) dates are treated as Julian. However, the proleptic
 * Gregorian date will appear in the tooltip.
 *
 * The "OS" (Old Style) symbol is supported in English, and dates annotated with this
 * symbol are processed accordingly: the year is incremented by one if the date falls
 * between 1 January and 24 March inclusive, and the resultant day is converted
 * according to Julian rules. The printout will be Julian or Gregorian, according to the
 * epoch. As before, the tooltip dialog will show a Gregorian or proleptic Gregorian date.
 *
 * The primary sort key for this datatype is the Julian Day, developed by J. J. Scaliger.
 * All dates are converted to JD and back-converted to the supported models for purposes
 * of output and captioning. In addition, this datatype supports the direct annotation of
 * a JD or a Modified Julian Day (MJD), which might be given in an astronomical table or
 * the flight schedule of a spacecraft, satellite, or rocket probe. For dates falling
 * earlier than midnight 1 January 4713 BC(E) (Julian), the program stores a negative
 * value consisting of the year (as a negative integer) and a fraction of a year. For
 * all other dates, the type stores the conventional JD, normalized to noon UTC.
 *
 * As such, this type can handle dates across history with full precision for storing,
 * and substantial precision for sorting and querying. The range of supported past
 * dates should encompass the Beginning of Time according to most of today's theories.
 * The range of supported future dates is limited more strictly, but it does also allow
 * year numbers in the order of 10^9.
 *
 * Years before common era (aka BC) can be denoted using "BC" in a date. The internal
 * numeric date model supports the year 0, and considers it to be the same as "1 BC"
 * in the Julian calendar. (The proleptic Gregorian calendar accepts a year of 0, but
 * this usage is not permitted here). All outputs follow ISO6801 and the remarks in
 * XML Schema Datatypes 2nd Edition (the latter uses a different convention that
 * disallows year 0, but it explicitly endorses the ISO convention and announces the
 * future use of this in XML). Note that the implementation currently does not support
 * the specification of negative year numbers as input; negative numbers are only used
 * internally. Likewise, "proleptic" negative He and AM inputs are not allowed.
 *
 * The implementation notices and stores whether parts of a date/time have been
 * omitted (as in "2008" or "May 2007"). For all exporting and sorting purposes,
 * incomplete dates are completed with defaults (usually using the earliest possible
 * time, i.e. interpreting "2008" as "Jan 1 2008 00:00:00"). The information
 * on what was unspecified is kept internally for improving behaviour e.g. for
 * outputs (defaults are not printed when querying for a value). Functions are
 * provided to access the individual time components (getYear, getMonth, getDay,
 * getTimeString), and those can also be used to find out what was unspecified.
 *
 * Time offsets are supported (e.g. "1 1 2008 12:00-2:00"). A time offset is the number
 * of hours:minutes to be added to UTC (GMT) to obtain the local-clock reading. Time zone
 * monikers (EST, CST, CET, MEZ, etc.) and military time (e.g. 1240Z, equivalent to 12:40
 * UTC) are also supported.
 *
 * Time offsets take leap years into account, e.g. the date
 * "Feb 28 2004 23:00+2:00" is equivalent to "29 February 2004 01:00:00", while
 * "Feb 28 1900 23:00+2:00" is equivalent to "1 March 1900 01:00:00".
 *
 * @todo Add support for additional calendar models (mainly requires conversion algorithms and
 * internationalization support).
 * @todo Try to reuse more of MediaWiki's records, e.g. to obtain month names or to
 * format dates. The problem is that MW is based on SIO timestamps that don't extend to
 * very ancient or future dates, and that MW uses PHP functions that are bound to UNIX time.
 *
 * @author Fabian Howahl
 * @author Terry A. Hurlbut
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTimeValue extends SMWDataValue {

	protected $m_wikivalue; // a suitable wiki input value
	protected $m_xsdvalue = false; // cache for DB key
	protected $m_gregvalue = false; // cache for (proleptic) Gregorian value
	protected $m_julvalue = false; // cache for (proleptic) Julian value
	protected $m_pref = false; // holds a symbol for the calendar model
	protected $m_day = false; // Gregorian day, remains false if unspecified
	protected $m_month = false; // Gregorian month, remains false if unspecified
	protected $m_year = false; // Gregorian year, remains false if unspecified
	protected $m_time = false; // time, remains false if unspecified
	protected $m_jd = 0; // numerical time representation similiar to Julian Day; for ancient times, a more compressed number is used (preserving ordering of time points)
	protected $m_format = false; // number of parts of the date that were specified
	protected $m_outformat = false; // A special code governing printout formats.
	protected $m_timeoffset; // contains offset (e.g. timezone)
	protected $m_timeannotation; // contains am or pm
	protected $m_dayj = false; // Julian day, remains false if unspecified
	protected $m_monthj = false; // Julian month, remains false if unspecified
	protected $m_yearj = false; // Julian year, remains false if unspecified
	// The following are constant (array-valued constants are not supported, hence the declaration as private static variable):
	private static $m_months = array( "January", "February", "March", "April" , "May" , "June" , "July" , "August" , "September" , "October" , "November" , "December" );
	private static $m_monthsshort = array( "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" );
	private static $m_formats = array( SMW_Y => array( 'year' ), SMW_YM => array( 'year', 'month' ), SMW_MY => array( 'month', 'year' ), SMW_YDM => array( 'year', 'day', 'month' ), SMW_YMD => array( 'year', 'month', 'day' ), SMW_DMY => array( 'day', 'month', 'year' ), SMW_MDY => array( 'month', 'day', 'year' ) );
	private static $m_daysofmonths = array ( 1 => 31, 2 => 29, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31 );
	private static $m_daysofmonthsam = array ( 1 => 30, 2 => 30, 3 => 30, 4 => 30, 5 => 30, 6 => 30, 7 => 30, 8 => 30, 9 => 30, 10 => 30, 11 => 30, 12 => 60 );
	// Time zone monikers and their associated offsets in hours and fractions of hours
	private static $m_tz = array( "A" => 1, "ACDT" => 10.5, "ACST" => 9.5, "ADT" => - 3, "AEDT" => 11,
		"AEST" => 10, "AKDT" => - 8, "AKST" => - 9, "AST" => - 4, "AWDT" => 9, "AWST" => 8,
		"B" => 2, "BST" => 1, "C" => 3, "CDT" => - 5, "CEDT" => 2, "CEST" => 2,
		"CET" => 1, "CST" => - 6, "CXT" => 7, "D" => 4, "E" => 5, "EDT" => - 4,
		"EEDT" => 3, "EEST" => 3, "EET" => 2, "EST" => - 5, "F" => 6, "G" => 7,
		"GMT" => 0, "H" => 8, "HAA" => - 3, "HAC" => - 5, "HADT" => - 9, "HAE" => - 4,
		"HAP" => - 7, "HAR" => - 6, "HAST" => - 10, "HAT" => - 2.5, "HAY" => - 8,
		"HNA" => - 4, "HNC" => - 6, "HNE" => - 5, "HNP" => - 8, "HNR" => - 7, "HNT" => - 3.5,
		"HNY" => - 9, "I" => 9, "IST" => 1, "K" => 10, "L" => 11, "M" => 12,
		"MDT" => - 6, "MESZ" => 2, "MEZ" => 1, "MSD" => 4, "MSK" => 3, "MST" => - 7,
		"N" => - 1, "NDT" => - 2.5, "NFT" => 11.5, "NST" => - 3.5, "O" => - 2, "P" => - 3,
		"PDT" => - 7, "PST" => - 8, "Q" => - 4, "R" => - 5, "S" => - 6, "T" => - 7,
		"U" => - 8, "UTC" => 0, "V" => - 9, "W" => - 10, "WDT" => 9, "WEDT" => 1,
		"WEST" => 1, "WET" => 0, "WST" => 8, "X" => - 11, "Y" => - 12, "Z" => 0 );

// constant epochal values
	const J1582 = 2299160.5;	// Date of switchover to Gregorian calendar
	const MJD_EPOCH = 2400000.5; // Normalizes the Modified JD to midnight 17 Nov 1858 Gregorian.

	protected function parseUserValue( $value ) {
		global $smwgContLang;

		$band = false; // group of bits storing information about the possible meaning of each digit of the entered date
		$this->m_pref = false;
		$this->m_day = false;
		$this->m_month = false;
		$this->m_year = false;
		$this->m_jd = false;
		$this->m_time = false;
		$this->m_timeoffset = 0;
		$this->m_timeannotation = false;
		$this->m_format = false;
		$this->m_dayj = false;
		$this->m_monthj = false;
		$this->m_yearj = false;

		$value = trim( $value ); // ignore whitespace
		if ( $this->m_caption === false ) { // Store the caption now.
			$this->m_caption = $value;
		}

		// if the value is a number, and it has at least six places left of decimal, we know
		// it's not a year, so treat it as a Julian day, but leave it as the caption and wikivalue.
		if ( is_numeric( $value ) && $value >= 100000 ) {
			$this->m_jd = $value;
			$this->m_format = 3;
			$this->JD2Date();
			$this->fracToTime();
			$this->m_wikivalue = $value;
			return true;
		}

		$this->m_wikivalue = $value;
		$filteredvalue = $value; // value without time definition and further abbreviations like PM or BC

		// browse string for special abbreviations referring to time like am, pm
		if ( preg_match( "/([Aa]|[Pp])[Mm]/u", $filteredvalue, $match ) ) {
		  $this->m_timeannotation = strtolower( trim( $match[0] ) );
		  $regexp = "/(\040|T){0,1}" . str_replace( "+", "\+", $match[0] ) . "(\040){0,1}/u"; // delete pm/am, preceding and following chars
		  $filteredvalue = preg_replace( $regexp, '', $filteredvalue ); // value without am/pm
		}

		// browse string in advance for timezone monikers ("EST", "WET", "MESZ", etc.)
		$regexptz = "/A[CEKW]?[DS]T|BST|CXT|[CEW]([DES]|E[DS])T|" .
			"GMT|H(A[DS]T|[AN][ACEPRTY])|IST|M(DT|E(S)?Z|S[DKT])|N[DFS]T|P[DS]T|UTC/u";
		if ( preg_match( $regexptz, $filteredvalue, $match ) ) {
			// Retrieve the offset and store it as the initial time offset value.
			$this->m_timeoffset = $this->m_timeoffset + self::$m_tz[$match[0]] / 24;
			$regexp = "/(\040|T){0,1}" . str_replace( "+", "\+", $match[0] ) . "(\040){0,1}/u"; // delete tz moniker and preceding and following chars
			$filteredvalue = preg_replace( $regexp, '', $filteredvalue ); // value without the tz moniker
		}

		// browse string for special abbreviations referring to year like AD, BC, and OS
		$is_yearbc = false;
		if ( preg_match( "/(A[DM]|BC(E)?|CE|Gr|He|Jl|(M)?JD|OS)/u", $filteredvalue, $match ) ) {
			$this->m_pref = trim( $match[0] );
			if ( ( $this->m_pref == 'BC' ) || ( $this->m_pref == 'BCE' ) ) {
				$is_yearbc = true;
			}
			$regexp = "/(\040|T){0,1}" . str_replace( "+", "\+", $match[0] ) . "(\040){0,1}/u"; // delete ad/bc value and preceding and following chars
			$filteredvalue = preg_replace( $regexp, '', $filteredvalue ); // value without ad/bc
		}

		// handle direct entry of Julian or Modified Julian days here; don't bother browsing for times.
		if ( ( $this->m_pref == 'JD' ) || ( $this->m_pref == 'MJD' ) ) {
			if ( !( is_numeric( $filteredvalue ) ) ) {// Immediate error check
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$this->addError( wfMsgForContent( 'smw_nodatetime', $value ) );
				return true;
			}
			$this->m_jd = ( $this->m_pref == 'JD' ) ? $filteredvalue : ( $filteredvalue + self::MJD_EPOCH );
			$this->m_pref = "Gr";
			$this->m_format = 3; // Specify all three parts of the date (see below)
			$this->JD2Date();
			$this->fracToTime();
			return true;
		}

		// browse string for civilian time value
		if ( preg_match( "/[0-2]?[0-9]:[0-5][0-9](:[0-5][0-9])?([+\-][0-2]?[0-9](:(30|00))?)?/u", $filteredvalue, $match ) ) {
			$time = $match[0];

			// timezone direct offset handling
			if ( preg_match( "/([+\-][0-2]?[0-9](:(30|00))?)/u", $time, $match2 ) ) { // get timezone definition
			  $offset = SMWTimeValue::normalizeTimeValue( $match2[0] );
			  $sign = 1;
			  if ( $offset[0] == '-' ) $sign = - 1;
			  $offset = substr( $offset, 1 );
			  list ( $offhours, $offminutes, $offseconds ) = explode( ':', $offset, 3 );
			  $offset = $sign * ( ( $offhours / 24 ) + ( $offminutes / ( 60 * 24 ) ) + ( $offseconds / ( 3600 * 24 ) ) );
    			  $this->m_timeoffset = $this->m_timeoffset + $offset;
			  $time = str_replace( $match2[0], '', $time );
			}

			list( $hours, $minutes, $seconds ) = explode( ':', SMWTimeValue::normalizeTimeValue( $time ), 3 );

			// am/pm handling
			if ( $this->m_timeannotation != false ) {
			  if ( !strcmp( $this->m_timeannotation, 'am' ) && $hours == 12 ) $hours = 0;
			  if ( !strcmp( $this->m_timeannotation, 'pm' ) && $hours <= 11 ) {
			    $this->m_timeoffset = $this->m_timeoffset -  0.5;
			  }
			}

			$this->m_time = SMWTimeValue::normalizeValue( $hours ) . ":" . SMWTimeValue::normalizeValue( $minutes ) . ":" . SMWTimeValue::normalizeValue( $seconds );
			$regexp = "/(\040|T){0,1}" . str_replace( "+", "\+", $match[0] ) . "(\040){0,1}/u"; // delete time value and preceding and following chars
			$filteredvalue = preg_replace( $regexp, '', $filteredvalue ); // value without time
		}

		// browse string for military time value
		if ( preg_match( "/([0-1][0-9]|2[0-3])[0-5][0-9]([0-5][0-9])?[A-IK-Z]/u", $filteredvalue, $match ) ) {
			$time = $match[0];
			// timezone handling (Zulu, Romeo, Sierra, etc.)
			if ( preg_match( "/[A-IK-Z]/u", $time, $match2 ) ) {// get military timezone offset
				$this->m_timeoffset = $this->m_timeoffset + self::$m_tz[$match2[0]] / 24;
				$time = str_replace( $match2[0], '', $time );// strip away the one-letter moniker
			}
			$hours = substr( $time, 0, 2 );
			$minutes = substr( $time, 2, 2 );
			$seconds = ( strlen( $time ) > 4 ) ? substr( $time, 4, 2 ) : '00';
			$this->m_time = SMWTimeValue::normalizeValue( $hours ) . ":" . SMWTimeValue::normalizeValue( $minutes ) . ":" . SMWTimeValue::normalizeValue( $seconds );
			$regexp = "/(\040|T){0,1}" . str_replace( "+", "\+", $match[0] ) . "(\040){0,1}/u"; // delete time value and preceding and following chars
			$filteredvalue = preg_replace( $regexp, '', $filteredvalue ); // value without time
		}

		// split array in order to separate the date digits
		$array = preg_split( "/[\040|.|,|\-|\/]+/u", $filteredvalue, 3 ); // TODO: support &nbsp and - again;

		// The following code segment creates a band by finding out which role each digit of the entered date can take
		// (date, year, month). The band starts with 1 and for each digit of the entered date a binary code with three
		// bits is attached. Examples:
		//		111 states that the digit can be interpreted as a month, a day or a year
		//		100 digit can just be interpreted as a month
		//		010 digit can just be interpreted as a day
		//		001 digit can just be interpreted as a year
		//		the remaining combinations are also possible (if reasonable)
		// A date consisting of three digits therefore will have a 10 bit band.
		if ( count( $array ) != 0 ) {
			$band = 1;
			foreach ( $array as $tmp ) {
				$band = $band << 3;
				$band = $band | $this->checkDigit( $tmp );
			}
		} else {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError( wfMsgForContent( 'smw_nodatetime', $value ) );
			return true;
		}

		$dateformats = $smwgContLang->getDateFormats(); // get the language dependent date formats

		$digitcount = count( $array ) - 1; // number of digits - 1 is used as an array index for $dateformats
		$found = false;
		$prelimModel = $this->findPrelimModel();
		foreach ( $dateformats[$digitcount] as $format ) { // check whether created band matches dateformats
			if ( !( ~$band & $format ) ) { // check if $format => $band ("the detected band supports the current format")
				$i = 0;
				foreach ( self::$m_formats[$format] as $globalvar ) { // map format digits to internal variables
					$globalvar = 'm_' . $globalvar; // (for searching this file) this is one of: m_year, m_month, m_day
					if ( $prelimModel == 'Jl' ) {
						$globalvar = $globalvar . 'j';
					}
					if ( !$this->$globalvar ) $this->$globalvar = intval( $array[$i] );
					$i++;
				}
				$found = true;
				break;
			}
		}

		// error catching
		if ( !$found ) { // no band matches the entered date
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError( wfMsgForContent( 'smw_nodatetime', $value ) );
			return true;
		} elseif ( ( $this->m_day > 0 ) && ( $this->m_day > self::$m_daysofmonths[$this->m_month] ) ) { // date does not exist in Gregorian calendar
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError( wfMsgForContent( 'smw_nodatetime', $value ) );
			return true;
		} elseif ( ( $this->m_dayj > 0 ) && ( $this->m_dayj > self::$m_daysofmonths[$this->m_monthj] ) ) { // date does not exist in Julian calendar
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError( wfMsgForContent( 'smw_nodatetime', $value ) );
			return true;
		} elseif ( ( $this->m_yearj != false ) && ( $this->m_yearj < - 4713 ) && ( $this->m_timeoffset != 0 ) ) { // no support for time offsets if year < -4713
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$this->addError( wfMsgForContent( 'smw_nodatetime', $value ) );
			return true;
		}

		// Handle OS: Increment the year if earlier than March 25.
		if ( $this->m_pref == 'OS' ) {
			if ( ( $this->m_monthj < 3 ) || ( ( $this->m_monthj == 3 ) && ( $this->m_dayj < 25 ) ) ) {
				$this->m_yearj++;
			}
		}

		// Handle BC: the year is negative.
		if ( $is_yearbc ) {
			if ( $this->m_yearj > 0 ) { // see class documentation on BC, "year 0", and ISO conformance ...
				$this->m_yearj = - ( $this->m_yearj );
			}
		}

		// Make the JD value and handle offset if applicable
		$this->createJD( $this->findModel() );
		if ( $this->m_jd > - 0.5 ) {
			$this->m_jd = $this->m_jd - $this->m_timeoffset;
			$this->JD2Date();
		} else {
			$this->m_day = $this->m_dayj;
			$this->m_month = $this->m_monthj;
			$this->m_year = $this->m_yearj;
		} // Copy Julian registers into Gregorian registers; needed for the XSD value
		return true;
	}

	protected static function findMonth( $label ) {
		global $smwgContLang;
		$retVal = $smwgContLang->findMonth( $label );
		if ( $retVal !== false ) {
			return $retVal;
		}
		$retVal = array_search( $label, self::$m_months );
		if ( $retVal !== false ) {
			return $retVal + 1;
		}
		$retVal = array_search( $label, self::$m_monthsshort );
		if ( $retVal !== false ) {
			return $retVal + 1;
		}
		return false;
	}

	protected function findPrelimModel() {
		if ( ( $this->m_pref == 'BC' ) || ( $this->m_pref == 'BCE' ) || ( $this->m_pref == 'Jl' ) || ( $this->m_pref == 'OS' ) ) {
			return 'Jl'; // Assume Julian model if specified in any way, shape or form.
		}
		return 'Gr';
	}

	protected function checkDigit( $digit ) {
		$prelimModel = $this->findPrelimModel();
		if ( !is_numeric( $digit ) ) { // check for alphanumeric day or month value
			if ( preg_match( "/[0-3]?[0-9](st|nd|rd|th)/u", $digit ) ) { // look for day value terminated by st/nd/th
				$dayVal = intval( substr( $digit, 0, strlen( $digit ) - 2 ) ); // remove st/nd/th
				if ( $prelimModel == 'Jl' ) {
					$this->m_dayj = $dayVal;
				} else {
					$this->m_day = $dayVal;
				}
				return SMW_DAY;
			}
			$monthnumber = SMWTimeValue::findMonth( $digit );
			if ( $monthnumber !== false ) {
				if ( $prelimModel == 'Jl' ) {
					$this->m_monthj = $monthnumber;
				} else {
					$this->m_month = $monthnumber;
				}
				return SMW_MONTH;
			}
			return 0;
		} elseif ( intval( $digit ) >= 1 && intval( $digit ) <= 12 ) { // number can be a month, a day or a year	(111)
			return SMW_DAY_MONTH_YEAR;
		} elseif ( intval( $digit ) >= 1 && intval( $digit ) <= 31 ) { // number can be day or year
			return SMW_DAY_YEAR;
		} elseif ( is_numeric( $digit ) ) { // number can just be a year (011)
			return SMW_YEAR;
		} else {
			return 0;
		}
	}

	// finds the appropriate model to use.
	protected function findModel() {
		if ( ( $this->m_pref == 'BC' ) || ( $this->m_pref == 'BCE' ) ) {
			return 'Jl'; // BC dates are automatically Julian.
		}
		if ( $this->m_pref == 'OS' ) {
			$this->m_pref = ''; // Erase the OS marker; it will not be needed after this.
			return 'Jl'; // Old Style dates are converted per Julian rules.
		}
		if ( ( $this->m_pref == 'Gr' ) || ( $this->m_pref == 'Jl' ) ) {// Specified calendar models
			return $this->m_pref;
		}
		// Model unspecified: must be determined by examination of the date parts.
		if ( $this->m_year > 1582 ) {
			return 'Gr'; // All dates after 1582 are Gregorian dates.
		}
		if ( ( $this->m_year == 1582 ) && ( $this->m_month > 10 ) ) {
			return 'Gr'; // The Gregorian calendar was inaugurated after October 4, 1582.
		}
		if ( ( $this->m_year == 1582 ) && ( $this->m_month == 10 ) && ( $this->m_day > 4 ) ) {
			return 'Gr';
		} // Set $model to 'Jl' and move all specified date parts to the Julian set.
		$this->m_dayj = $this->m_day;
		$this->m_monthj = $this->m_month;
		$this->m_yearj = $this->m_year;
		return 'Jl';
	}

	protected function parseDBkeys( $args ) {
		$this->m_caption = false;
		if ( count( $args ) < 2 ) return;
		$timeparts = explode( 'T', $args[0], 2 );
		if ( count( $timeparts ) != 2 ) return;
		$date = reset( $timeparts );
		$this->m_time = end( $timeparts );
		$d = explode( '/', $date, 3 );
		if ( count( $d ) == 3 ) list( $this->m_year, $this->m_month, $this->m_day ) = $d;
		elseif ( count( $d ) == 2 ) list( $this->m_year, $this->m_month ) = $d;
		elseif ( count( $d ) == 1 ) list( $this->m_year ) = $d;
		if ( $this->m_year < - 4713 ) {
			$this->m_yearj = $this->m_year;
			$this->m_monthj = $this->m_month;
			$this->m_dayj = $this->m_day;
			$this->createJD( 'Jl' ); // This date falls earlier than the Julian Era.
		} else {
			$this->createJD( 'Gr' ); // This is a Gregorian or proleptic Gregorian date
		}
		if ( $this->m_jd > - 0.5 ) {// Back-convert only if the JD is suitable for that
			$this->JD2Date();
		}
		$this->makePrintoutValue();
		$this->m_caption = ( $this->m_jd < self::J1582 ) ? $this->m_julvalue : $this->m_gregvalue;
		$this->m_wikivalue = $this->m_gregvalue;
	}

	/// make sure that existing values are updated: set the format string and force
	/// a reconfiguration of all printouts.
	public function setOutputFormat( $formatstring ) {
		if ( $formatstring != $this->m_outformat ) {
			$this->m_outformat = $formatstring;
			$this->m_gregvalue = false;
			$this->m_julvalue = false;
		}
	}

	public function getShortWikiText( $linked = NULL ) {
		$this->unstub();
		if ( $this->m_caption !== false ) {
			return $this->m_caption;
		} else {
			$this->makePrintoutValue();
			return $this->m_gregvalue;
		}
	}

	public function getShortHTMLText( $linker = NULL ) {
		return $this->getShortWikiText( $linker ); // should be safe (based on xsdvalue)
	}

	public function getLongWikiText( $linked = NULL ) {
		if ( !$this->isValid() ) {
			return $this->getErrorText();
		} else {
			$this->makePrintoutValue();
			return $this->m_jd < self::J1582 ? $this->m_julvalue : $this->m_gregvalue;
		}
	}

	public function getLongHTMLText( $linker = NULL ) {
		return $this->getLongWikiText( $linker );
	}

	public function getDBkeys() {
		$this->unstub();
		if ( $this->m_xsdvalue === false ) {
			$this->m_xsdvalue = $this->m_year . "/" . $this->m_month . "/" . $this->m_day . "T" . $this->m_time;
		}
		return array( $this->m_xsdvalue, $this->m_jd );
	}

	public function getSignature() {
		return 'tf';
	}

	public function getValueIndex() {
		return 1;
	}

	public function getLabelIndex() {
		return 0;
	}

	public function getWikiValue() {
		$this->unstub();
		return $this->m_wikivalue;
	}

	public function getHash() {
		if ( $this->isValid() ) {
			return strval( $this->m_jd );
		} else {
			return implode( "\t", $this->getErrors() );
		}
	}

	public function isNumeric() {
		return true;
	}

	public function getExportData() {
		if ( $this->isValid() ) {
			$lit = new SMWExpLiteral( $this->getXMLSchemaDate(), $this, 'http://www.w3.org/2001/XMLSchema#dateTime' );
			return new SMWExpData( $lit );
		} else {
			return NULL;
		}
	}

	/**
	 * Return the year as a number corresponding to the year in the Julian or
	 * Gregorian calendar and using the astronomical year numbering (0 means 1 BC).
	 */
	public function getYear( $model = 'Gr' ) {
		$this->unstub();
		switch ( $model ) {
			case 'Jl':
				return $this->m_yearj;
			default:
				return $this->m_year;
		}
	}

	/**
	 * Return the month as a number (between 1 and 12) based on the Julian or
	 * Gregorian calendar.
	 * The parameter $default optionally specifies the value returned
	 * if the date is valid but has no explicitly specified month. It can
	 * also be set to FALSE to detect this situation.
	 */
	public function getMonth( $model = 'Gr', $default = 1 ) {
		$this->unstub();
		switch ( $model ) {
			case 'Jl':
				return ( $this->m_monthj != false ) ? $this->m_monthj:$default;
			default:
				return ( $this->m_month != false ) ? $this->m_month:$default;
		}
	}

	/**
	 * Return the day as a number based on the Julian or Gregorian calendar.
	 * The parameter $default optionally specifies the value returned
	 * if the date is valid but has no explicitly specified date. It can
	 * also be set to FALSE to detect this situation.
	 */
	public function getDay( $model = 'Gr', $default = 1 ) {
		$this->unstub();
		switch ( $model ) {
			case 'Jl':
				return ( $this->m_dayj != false ) ? $this->m_dayj:$default;
			default:
				return ( $this->m_day != false ) ? $this->m_day:$default;
		}
	}

	/**
	 * Return the time as a string. The time string has the format HH:MM:SS,
	 * without any timezone information (see class documentaion for details
	 * on current timezone handling).
	 * The parameter $default optionally specifies the value returned
	 * if the date is valid but has no explicitly specified time. It can
	 * also be set to FALSE to detect this situation.
	 */
	public function getTimeString( $default = '00:00:00' ) {
		$this->unstub();
		return ( $this->m_time != false ) ? SMWTimeValue::normalizeTimeValue( $this->m_time ):$default;
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
	 *
	 * @note This function may return year numbers with less or more than 4 digits.
	 */
	public function getXMLSchemaDate( $mindefault = true ) {
		if ( $this->isValid() ) {
			if ( $mindefault ) {
				return number_format( $this->m_year, 0, '.', '' ) . '-' . SMWTimeValue::normalizeValue( $this->getMonth() ) . '-' . SMWTimeValue::normalizeValue( $this->getDay() ) . 'T' . $this->getTimeString();
			} else {
				return number_format( $this->m_year, 0, '.', '' ) . '-' . SMWTimeValue::normalizeValue( $this->getMonth( 12 ) ) . '-' . SMWTimeValue::normalizeValue( $this->getDay( 31 ) ) . 'T' . $this->getTimeString( '23:59:59' );
			}
		} else {
			return false;
		}
	}

	/**
	 * Build preferred values for printout, to be used as captions when setting up values
	 * from the store. Note: Proleptic Hillel II and Biblical values are not permitted.
	 */
	protected function makePrintoutValue() {
		$this->makeGregorianValue();
		$this->makeJulianValue();
	}

	protected function makeGregorianValue() {
		global $smwgContLang;
		if ( $this->m_gregvalue === false ) {
			// MediaWiki date function is not applicable any more (no support for BC Dates)
			if ( ( strtoupper( $this->m_outformat ) == 'ISO' ) || ( $this->m_outformat == '-' ) ) { // ISO8601 date formatting
				if ( $this->m_year > 0 ) {
					$this->m_gregvalue = str_pad( $this->m_year, 4, "0", STR_PAD_LEFT );
				} else {
					$this->m_gregvalue = '-' . str_pad( ( - ( $this->m_year ) ), 4, "0", STR_PAD_LEFT );
				}
				$this->m_gregvalue .= '-'
				   . ( $this->m_month ? str_pad( $this->m_month, 2, "0", STR_PAD_LEFT ):'01' ) . '-'
				   . ( $this->m_day ? str_pad( $this->m_day, 2, "0", STR_PAD_LEFT ):'01' )
				   . ( $this->m_time ? 'T' . $this->m_time:'' );
			} else { // Default date formatting
				if ( $this->m_year > 0 ) {
					$this->m_gregvalue = number_format( $this->m_year, 0, '.', '' ) . ( ( ( $this->m_pref == 'AD' ) || ( $this->m_pref == 'CE' ) ) ? ( ' ' . $this->m_pref ) : '' );
				} else {
					$this->m_gregvalue = number_format( - ( $this->m_year ), 0, '.', '' ) . ( ( $this->m_pref == 'BCE' ) ? ' ' . 'BCE' : ' ' . 'BC' ); // note: there should be no digits after the comma anyway
				}
				if ( $this->m_month ) {
					$this->m_gregvalue =  $smwgContLang->getMonthLabel( $this->m_month ) . " " . $this->m_gregvalue;
				}
				if ( $this->m_day ) {
					$this->m_gregvalue =  $this->m_day . " " . $this->m_gregvalue;
				}
				if ( $this->m_time ) {
					$this->m_gregvalue .= " " . $this->m_time;
				}
			}
		}
	}

	protected function makeJulianValue() {
		global $smwgContLang;
		if ( $this->m_julvalue === false ) {
			// MediaWiki date function is not applicable any more (no support for BC Dates)
			if ( ( strtoupper( $this->m_outformat ) == 'ISO' ) || ( $this->m_outformat == '-' ) ) { // ISO8601 date formatting
				if ( $this->m_yearj > 0 ) {
					$this->m_julvalue = str_pad( $this->m_yearj, 4, "0", STR_PAD_LEFT );
				} else {
					$this->m_julvalue = '-' . str_pad( ( - ( $this->m_yearj ) ), 4, "0", STR_PAD_LEFT );
				}
				$this->m_julvalue .= '-'
				   . ( $this->m_monthj ? str_pad( $this->m_monthj, 2, "0", STR_PAD_LEFT ):'01' ) . '-'
				   . ( $this->m_dayj ? str_pad( $this->m_dayj, 2, "0", STR_PAD_LEFT ):'01' )
				   . ( $this->m_time ? 'T' . $this->m_time:'' );
			} else {
				if ( $this->m_yearj > 0 ) {
					$this->m_julvalue = number_format( $this->m_yearj, 0, '.', '' ) . ( ( ( $this->m_pref == 'AD' ) || ( $this->m_pref == 'CE' ) ) ? ( ' ' . $this->m_pref ) : '' );
				} else {
					$this->m_julvalue = number_format( - ( $this->m_yearj ), 0, '.', '' ) . ( ( $this->m_pref == 'BCE' ) ? ' ' . 'BCE' : ' ' . 'BC' ); // note: there should be no digits after the comma anyway
				}
				if ( $this->m_monthj ) {
					$this->m_julvalue =  $smwgContLang->getMonthLabel( $this->m_monthj ) . " " . $this->m_julvalue;
				}
				if ( $this->m_dayj ) {
					$this->m_julvalue =  $this->m_dayj . " " . $this->m_julvalue;
				}
				if ( $this->m_time ) {
					$this->m_julvalue .= " " . $this->m_time;
				}
			}
		}
	}

	protected static function normalizeValue( $value ) {
		if ( strlen( $value ) == 1 ) {
			$value = "0" . $value;
		}
		return $value;
	}

	protected static function normalizeTimeValue( $value ) {
		$value = SMWTimeValue::normalizeValue( $value );
		$parts = explode( ":", $value );
		switch ( count( $parts ) ) {
		case 1: return $parts[0] . ":00:00";
		case 2: return $parts[0] . ":" . $parts[1] . ":00";
		default: return $value;
		}
	}

	/**
	 * This function adds a time fraction to any Julian Day.
	 */
	 protected function createTime() {
		if ( $this->m_time != false ) { // Calculate fraction only if time is set -- the default time is 0
			list ( $hours, $minutes, $seconds ) = explode( ':', $this->getTimeString(), 3 );
			$time = ( $hours / 24 ) + ( $minutes / ( 60 * 24 ) ) + ( $seconds / ( 3600 * 24 ) );
			$this->m_jd += $time;
		}
	 }

	/**
	 * This function computes a numerical value based on the currently set date. If the year is
	 * greater or equal to -4712 (4713 BC), then (something that is closely inspired by) the Julian Day
	 * (JD) is computed. The JD has the form XXXX.YYYY where XXXX is the number of days having elapsed since
	 * noon on 1 January 4713 BC and YYYY is the elapsed time of the day as fraction of 1.
	 * See http://en.wikipedia.org/wiki/Julian_day
	 * If the year is before -4713, then the computed number XXXX.YYYY has the following form: XXXX is
	 * the number of years BC and YYYY represents the elapsed days of the year as fraction of 1. This
	 * enables even large negative dates using 32bit floats.
	 *
	 * @note The result of this function is used only internally. It should not be assumed to be the
	 * exact JD, even for dates after 4713 BC, unless a proper time-zone offset or moniker is specified.
	 */
	protected function createJD( $model ) {
		switch( $model ) {
			case "Gr":
				$this->gregorian2JD();
				break;
			case "Jl":
				$this->julian2JD();
				break;
		}
		$this->createTime();
	}

	/// Calculate a Julian day according to Gregorian calendar rules
	protected function gregorian2JD() {
		$this->m_jd = 0;
		$a = intval( ( 14 - $this->getMonth( 'Gr' ) ) / 12 );
		$y = $this->m_year + 4800 - $a;
		$m = $this->getMonth( 'Gr' ) + 12 * $a - 3;
		$this->m_jd += $this->getDay( 'Gr' ) + intval( ( 153 * $m + 2 ) / 5 ) + 365 * $y + intval( $y / 4 ) - intval( $y / 100 ) + intval( $y / 400 ) - 32045.5;
		$this->m_format = ( $this->m_day != false ) ? 3 : ( ( $this->m_month != false ) ? 2 : 1 );
	}

	/// Calculate a Julian day according to Julian calendar rules
	protected function julian2JD() {
		if ( $this->m_yearj >= - 4713 ) {
			$this->m_jd = 0;
			$y1 = ( $this->m_yearj < 1 ) ? ( $this->m_yearj + 1 ) : $this->m_yearj;
			$m1 = $this->getMonth( 'Jl' );
			$y2 = ( $m1 <= 2 ) ? ( $y1 - 1 ) : $y1;
			$m2 = ( $m1 <= 2 ) ? ( $m1 + 12 ) : $m1;
			$this->m_jd += intval( ( 365.25 * ( $y2 + 4716 ) ) ) + intval( ( 30.6001 * ( $m2 + 1 ) ) ) + $this->getDay( 'Jl' ) - 1524.5;
		} else { // starting from the time when JD would be negative, use our own "stretched" representation, currently this just ignores local time
			$time = 1 - ( ( $this->getMonth( 'Jl' ) / 12 ) + ( $this->getDay( 'Jl' ) / 365 ) );
			$this->m_jd = $this->m_yearj - $time;
		}
		$this->m_format = ( $this->m_dayj != false ) ? 3 : ( ( $this->m_monthj != false ) ? 2 : 1 );
	}

	/// Convert the Julian Day fraction to the time string.
	protected function fracToTime() {
		$wjd = $this->m_jd + 0.5;
		$fraction = $wjd - intval( $wjd );
		$time = round( $fraction * 3600 * 24 );
		$hours = intval( $time / 3600 );
		$time = $time - $hours * 3600;
		$minutes = intval( $time / 60 );
		$seconds = intval( $time - $minutes * 60 );
		$this->m_time = SMWTimeValue::normalizeValue( $hours ) . ":" . SMWTimeValue::normalizeValue( $minutes ) . ":" . SMWTimeValue::normalizeValue( $seconds );
	}

	/// Convert Julian Day to m_year, m_month, and m_day according to the proper model.
	/// Do NOT invoke AM or Hillel conversion functions if the JD is less than their respective epochs.
	protected function JD2Date() {
		$this->JD2Julian();
		$this->JD2Gregorian();
		if ( $this->m_time != false ) { // Do not fill this in if it was not filled in to begin with
			$this->fracToTime();
		}
	}

	/// Convert Julian Day (see createJD) back to a Gregorian date.
	protected function JD2Gregorian() {
		$j = intval( $this->m_jd + 0.5 ) + 32044;
		$g = intval( $j / 146097 );
		$dg = $j % 146097;
		$c = intval( ( ( intval( $dg / 36524 ) + 1 ) * 3 ) / 4 );
		$dc = $dg - $c * 36524;
		$b = intval( $dc / 1461 );
		$db = $dc % 1461;
		$a = intval(  ( ( intval( $db / 365 ) + 1 ) * 3 ) / 4 );
		$da = $db - ( $a * 365 );
		$y = $g * 400 + $c * 100 + $b * 4 + $a;
		$m = intval( ( $da * 5 + 308 ) / 153 ) - 2;
		$d = $da - intval( ( ( $m + 4 ) * 153 ) / 5 ) + 122;
		$this->m_year = $y - 4800 + intval( ( $m + 2 ) / 12 );
		$this->m_month = ( $this->m_format >= 2 ) ? ( ( $m + 2 ) % 12 + 1 ) : false;
		$this->m_day = ( $this->m_format == 3 ) ? ( $d + 1 ) : false;
		if ( ( $this->m_format == 2 ) && ( $d > 1 ) ) {
			$this->m_month++;
			if ( $this->m_month > 12 ) {
				$this->m_month = 1;
				$this->m_year++;
			}
		}
	}

	/// Convert Julian Day back to a Julian date.
	protected function JD2Julian() {
		$b = intval( $this->m_jd + 0.5 ) + 1524;
		$c = intval( ( $b - 122.1 ) / 365.25 );
		$d = intval( 365.25 * $c );
		$e = intval( ( $b - $d ) / 30.6001 );
		$m = intval( ( $e < 14 ) ? ( $e - 1 ) : ( $e - 13 ) );
		$y = intval( ( $m > 2 ) ? ( $c - 4716 ) : ( $c - 4715 ) );
		$this->m_yearj = ( $y < 1 ) ? ( $y - 1 ) : $y;
		$this->m_monthj = ( $this->m_format >= 2 ) ? $m : false;
		$this->m_dayj = ( $this->m_format == 3 ) ? ( $b - $d - intval( 30.6001 * $e ) ) : false;
	}
}
