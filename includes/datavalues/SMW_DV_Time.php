<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue captures values of dates and times, in many formats,
 * throughout history and pre-history. The implementation can handle dates
 * across history with full precision for storing, and substantial precision
 * for sorting and querying. The range of supported past dates should encompass
 * the Beginning of Time according to most of today's theories. The range of
 * supported future dates is limited more strictly, but it does also allow
 * year numbers in the order of 10^9.
 * 
 * The implementation notices and stores whether parts of a date/time have been
 * omitted (as in "2008" or "May 2007"). For all exporting and sorting
 * purposes, incomplete dates are completed with defaults (usually using the
 * earliest possible time, i.e. interpreting "2008" as "Jan 1 2008 00:00:00").
 * The information on what was unspecified is kept internally for improving
 * behaviour e.g. for outputs (defaults are not printed when querying for a
 * value). This largely uses the precision handling of SMWDITime.
 *
 * 
 * Date formats
 *
 * Dates can be given in many formats, using numbers, month names, and
 * abbreviated month names. The preferred interpretation of ambiguous dates
 * ("1 2 2008" or even "1 2 3 BC") is controlled by the language file, as is
 * the local naming of months. English month names are always supported.
 * 
 * Dates can be given in Gregorian or Julian calendar, set by the token "Jl"
 * or "Gr" in the input. If neither is set, a default is chosen: inputs after
 * October 15, 1582 (the time when the Gregorian calendar was first inaugurated
 * in some parts of the world) are considered Gr, earlier inputs are considered
 * Jl. In addition to Jl and Gr, we support "OS" (Old Style English dates that
 * refer to the use of Julian calendar with a displaced change of year on March
 * 24), JD (direct numerical input in Julian Day notation), and MJD (direct
 * numerical input in Modified Julian Day notation as used in aviation and
 * space flight).
 * 
 * The class does not support the input of negative year numbers but uses the
 * markers "BC"/"BCE" and "AD"/"CE" instead. There is no year 0 in Gregorian or
 * Julian calendars, but the class graciously considers this input to mean year
 * 1 BC(E).
 * 
 * For prehisoric dates before 9999 BC(E) only year numbers are allowed
 * (nothing else makes much sense). At this time, the years of Julian and
 * Gregorian calendar still overlap significantly, so the transition to a
 * purely solar annotation of prehistoric years is smooth. Technically, the
 * class will consider prehistoric dates as Gregorian but very ancient times
 * may be interpreted as desired (probably with reference to a physical notion
 * of time that is not dependent on revolutions of earth around the sun).
 * 
 * 
 * Time formats
 *
 * Times can be in formats like "23:12:45" and "12:30" possibly with additional
 * modifiers "am" or "pm". Timezones are supported: the class knows many 
 * international timezone monikers (e.g. CET or GMT) and also allows time
 * offsets directly after a time (e.g. "10:30-3:30" or "14:45:23+2"). Such
 * offsets always refer to UTC. Timezones are only used on input and are not
 * stored as part of the value.
 * 
 * Time offsets take leap years into account, e.g. the date
 * "Feb 28 2004 23:00+2:00" is equivalent to "29 February 2004 01:00:00", while
 * "Feb 28 1900 23:00+2:00" is equivalent to "1 March 1900 01:00:00".
 *
 * Military time format is supported. This consists of 4 or 6 numeric digits
 * followed by a one-letter timezone code (e.g. 1240Z is equivalent to 12:40
 * UTC).
 * 
 * 
 * I18N
 *
 * Currently, neither keywords like "BCE", "Jl", or "pm", nor timezone monikers
 * are internationalized. Timezone monikers may not require this, other than
 * possibly for Cyrillic (added when needed). Month names are fully
 * internationalized, but English names and abbreviations will also work in all
 * languages. The class also supports ordinal day-of-month annotations like
 * "st" and "rd", again only for English.
 * 
 * I18N includes the preferred order of dates, e.g. to interpret "5 6 2010".
 *
 * @todo Theparsing process can encounter many kinds of well-defined problems
 * but uses only one error message. More detailed reporting should be done.
 * @todo Try to reuse more of MediaWiki's records, e.g. to obtain month names
 * or to format dates. The problem is that MW is based on SIO timestamps that
 * don't extend to very ancient or future dates, and that MW uses PHP functions
 * that are bound to UNIX time.
 *
 * @author Markus Krötzsch
 * @author Fabian Howahl
 * @author Terry A. Hurlbut
 * @ingroup SMWDataValues
 */
class SMWTimeValue extends SMWDataValue {

	protected $m_dataitem_greg = null;
	protected $m_dataitem_jul = null;

	protected $m_wikivalue; // a suitable wiki input value

	// The following are constant (array-valued constants are not supported, hence the declaration as private static variable):
	protected static $m_months = array( 'January', 'February', 'March', 'April' , 'May' , 'June' , 'July' , 'August' , 'September' , 'October' , 'November' , 'December' );
	protected static $m_monthsshort = array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' );
	protected static $m_formats = array( SMW_Y => array( 'y' ), SMW_YM => array( 'y', 'm' ), SMW_MY => array( 'm', 'y' ), SMW_YDM => array( 'y', 'd', 'm' ), SMW_YMD => array( 'y', 'm', 'd' ), SMW_DMY => array( 'd', 'm', 'y' ), SMW_MDY => array( 'm', 'd', 'y' ) );

	/// General purpose time zone monikers and their associated offsets in hours and fractions of hours
	protected static $m_tz = array( 'A' => 1, 'ACDT' => 10.5, 'ACST' => 9.5, 'ADT' => -3, 'AEDT' => 11,
		'AEST' => 10, 'AKDT' => -8, 'AKST' => -9, 'AST' => -4, 'AWDT' => 9, 'AWST' => 8,
		'B' => 2, 'BST' => 1, 'C' => 3, 'CDT' => - 5, 'CEDT' => 2, 'CEST' => 2,
		'CET' => 1, 'CST' => -6, 'CXT' => 7, 'D' => 4, 'E' => 5, 'EDT' => - 4,
		'EEDT' => 3, 'EEST' => 3, 'EET' => 2, 'EST' => - 5, 'F' => 6, 'G' => 7,
		'GMT' => 0, 'H' => 8, 'HAA' => - 3, 'HAC' => - 5, 'HADT' => - 9, 'HAE' => -4,
		'HAP' => -7, 'HAR' => -6, 'HAST' => -10, 'HAT' => -2.5, 'HAY' => -8,
		'HNA' => -4, 'HNC' => -6, 'HNE' => -5, 'HNP' => -8, 'HNR' => -7, 'HNT' => -3.5,
		'HNY' => -9, 'I' => 9, 'IST' => 1, 'K' => 10, 'L' => 11, 'M' => 12,
		'MDT' => -6, 'MESZ' => 2, 'MEZ' => 1, 'MSD' => 4, 'MSK' => 3, 'MST' => -7,
		'N' => -1, 'NDT' => -2.5, 'NFT' => 11.5, 'NST' => -3.5, 'O' => -2, 'P' => -3 ,
		'PDT' => -7, 'PST' => -8, 'Q' => - 4, 'R' => - 5, 'S' => -6, 'T' => -7,
		'U' => -8, 'UTC' => 0, 'V' => - 9, 'W' => -10, 'WDT' => 9, 'WEDT' => 1,
		'WEST' => 1, 'WET' => 0, 'WST' => 8, 'X' => -11, 'Y' => -12, 'Z' => 0 );
	/// Military time zone monikers and their associated offsets in hours
	protected static $m_miltz = array( 'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6,
		'G' => 7, 'H' => 8, 'I' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => -1, 'O' => -2,
		'P' => -3, 'Q' => -4, 'R' => -5, 'S' => -6, 'T' => -7, 'U' => -8, 'V' => -9,
		'W' => -10, 'X' => -11, 'Y' => -12, 'Z' => 0 );

	/// Moment of switchover to Gregorian calendar.
	const J1582 = 2299160.5;
	/// Offset of Julian Days for Modified JD inputs.
	const MJD_EPOCH = 2400000.5;
	/// The year before which we do not accept anything but year numbers and largely discourage calendar models.
	const PREHISTORY = -10000;

	protected function parseUserValue( $value ) {
		$value = trim( $value ); // ignore whitespace
		$this->m_wikivalue = $value;
		if ( $this->m_caption === false ) { // Store the caption now.
			$this->m_caption = $value;
		}
		$this->m_dataitem = null;

		/// TODO Direct JD input currently cannot cope with decimal numbers
		$datecomponents = array();
		$calendarmodel = $era = $hours = $minutes = $seconds = $timeoffset = false;
		
		// Check if it's parseable by wfTimestamp when it's not a year (which is wrongly interpreted).
		if ( strlen( $value ) != 4 && wfTimestamp( TS_MW, $value ) !== false ) {
			$timeStamp = wfTimestamp( TS_MW, $value );
			
			$this->m_dataitem = new SMWDITime(
				SMWDITime::CM_GREGORIAN,
				substr( $timeStamp, 0, 4 ),
				substr( $timeStamp, 4, 2 ),
				substr( $timeStamp, 6, 2 ),
				substr( $timeStamp, 8, 2 ),
				substr( $timeStamp, 10, 2 ),
				substr( $timeStamp, 12, 2 )
			);
		}
		else if ( $this->parseDateString( $value, $datecomponents, $calendarmodel, $era, $hours, $minutes, $seconds, $timeoffset ) ) {
			if ( ( $calendarmodel === false ) && ( $era === false ) && ( count( $datecomponents ) == 1 ) && ( intval( end( $datecomponents ) ) >= 100000 ) ) {
				$calendarmodel = 'JD'; // default to JD input if a single number was given as the date
			}
			
			if ( ( $calendarmodel == 'JD' ) || ( $calendarmodel == 'MJD' ) ) {
				if ( ( $era === false ) && ( $hours === false ) && ( $timeoffset == 0 ) ) {
					try {
						$jd = floatval( reset( $datecomponents ) );
						if ( $calendarmodel == 'MJD' ) $jd += self::MJD_EPOCH;
						$this->m_dataitem = SMWDITime::newFromJD( $jd, SMWDITime::CM_GREGORIAN, SMWDITime::PREC_YMDT, $this->m_typeid );
					} catch ( SMWDataItemException $e ) {
						smwfLoadExtensionMessages( 'SemanticMediaWiki' );
						$this->addError( wfMsgForContent( 'smw_nodatetime', $this->m_wikivalue ) );
					}
				} else {
					smwfLoadExtensionMessages( 'SemanticMediaWiki' );
					$this->addError( wfMsgForContent( 'smw_nodatetime', $this->m_wikivalue ) );
				}
			} else {
				$this->setDateFromParsedValues( $datecomponents, $calendarmodel, $era, $hours, $minutes, $seconds, $timeoffset );
			}
		}
		
		if ( $this->m_dataitem === null ) { // make sure that m_dataitem is set in any case
			$this->m_dataitem = new SMWDITime( SMWDITime::CM_GREGORIAN, 32202 );
		}
	}

	/**
	 * Parse the given string to check if it a date/time value.
	 * The function sets the provided call-by-ref values to the respective
	 * values. If errors are encountered, they are added to the objects
	 * error list and false is returned. Otherwise, true is returned.
	 * @param $string string input time representation, e.g. "12 May 2007 13:45:23-3:30"
	 * @param $datecomponents array of strings that might belong to the specification of a date
	 * @param $calendarmodesl string if model was set in input, otherwise false
	 * @param $era string '+' or '-' if provided, otherwise false
	 * @param $hours integer set to a value between 0 and 24
	 * @param $minutes integer set to a value between 0 and 59
	 * @param $seconds integer set to a value between 0 and 59, or false if not given
	 * @param $timeoffset double set to a value for time offset (e.g. 3.5), or false if not given
	 * @return boolean stating if the parsing succeeded
	 * @todo This method in principle allows date parsing to be internationalized further. Should be done.
	 */
	protected function parseDateString( $string, &$datecomponents, &$calendarmodel, &$era, &$hours, &$minutes, &$seconds, &$timeoffset ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		// crude preprocessing for supporting different date separation characters;
		// * this does not allow localized time notations such as "10.34 pm"
		// * this creates problems with keywords that contain "." such as "p.m."
		// * yet "." is an essential date separation character in languages such as German
		$parsevalue = str_replace( array( '/', '.', '&nbsp;', ',' ), array( '-', ' ', ' ', ' ' ), $string );

		$matches = preg_split( "/([T]?[0-2]?[0-9]:[\:0-9]+[+\-]?[0-2]?[0-9\:]+|[a-z,A-Z]+|[0-9]+|[ ])/u", $parsevalue , -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		$datecomponents = array();
		$calendarmodel = $timezoneoffset = $era = $ampm = false;
		$hours = $minutes = $seconds = $timeoffset = false;
		$unclearparts = array();
		$prevmatchwasnumber = $matchisnumber = false; // used for looking back; numbers are days/months/years by default but may be re-interpreted if certain further symbols are found
		$prevmatchwasdate = $matchisdate = false; // used for ensuring that date parts are in one block
		foreach ( $matches as $match ) {
			$prevmatchwasnumber = $matchisnumber;
			$prevmatchwasdate   = $matchisdate;
			$matchisnumber = $matchisdate = false;
			if ( $match == ' ' ) {
				$matchisdate = $prevmatchwasdate; // spaces in dates do not end the date
			} elseif ( $match == '-' ) { // can only occur separately between date components
				$datecomponents[] = $match; // we check later if this makes sense
				$matchisdate = true;
			} elseif ( is_numeric( $match ) &&
			           ( $prevmatchwasdate || ( count( $datecomponents ) == 0 ) ) ) {
				$datecomponents[] = $match;
				$matchisnumber = true;
				$matchisdate = true;
			} elseif ( ( $era === false ) && ( in_array( $match, array( 'AD', 'CE' ) ) ) ) {
				$era = '+';
			} elseif ( ( $era === false ) && (  in_array( $match, array( 'BC', 'BCE' ) ) ) ) {
				$era = '-';
			} elseif ( ( $calendarmodel === false ) && ( in_array( $match, array( 'Gr', 'He', 'Jl', 'MJD', 'JD', 'OS' ) ) ) ) {
				$calendarmodel = $match;
			} elseif (  ( $ampm === false ) && ( ( strtolower( $match ) == 'am' ) || ( strtolower( $match ) == 'pm' ) ) ) {
				$ampm = strtolower( $match );
			} elseif (  ( $hours === false ) && ( self::parseTimeString( $match, $hours, $minutes, $seconds, $timeoffset ) ) ) {
				// nothing to do
			} elseif ( ( $hours === true ) && ( $timezoneoffset === false ) &&
			           ( array_key_exists( $match, self::$m_tz ) ) ) {
				// only accept timezone if time has already been set
				$timezoneoffset = self::$m_tz[ $match ];
			} elseif ( ( $prevmatchwasnumber ) && ( $hours === false ) && ( $timezoneoffset === false ) &&
			           ( array_key_exists( $match, self::$m_miltz ) ) &&
				   ( self::parseMilTimeString( end( $datecomponents ), $hours, $minutes, $seconds ) ) ) {
					// military timezone notation is found after a number -> re-interpret the number as military time
					array_pop( $datecomponents );
					$timezoneoffset = self::$m_miltz[ $match ];
			} elseif ( ( $prevmatchwasdate || ( count( $datecomponents ) == 0 ) ) &&
				   $this->parseMonthString( $match, $monthname ) ) {
				$datecomponents[] = $monthname;
				$matchisdate = true;
			} elseif ( $prevmatchwasnumber && $prevmatchwasdate && ( in_array( $match, array( 'st', 'nd', 'rd', 'th' ) ) ) ) {
				$datecomponents[] = 'd' . strval( array_pop( $datecomponents ) ); // must be a day; add standard marker
				$matchisdate = true;
			} else {
				$unclearparts[] = $match;
			}
		}
		// Useful for debugging:
		// 		print "\n\n Results \n\n";
		// 		debug_zval_dump( $datecomponents );
		// 		print "\ncalendarmodel: $calendarmodel   \ntimezoneoffset: $timezoneoffset  \nera: $era  \nampm: $ampm  \nh: $hours  \nm: $minutes  \ns:$seconds  \ntimeoffset: $timeoffset  \n";
		// 		debug_zval_dump( $unclearparts );

		// Abort if we found unclear or over-specific information:
		if ( ( count( $unclearparts ) != 0 ) ||
		     ( ( $timezoneoffset !== false ) && ( $timeoffset !== false ) ) ) {
			$this->addError( wfMsgForContent( 'smw_nodatetime', $this->m_wikivalue ) );
			return false;
		}
		$timeoffset = $timeoffset + $timezoneoffset;
		// Check if the a.m. and p.m. information is meaningful
		if ( ( $ampm !== false ) && ( ( $hours > 12 ) || ( $hours == 0 ) ) ) { // Note: the == 0 check subsumes $hours===false
			$this->addError( wfMsgForContent( 'smw_nodatetime', $this->m_wikivalue ) );
			return false;
		} elseif ( ( $ampm == 'am' ) && ( $hours == 12 ) ) {
			$hours = 0;
		} elseif ( ( $ampm == 'pm' ) && ( $hours < 12 ) ) {
			$hours += 12;
		}
		return true;
	}

	/**
	 * Parse the given string to check if it encodes an international time.
	 * If successful, the function sets the provided call-by-ref values to
	 * the respective numbers and returns true. Otherwise, it returns
	 * false and does not set any values.
	 * @note This method is only temporarily public for enabling SMWCompatibilityHelpers. Do not use it directly in your code.
	 *
	 * @param $string string input time representation, e.g. "13:45:23-3:30"
	 * @param $hours integer between 0 and 24
	 * @param $minutes integer between 0 and 59
	 * @param $seconds integer between 0 and 59, or false if not given
	 * @param $timeoffset double for time offset (e.g. 3.5), or false if not given
	 * @return boolean stating if the parsing succeeded
	 */
	public static function parseTimeString( $string, &$hours, &$minutes, &$seconds, &$timeoffset ) {
		if ( !preg_match( "/^[T]?([0-2]?[0-9]):([0-5][0-9])(:[0-5][0-9])?(([+\-][0-2]?[0-9])(:(30|00))?)?$/u", $string, $match ) ) {
			return false;
		} else {
			$nhours = intval( $match[1] );
			$nminutes = $match[2] ? intval( $match[2] ) : false;
			if ( ( count( $match ) > 3 ) && ( $match[3] != '' ) ) {
				$nseconds = intval( substr( $match[3], 1 ) );
			} else {
				$nseconds = false;
			}
			if ( ( $nhours < 25 ) && ( ( $nhours < 24 ) || ( $nminutes + $nseconds == 0 ) ) ) {
				$hours = $nhours;
				$minutes = $nminutes;
				$seconds = $nseconds;
				if ( ( count( $match ) > 5 ) && ( $match[5] != '' ) ) {
					$timeoffset = intval( $match[5] );
					if ( ( count( $match ) > 7 ) && ( $match[7] == '30' ) ) {
						$timeoffset += 0.5;
					}
				} else {
					$timeoffset = false;
				}
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Parse the given string to check if it encodes a "military time".
	 * If successful, the function sets the provided call-by-ref values to
	 * the respective numbers and returns true. Otherwise, it returns
	 * false and does not set any values.
	 * @param $string string input time representation, e.g. "134523"
	 * @param $hours integer between 0 and 24
	 * @param $minutes integer between 0 and 59
	 * @param $seconds integer between 0 and 59, or false if not given
	 * @return boolean stating if the parsing succeeded
	 */
	protected static function parseMilTimeString( $string, &$hours, &$minutes, &$seconds ) {
		if ( !preg_match( "/^([0-2][0-9])([0-5][0-9])([0-5][0-9])?$/u", $string, $match ) ) {
			return false;
		} else {
			$nhours = intval( $match[1] );
			$nminutes = $match[2] ? intval( $match[2] ) : false;
			$nseconds = ( ( count( $match ) > 3 ) && $match[3] ) ? intval( $match[3] ) : false;
			if ( ( $nhours < 25 ) && ( ( $nhours < 24 ) || ( $nminutes + $nseconds == 0 ) ) ) {
				$hours = $nhours;
				$minutes = $nminutes;
				$seconds = $nseconds;
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Parse the given string to check if it refers to the string name ot
	 * abbreviation of a month name. If yes, it is replaced by a normalized
	 * month name (placed in the call-by-ref parameter) and true is
	 * returned. Otherwise, false is returned and $monthname is not changed.
	 * @param $string string month name or abbreviation to parse
	 * @param $monthname string with standard 3-letter English month abbreviation
	 * @return boolean stating whether a month was found
	 */
	protected static function parseMonthString( $string, &$monthname ) {
		global $smwgContLang;
		$monthnum = $smwgContLang->findMonth( $string ); // takes precedence over English month names!
		if ( $monthnum !== false ) {
			$monthnum -= 1;
		} else {
			$monthnum = array_search( $string, self::$m_months ); // check English names
		}
		if ( $monthnum !== false ) {
			$monthname = self::$m_monthsshort[ $monthnum ];
			return true;
		} elseif ( array_search( $string, self::$m_monthsshort ) !== false ) {
			$monthname = $string;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Validate and interpret the date components as retrieved when parsing
	 * a user input. The method takes care of guessing how a list of values
	 * such as "10 12 13" is to be interpreted using the current language
	 * settings. The result is stored in the call-by-ref parameter
	 * $date that uses keys 'y', 'm', 'd' and contains the respective
	 * numbers as values, or false if not specified. If errors occur, error
	 * messages are added to the objects list of errors, and false is
	 * returned. Otherwise, true is returned.
	 * @param $datecomponents array of strings that might belong to the specification of a date
	 * @param $date array set to result
	 * @return boolean stating if successful
	 */
	protected function interpretDateComponents( $datecomponents, &$date ) {
		global $smwgContLang;
		// The following code segment creates a bit vector to encode
		// which role each digit of the entered date can take (day,
		// year, month). The vector starts with 1 and contains three
		// bits per date component, set ot true whenever this component
		// could be a month, a day, or a year (this is the order).
		// Examples:
		//   100 component could only be a month
		//   010 component could only be a day
		//   001 component could only be a year
		//   011 component could be a day or a year but no month etc.
		// For three components, we thus get a 10 digit bit vector.
		$datevector = 1;
		$propercomponents = array();
		$justfounddash = true; // avoid two dashes in a row, or dashes at the end
		$error = false;
		$numvalue = 0;
		foreach ( $datecomponents as $component ) {
			if ( $component == "-" ) {
				if ( $justfounddash ) {
					$error = true;
					break;
				}
				$justfounddash = true;
			} else {
				$justfounddash = false;
				$datevector = ( $datevector << 3 ) | $this->checkDateComponent( $component, $numvalue );
				$propercomponents[] = $numvalue;
			}
		}
		if ( ( $error ) || ( $justfounddash ) || ( count( $propercomponents ) == 0 ) || ( count( $propercomponents ) > 3 ) ) {
			$this->addError( wfMsgForContent( 'smw_nodatetime', $this->m_wikivalue ) );
			return false;
		}
		// Now use the bitvector to find the preferred interpretation of the date components:
		$dateformats = $smwgContLang->getDateFormats();
		$date = array( 'y' => false, 'm' => false, 'd' => false );
		foreach ( $dateformats[count( $propercomponents ) - 1] as $formatvector ) {
			if ( !( ~$datevector & $formatvector ) ) { // check if $formatvector => $datevector ("the input supports the format")
				$i = 0;
				foreach ( self::$m_formats[$formatvector] as $fieldname ) {
					$date[$fieldname] = $propercomponents[$i];
					$i += 1;
				}
				break;
			}
		}
		if ( $date['y'] === false ) { // no band matches the entered date
			$this->addError( wfMsgForContent( 'smw_nodatetime', $this->m_wikivalue ) );
			return false;
		}
		return true;
	}

	/**
	 * Initialise data from the provided intermediate results after
	 * parsing, assuming that a conventional date notation is used.
	 * If errors occur, error messages are added to the objects list of
	 * errors, and false is returned. Otherwise, true is returned.
	 * @param $datecomponents array of strings that might belong to the specification of a date
	 * @param $calendarmodesl string if model was set in input, otherwise false
	 * @param $era string '+' or '-' if provided, otherwise false
	 * @param $hours integer value between 0 and 24
	 * @param $minutes integer value between 0 and 59
	 * @param $seconds integer value between 0 and 59, or false if not given
	 * @param $timeoffset double value for time offset (e.g. 3.5), or false if not given
	 * @return boolean stating if successful
	 */
	protected function setDateFromParsedValues( $datecomponents, $calendarmodel, $era, $hours, $minutes, $seconds, $timeoffset ) {
		$date = false;
		if ( !$this->interpretDateComponents( $datecomponents, $date ) ) {
			return false;
		}

		// Handle BC: the year is negative.
		if ( ( $era == '-' ) && ( $date['y'] > 0 ) ) { // see class documentation on BC, "year 0", and ISO conformance ...
			$date['y'] = -( $date['y'] );
		}
		// Old Style is a special case of Julian calendar model where the change of the year was 25 March:
		if ( ( $calendarmodel == 'OS' ) &&
		     ( ( $date['m'] < 3 ) || ( ( $date['m'] == 3 ) && ( $date['d'] < 25 ) ) ) ) {
			$date['y']++;
		}

		$calmod = $this->getCalendarModel( $calendarmodel, $date['y'], $date['m'], $date['d'] );
		try {
			$this->m_dataitem = new SMWDITime( $calmod, $date['y'], $date['m'], $date['d'], $hours, $minutes, $seconds, $this->m_typeid );
		} catch ( SMWDataItemException $e ) {
			$this->addError( wfMsgForContent( 'smw_nodatetime', $this->m_wikivalue ) );
			return false;
		}

		// Having more than years or specifying a calendar model does
		// not make sense for prehistoric dates, and our calendar
		// conversion would not be reliable if JD numbers get too huge:
		if ( ( $date['y'] <= self::PREHISTORY ) && 
		     ( ( $this->m_dataitem->getPrecision() > SMWDITime::PREC_Y ) || ( $calendarmodel !== false ) ) ) {
			$this->addError( wfMsgForContent( 'smw_nodatetime', $this->m_wikivalue ) );
			return false;
		}
		if ( $timeoffset != 0 ) {
			$newjd = $this->m_dataitem->getJD() - $timeoffset / 24;
			try {
				$this->m_dataitem = SMWDITime::newFromJD( $newjd, $calmod, $this->m_dataitem->getPrecision(), $this->m_typeid );
			} catch ( SMWDataItemException $e ) {
				$this->addError( wfMsgForContent( 'smw_nodatetime', $this->m_wikivalue ) );
				return false;
			}
		}
		return true;
	}

	/**
	 * Check which roles a string component might play in a date, and
	 * set the call-by-ref parameter to the proper numerical
	 * representation. The component string has already been normalized to
	 * be either a plain number, a month name, or a plain number with "d"
	 * pre-pended. The result is a bit vector to indicate the possible
	 * interpretations.
	 * @param $component string
	 * @param $numvalue integer representing the components value
	 * @return integer that encodes a three-digit bit vector
	 */
	protected static function checkDateComponent( $component, &$numvalue ) {
		if ( $component == '' ) { // should not happen
			$numvalue = 0;
			return 0;
		} elseif ( is_numeric( $component ) ) {
			$numvalue = intval( $component );
			if ( ( $numvalue >= 1 ) && ( $numvalue <= 12 ) ) {
				return SMW_DAY_MONTH_YEAR; // can be a month, day or year
			} elseif ( ( $numvalue >= 1 ) && ( $numvalue <= 31 ) ) { 
				return SMW_DAY_YEAR; // can be day or year
			} else { // number can just be a year
				return SMW_YEAR;
			}
		} elseif ( $component{0} == 'd' ) { // already marked as day
			if ( is_numeric( substr( $component, 1 ) ) ) {
				$numvalue = intval( substr( $component, 1 ) );
				return ( ( $numvalue >= 1 ) && ( $numvalue <= 31 ) ) ? SMW_DAY : 0;
			} else {
				return 0;
			}
		} else {
			$monthnum = array_search( $component, self::$m_monthsshort );
			if ( $monthnum !== false ) {
				$numvalue = $monthnum + 1;
				return SMW_MONTH;
			} else {
				return 0;
			}
		}
	}

	/**
	 * Determine the calender model under which an input should be
	 * interpreted based on the given input data.
	 * @param $presetmodel mixed string related to a user input calendar model (OS, Jl, Gr) or false
	 * @param $year integer of the given year (adjusted for BC(E), i.e. possibly negative)
	 * @param $month mixed integer of the month or false
	 * @param $day mixed integer of the day or false
	 * @return integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 */
	protected function getCalendarModel( $presetmodel, $year, $month, $day ) {
		if ( $presetmodel == 'OS' ) { // Old Style is a notational convention of Julian dates only
			$presetmodel = 'Jl';
		}
		if ( $presetmodel == 'Gr' ) {
			return SMWDITime::CM_GREGORIAN;
		} elseif ( $presetmodel == 'Jl' ) {
			return SMWDITime::CM_JULIAN;
		}
		if ( ( $year > 1582 ) ||
		     ( ( $year == 1582 ) && ( $month > 10 ) ) ||
		     ( ( $year == 1582 ) && ( $month == 10 ) && ( $day > 4 ) ) ) {
			return SMWDITime::CM_GREGORIAN;
		} elseif ( $year > self::PREHISTORY ) {
			return SMWDITime::CM_JULIAN;
		} else {
			// proleptic Julian years at some point deviate from the count of complete revolutions of the earth around the sun
			// hence assume that earlier date years are Gregorian (where this effect is very weak only)
			// This is mostly for internal use since we will not allow users to specify calendar models at this scale
			return SMWDITime::CM_GREGORIAN;
		}
	}

	/**
	 * @see SMWDataValue::loadDataItem()
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	protected function loadDataItem( SMWDataItem $dataItem ) {
		if ( $dataItem->getDIType() == SMWDataItem::TYPE_TIME ) {
			$this->m_dataitem = $dataItem;
			$this->m_caption = $this->m_wikivalue = false;
			return true;
		} else {
			return false;
		}
	}

	public function getShortWikiText( $linked = NULL ) {
		return ( $this->m_caption !== false ) ? $this->m_caption : $this->getPreferredCaption();
	}

	public function getShortHTMLText( $linker = NULL ) {
		return $this->getShortWikiText( $linker ); // safe in HTML
	}

	public function getLongWikiText( $linked = NULL ) {
		return  $this->isValid() ? $this->getPreferredCaption() : $this->getErrorText();
	}

	public function getLongHTMLText( $linker = NULL ) {
		return $this->getLongWikiText( $linker ); // safe in HTML
	}

	/// @todo The preferred caption may not be suitable as a wiki value (i.e. not parsable).
	public function getWikiValue() {
		return $this->m_wikivalue ? $this->m_wikivalue : $this->getPreferredCaption();
	}

	public function isNumeric() {
		return true;
	}

	/**
	 * Return the year number in the given calendar model, or false if
	 * this number is not available (typically when attempting to get
	 * prehistoric Julian calendar dates). As everywhere in this class,
	 * there is no year 0.
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @return mixed typically a number but possibly false
	 */
	public function getYear( $calendarmodel = SMWDITime::CM_GREGORIAN ) {
		$di = $this->getDataForCalendarModel( $calendarmodel );
		if ( $di !== null ) {
			return $di->getYear();
		} else {
			return false;
		}
	}

	/**
	 * Return the month number in the given calendar model, or false if
	 * this number is not available (typically when attempting to get
	 * prehistoric Julian calendar dates).
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @param $default value to return if month is not set at our level of precision
	 * @return mixed typically a number but possibly anything given as $default
	 */
	public function getMonth( $calendarmodel = SMWDITime::CM_GREGORIAN, $default = 1 ) {
		$di = $this->getDataForCalendarModel( $calendarmodel );
		if ( $di !== null ) {
			return ( $di->getPrecision() >= SMWDITime::PREC_YM ) ? $di->getMonth() : $default;
		} else {
			return false;
		}
	}

	/**
	 * Return the day number in the given calendar model, or false if this
	 * number is not available (typically when attempting to get
	 * prehistoric Julian calendar dates).
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @param $default value to return if day is not set at our level of precision
	 * @return mixed typically a number but possibly anything given as $default
	 */
	public function getDay( $calendarmodel = SMWDITime::CM_GREGORIAN, $default = 1 ) {
		$di = $this->getDataForCalendarModel( $calendarmodel );
		if ( $di !== null ) {
			return ( $di->getPrecision() >= SMWDITime::PREC_YMD ) ? $di->getDay() : $default;
		} else {
			return false;
		}
	}

	/**
	 * Return the time as a string. The time string has the format HH:MM:SS,
	 * without any timezone information (see class documentation for details
	 * on current timezone handling).
	 * The parameter $default optionally specifies the value returned
	 * if the date is valid but has no explicitly specified time. It can
	 * also be set to false to detect this situation.
	 */
	public function getTimeString( $default = '00:00:00' ) {
		if ( $this->m_dataitem->getPrecision() < SMWDITime::PREC_YMDT ) {
			return $default;
		} else {
			return sprintf( "%02d", $this->m_dataitem->getHour() ) . ':' .
			       sprintf( "%02d", $this->m_dataitem->getMinute()) . ':' .
			       sprintf( "%02d", $this->m_dataitem->getSecond() );
		}
	}

	/**
	 * @deprecated This method is now called getISO8601Date(). It will vanish before SMW 1.7.
	 */
	public function getXMLSchemaDate( $mindefault = true ) {
		return $this->getISO8601Date( $mindefault );
	}

	/**
	 * Compute a string representation that largely follows the ISO8601
	 * standard of representing dates. Large year numbers may have more
	 * than 4 digits, which is not strictly conforming to the standard.
	 * The date includes year, month, and day regardless of the input
	 * precision, but will only include time when specified.
	 * 
	 * Conforming to the 2000 version of ISO8601, year 1 BC(E) is
	 * represented as "0000", year 2 BC(E) as "-0001" and so on.
	 *
	 * @param $mindefault boolean determining whether values below the
	 * precision of our input should be completed with minimal or maximal
	 * conceivable values
	 * @return string
	 */
	public function getISO8601Date( $mindefault = true ) {
		$yearnum = ( $this->getYear() > 0 ) ? $this->getYear() : 1 - $this->getYear();
		$result = ( $this->getYear() > 0 ) ? '' : '-';
		$monthnum = $this->getMonth( SMWDITime::CM_GREGORIAN, ( $mindefault ? 1 : 12 ) );
		$result .= str_pad( $this->getYear(), 4, "0", STR_PAD_LEFT ) .
			  '-' . str_pad( $monthnum, 2, "0", STR_PAD_LEFT );
		if ( !$mindefault && ( $this->m_dataitem->getPrecision() < SMWDITime::PREC_YMD ) ) {
			$maxday = SMWDITime::getDayNumberForMonth( $monthnum, $this->getYear(), SMWDITime::CM_GREGORIAN );
			$result .= '-' . str_pad( $this->getDay( SMWDITime::CM_GREGORIAN, $maxday ), 2, "0", STR_PAD_LEFT );
		} else {
			$result .= '-' . str_pad( $this->getDay(), 2, "0", STR_PAD_LEFT );
		}
		if ( $this->m_dataitem->getPrecision() == SMWDITime::PREC_YMDT ) {
			$result .= 'T' . $this->getTimeString( ( $mindefault ? '00:00:00' : '23:59:59' ) );
		}
		return $result;
	}

	/**
	 * Get the current data in the specified calendar model. Conversion is
	 * not done for prehistoric dates (where it might lead to precision
	 * errors and produce results that are not meaningful). In this case,
	 * null might be returned if no data in the specified format is
	 * available.
	 * @param $calendarmodel integer one of SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @return SMWDITime
	 */
	protected function getDataForCalendarModel( $calendarmodel ) {
		if ( $this->m_dataitem->getYear() <= self::PREHISTORY ) {
			return ( $this->m_dataitem->getCalendarModel() == $calendarmodel ) ? $this->m_dataitem : null;
		} elseif ( $calendarmodel == SMWDITime::CM_GREGORIAN ) {
			if ( $this->m_dataitem_greg === null ) {
				$this->m_dataitem_greg = $this->m_dataitem->getForCalendarModel( SMWDITime::CM_GREGORIAN );
			}
			return $this->m_dataitem_greg;
		} else {
			if ( $this->m_dataitem_jul === null ) {
				$this->m_dataitem_jul = $this->m_dataitem->getForCalendarModel( SMWDITime::CM_JULIAN );
			}
			return $this->m_dataitem_jul;
		}
	}

	/**
	 * Compute a suitable string to display the given date item.
	 * @note MediaWiki's date functions are not applicable for the range of historic dates we support.
	 * 
	 * @since 1.6
	 * 
	 * @param SMWDITime $dataitem
	 * 
	 * @return string
	 * @todo Internationalize the CE and BCE strings.
	 */
	public function getCaptionFromDataitem( SMWDITime $dataitem ) {
		global $smwgContLang;
		if ( $dataitem->getYear() > 0 ) {
			$cestring = '';
			$result = number_format( $dataitem->getYear(), 0, '.', '' ) . ( $cestring ? ( ' ' . $cestring ) : '' );
		} else {
			$bcestring = 'BC';
			$result = number_format( -( $dataitem->getYear() ), 0, '.', '' ) . ( $bcestring ? ( ' ' . $bcestring ) : '' );
		}
		if ( $dataitem->getPrecision() >= SMWDITime::PREC_YM ) {
			$result =  $smwgContLang->getMonthLabel( $dataitem->getMonth() ) . " " . $result;
		}
		if ( $dataitem->getPrecision() >= SMWDITime::PREC_YMD ) {
			$result =  $dataitem->getDay() . " " . $result;
		}
		if ( $dataitem->getPrecision() >= SMWDITime::PREC_YMDT ) {
			$result .= " " . $this->getTimeString();
		}
		return $result;
	}

	/**
	 * Compute a suitable string to display this date, taking into account
	 * the output format and the preferrable calendar models for the data.
	 * @note MediaWiki's date functions are not applicable for the range of historic dates we support.
	 * @return string
	 */
	protected function getPreferredCaption() {
		if ( ( strtoupper( $this->m_outformat ) == 'ISO' ) || ( $this->m_outformat == '-' ) ) {
			return $this->getISO8601Date();
		} else {
			if ( $this->m_dataitem->getYear() <= self::PREHISTORY ) {
				return $this->getCaptionFromDataitem( $this->m_dataitem ); // should be Gregorian, but don't bother here
			} elseif ( $this->m_dataitem->getJD() < self::J1582 ) {
				return $this->getCaptionFromDataitem( $this->getDataForCalendarModel( SMWDITime::CM_JULIAN ) );
			} else {
				return $this->getCaptionFromDataitem( $this->getDataForCalendarModel( SMWDITime::CM_GREGORIAN ) );
			}
		}
	}

}
