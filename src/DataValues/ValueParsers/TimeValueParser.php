<?php

namespace SMW\DataValues\ValueParsers;

use SMW\DataValues\Time\Components;
use SMW\DataValues\Time\Timezone;
use SMW\Localizer;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Markus Krötzsch
 * @author Fabian Howahl
 * @author Terry A. Hurlbut
 * @author mwjames
 */
class TimeValueParser implements ValueParser {

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @var string
	 */
	private $userValue = '';

	/**
	 * @var array
	 */
	private $languageCode = 'en';

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ) {
		$this->languageCode = $languageCode;
	}

	/**
	 * @since 3.0
	 */
	public function clearErrors() {
		$this->errors = [];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $userValue
	 *
	 * @return string|false
	 */
	public function parse( $userValue ) {

		$this->errors = [];
		$this->userValue = $userValue;

		$datecomponents = [];
		$calendarmodel = $era = $hours = $minutes = $seconds = $microseconds = $timeoffset = $timezone = false;

 		$status = $this->parseDateString(
 			$userValue,
 			$datecomponents,
 			$calendarmodel,
 			$era,
 			$hours,
 			$minutes,
 			$seconds,
 			$microseconds,
 			$timeoffset,
 			$timezone
 		);

		 // Default to JD input if a single number was given as the date
		if ( ( $calendarmodel === false ) && ( $era === false ) && ( count( $datecomponents ) == 1 || count( $datecomponents ) == 2 ) && ( intval( end( $datecomponents ) ) >= 100000 ) ) {
			$calendarmodel = 'JD';
		}

		$components = new Components(
			[
				'value' => $userValue,
				'datecomponents' => $datecomponents,
				'calendarmodel' => $calendarmodel,
				'era' => $era,
				'hours' => $hours,
				'minutes' => $minutes,
				'seconds' => $seconds,
				'microseconds' => $microseconds,
				'timeoffset' => $timeoffset,
				'timezone' => $timezone
			]
		);

		return $status ? $components : false;
	}

	/**
	 * Parse the given string to check if it a date/time value.
	 * The function sets the provided call-by-ref values to the respective
	 * values. If errors are encountered, they are added to the objects
	 * error list and false is returned. Otherwise, true is returned.
	 *
	 * @todo This method in principle allows date parsing to be internationalized
	 * further.
	 *
	 * @param $string string input time representation, e.g. "12 May 2007 13:45:23-3:30"
	 * @param $datecomponents array of strings that might belong to the specification of a date
	 * @param $calendarmodesl string if model was set in input, otherwise false
	 * @param $era string '+' or '-' if provided, otherwise false
	 * @param $hours integer set to a value between 0 and 24
	 * @param $minutes integer set to a value between 0 and 59
	 * @param $seconds integer set to a value between 0 and 59, or false if not given
	 * @param $timeoffset double set to a value for time offset (e.g. 3.5), or false if not given
	 *
	 * @return boolean stating if the parsing succeeded
	 */
	private function parseDateString( $string, &$datecomponents, &$calendarmodel, &$era, &$hours, &$minutes, &$seconds, &$microseconds, &$timeoffset, &$timezone ) {

		$calendarmodel = $timezoneoffset = $era = $ampm = false;
		$hours = $minutes = $seconds = $microseconds = $timeoffset = $timezone = false;

		// Fetch possible "America/Argentina/Mendoza"
		$timzoneIdentifier = substr( $string, strrpos( $string, ' ' ) + 1 );

		if ( Timezone::isValid( $timzoneIdentifier ) ) {
			$string = str_replace( $timzoneIdentifier, '', $string );
			$timezoneoffset = Timezone::getOffsetByAbbreviation( $timzoneIdentifier ) / 3600;
			$timezone = Timezone::getIdByAbbreviation( $timzoneIdentifier );
		}

		// Preprocessing for supporting different date separation characters;
		// * this does not allow localized time notations such as "10.34 pm"
		// * this creates problems with keywords that contain "." such as "p.m."
		// * yet "." is an essential date separation character in languages such as German
		$parsevalue = str_replace( [ '/', '.', '&nbsp;', ',', '年', '月', '日', '時', '分' ], [ '-', ' ', ' ', ' ', ' ', ' ', ' ', ':', ' ' ], $string );

		$matches = preg_split( "/([T]?[0-2]?[0-9]:[\:0-9]+[+\-]?[0-2]?[0-9\:]+|[\p{L}]+|[0-9]+|[ ])/u", $parsevalue, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		$datecomponents = [];
		$unclearparts = [];

		 // Used for looking back; numbers are days/months/years by default but
		 // may be re-interpreted if certain further symbols are found
		$matchisnumber = false;

		// Used for ensuring that date parts are in one block
		$matchisdate = false;

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
			           ( $prevmatchwasdate || count( $datecomponents ) == 0 ) ) {
				$datecomponents[] = $match;
				$matchisnumber = true;
				$matchisdate = true;
			} elseif ( $era === false && in_array( $match, [ 'AD', 'CE' ] ) ) {
				$era = '+';
			} elseif ( $era === false && in_array( $match, [ 'BC', 'BCE' ] ) ) {
				$era = '-';
			} elseif ( $calendarmodel === false && in_array( $match, [ 'Gr', 'GR' , 'He', 'Jl', 'JL', 'MJD', 'JD', 'OS' ] ) ) {
				$calendarmodel = $match;
			} elseif ( $ampm === false && ( strtolower( $match ) === 'am' || strtolower( $match ) === 'pm' ) ) {
				$ampm = strtolower( $match );
			} elseif ( $hours === false && self::parseTimeString( $match, $hours, $minutes, $seconds, $timeoffset ) ) {
				// nothing to do
			} elseif ( $hours !== false && $timezoneoffset === false && Timezone::isValid( $match ) ) {
				// only accept timezone if time has already been set
				$timezoneoffset = Timezone::getOffsetByAbbreviation( $match ) / 3600;
				$timezone = Timezone::getIdByAbbreviation( $match );
			} elseif ( $prevmatchwasnumber && $hours === false && $timezoneoffset === false &&
					Timezone::isMilitary( $match ) &&
					self::parseMilTimeString( end( $datecomponents ), $hours, $minutes, $seconds ) ) {
					// military timezone notation is found after a number -> re-interpret the number as military time
					array_pop( $datecomponents );
					$timezoneoffset = Timezone::getOffsetByAbbreviation( $match ) / 3600;
					$timezone = Timezone::getIdByAbbreviation( $match );
			} elseif ( ( $prevmatchwasdate || count( $datecomponents ) == 0 ) &&
				   $this->parseMonthString( $match, $monthname ) ) {
				$datecomponents[] = $monthname;
				$matchisdate = true;
			} elseif ( $prevmatchwasnumber && $prevmatchwasdate && in_array( $match, [ 'st', 'nd', 'rd', 'th' ] ) ) {
				$datecomponents[] = 'd' . strval( array_pop( $datecomponents ) ); // must be a day; add standard marker
				$matchisdate = true;
			} elseif ( is_string( $match ) ) {
				$microseconds = $match;
			} else {
				$unclearparts[] = $match;
			}
		}

		// $this->debug( $datecomponents, $calendarmodel, $era, $hours, $minutes, $seconds, $microseconds, $timeoffset, $timezone );

		// Abort if we found unclear or over-specific information:
		if ( count( $unclearparts ) != 0 ) {
			$this->errors[] = [ 'smw-datavalue-time-invalid-values', $this->userValue, implode( ', ', $unclearparts ) ];
			return false;
		}

		if ( ( $timezoneoffset !== false && $timeoffset !== false ) ) {
			$this->errors[] = [ 'smw-datavalue-time-invalid-offset-zone-usage', $this->userValue ];
			return false;
		}

		if ( ( $timezoneoffset !== false && $timeoffset !== false ) ) {
			$this->errors[] = [ 'smw-datavalue-time-invalid-offset-zone-usage', $this->userValue ];
			return false;
		}

		$timeoffset = $timeoffset + $timezoneoffset;

		// Check if the a.m. and p.m. information is meaningful
		// Note: the == 0 check subsumes $hours===false
		if ( $ampm !== false && ( $hours > 12 || $hours == 0 ) ) {
			$this->errors[] = [ 'smw-datavalue-time-invalid-ampm', $this->userValue, $hours ];
			return false;
		} elseif ( $ampm == 'am' && $hours == 12 ) {
			$hours = 0;
		} elseif ( $ampm == 'pm' && $hours < 12 ) {
			$hours += 12;
		}

		return true;
	}

	/**
	 * Parse the given string to check if it encodes an international time.
	 * If successful, the function sets the provided call-by-ref values to
	 * the respective numbers and returns true. Otherwise, it returns
	 * false and does not set any values.
	 *
	 * @param $string string input time representation, e.g. "13:45:23-3:30"
	 * @param $hours integer between 0 and 24
	 * @param $minutes integer between 0 and 59
	 * @param $seconds integer between 0 and 59, or false if not given
	 * @param $timeoffset double for time offset (e.g. 3.5), or false if not given
	 *
	 * @return boolean stating if the parsing succeeded
	 */
	private static function parseTimeString( $string, &$hours, &$minutes, &$seconds, &$timeoffset ) {

		if ( !preg_match( "/^[T]?([0-2]?[0-9]):([0-5][0-9])(:[0-5][0-9])?(([+\-][0-2]?[0-9])(:(30|00))?)?$/u", $string, $match ) ) {
			return false;
		} else {
			$nhours = intval( $match[1] );
			$nminutes = $match[2] ? intval( $match[2] ) : false;

			if ( ( count( $match ) > 3 ) && ( $match[3] !== '' ) ) {
				$nseconds = intval( substr( $match[3], 1 ) );
			} else {
				$nseconds = false;
			}

			if ( ( $nhours < 25 ) && ( ( $nhours < 24 ) || ( $nminutes + $nseconds == 0 ) ) ) {
				$hours = $nhours;
				$minutes = $nminutes;
				$seconds = $nseconds;
				if ( ( count( $match ) > 5 ) && ( $match[5] !== '' ) ) {
					$timeoffset = intval( $match[5] );
					if ( ( count( $match ) > 7 ) && ( $match[7] == '30' ) ) {
						$timeoffset += 0.5;
					}
				} else {
					$timeoffset = false;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Parse the given string to check if it encodes a "military time".
	 * If successful, the function sets the provided call-by-ref values to
	 * the respective numbers and returns true. Otherwise, it returns
	 * false and does not set any values.
	 *
	 * @param $string string input time representation, e.g. "134523"
	 * @param $hours integer between 0 and 24
	 * @param $minutes integer between 0 and 59
	 * @param $seconds integer between 0 and 59, or false if not given
	 *
	 * @return boolean stating if the parsing succeeded
	 */
	private static function parseMilTimeString( $string, &$hours, &$minutes, &$seconds ) {

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
			}
		}

		return false;
	}

	/**
	 * Parse the given string to check if it refers to the string name ot
	 * abbreviation of a month name. If yes, it is replaced by a normalized
	 * month name (placed in the call-by-ref parameter) and true is
	 * returned. Otherwise, false is returned and $monthname is not changed.
	 *
	 * @param $string string month name or abbreviation to parse
	 * @param $monthname string with standard 3-letter English month abbreviation
	 *
	 * @return boolean stating whether a month was found
	 */
	private function parseMonthString( $string, &$monthname ) {

		// takes precedence over English month names!
		$monthnum = Localizer::getInstance()->getLang( $this->languageCode )->findMonthNumberByLabel( $string );

		if ( $monthnum !== false ) {
			$monthnum -= 1;
		} else {
			$monthnum = array_search( $string, Components::$months ); // check English names
		}

		if ( $monthnum !== false ) {
			$monthname = Components::$monthsShort[$monthnum];
			return true;
		} elseif ( array_search( $string, Components::$monthsShort ) !== false ) {
			$monthname = $string;
			return true;
		}

		return false;
	}

	private function debug( $datecomponents, $calendarmodel, $era, $hours, $minutes, $seconds, $microseconds, $timeoffset, $timezone ) {
		//print "\n\n Results \n\n";
		//debug_zval_dump( $datecomponents );
		//print "\ncalendarmodel: $calendarmodel   \ntimezoneoffset: $timezoneoffset  \nera: $era  \nampm: $ampm  \nh: $hours  \nm: $minutes  \ns:$seconds  \ntimeoffset: $timeoffset  \n";
		//debug_zval_dump( $unclearparts );
	}

}
