<?php
/**
 * @file
 * @ingroup SMWDataItems
 */

/**
 * This class implements time data items.
 * Such data items represent a unique point in time, given in either Julian or
 * Gregorian notation (possibly proleptic), and a precision setting that states
 * which of the components year, month, day, time were specified expicitly.
 * Even when not specified, the data item always assumes default values for the
 * missing parts, so the item really captures one point in time, no intervals.
 * Times are always assumed to be in UTC.
 * 
 * "Y0K issue": Neither the Gregorian nor the Julian calendar assume a year 0,
 * i.e. the year 1 BC(E) was followed by 1 AD/CE. See
 * http://en.wikipedia.org/wiki/Year_zero
 * This implementation adheres to this convention and disallows year 0. The
 * stored year numbers use positive numbers for CE and negative numbers for
 * BCE. This is not just relevant for the question of how many years have
 * (exactly) passed since a given date, but also for the location of leap
 * years.
 *
 * @since 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataItems
 */
class SMWDITime extends SMWDataItem {

	const CM_GREGORIAN = 1;
	const CM_JULIAN = 2;

	const PREC_Y    = 0;
	const PREC_YM   = 1;
	const PREC_YMD  = 2;
	const PREC_YMDT = 3;

	/**
	 * Maximal number of days in a given month.
	 * @var array
	 */
	protected static $m_daysofmonths = array ( 1 => 31, 2 => 29, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31 );

	/**
	 * Precision SMWDITime::PREC_Y, SMWDITime::PREC_YM,
	 * SMWDITime::PREC_YMD, or SMWDITime::PREC_YMDT.
	 * @var integer
	 */
	protected $m_precision;
	/**
	 * Calendar model: SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN.
	 * @var integer
	 */
	protected $m_model;
	/**
	 * Number of year, possibly negative.
	 * @var integer
	 */
	protected $m_year;
	/**
	 * Number of month.
	 * @var integer
	 */
	protected $m_month;
	/**
	 * Number of day.
	 * @var integer
	 */
	protected $m_day;
	/**
	 * Hours of the day.
	 * @var integer
	 */
	protected $m_hours;
	/**
	 * Minutes of the hour.
	 * @var integer
	 */
	protected $m_minutes;
	/**
	 * Seconds of the minute.
	 * @var integer
	 */
	protected $m_seconds;

	/**
	 * Create a time data item. All time components other than the year can
	 * be false to indicate that they are not specified. This will affect
	 * the internal precision setting. The missing values are initialised
	 * to minimal values (0 or 1) for internal calculations.
	 * 
	 * @param $calendarmodel integer one of SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @param $year integer number of the year (possibly negative)
	 * @param $month mixed integer number or false
	 * @param $day mixed integer number or false
	 * @param $hour mixed integer number or false
	 * @param $minute mixed integer number or false
	 * @param $second mixed integer number or false
	 *
	 * @todo Implement more validation here.
	 */
	public function __construct( $calendarmodel, $year, $month = false, $day = false,
	                             $hour = false, $minute = false, $second = false ) {
		if ( ( $calendarmodel != self::CM_GREGORIAN ) && ( $calendarmodel != self::CM_JULIAN ) ) {
			throw new SMWDataItemException( "Unsupported calendar model constant \"$calendarmodel\"." );
		}
		if ( $year == 0 ) {
			throw new SMWDataItemException( "There is no year 0 in Gregorian and Julian calendars." );
		}
		$this->m_model   = $calendarmodel;
		$this->m_year    = intval( $year );
		$this->m_month   = $month != false ? intval( $month ) : 1;
		$this->m_day     = $day != false ? intval( $day ) : 1;
		$this->m_hours   = $hour !== false ? intval( $hour ) : 0;
		$this->m_minutes = $minute !== false ? intval( $minute ) : 0;
		$this->m_seconds = $second !== false ? intval( $second ) : 0;
		if ( ( $this->m_hours < 0 ) || ( $this->m_hours > 23 ) ||
		     ( $this->m_minutes < 0 ) || ( $this->m_minutes > 59 ) ||
		     ( $this->m_seconds < 0 ) || ( $this->m_seconds > 59 ) ||
		     ( $this->m_month < 1 ) || ( $this->m_month > 12 ) ) {
			throw new SMWDataItemException( "Part of the date is out of bounds." );
		}
		if ( $this->m_day > self::getDayNumberForMonth( $this->m_month, $this->m_year, $this->m_model ) ) {
			throw new SMWDataItemException( "Month {$this->m_month} in year {$this->m_year} did not have {$this->m_day} days in this calendar model." );
		}
		if ( $month === false ) {
			$this->m_precision = self::PREC_Y;
		} elseif ( $day === false ) {
			$this->m_precision = self::PREC_YM;
		} elseif ( $hour === false ) {
			$this->m_precision = self::PREC_YMD;
		} else {
			$this->m_precision = self::PREC_YMDT;
		}
	}

	public function getDIType() {
		return SMWDataItem::TYPE_TIME;
	}

	public function getCalendarModel() {
		return $this->m_model;
	}

	public function getPrecision() {
		return $this->m_precision;
	}

	public function getYear() {
		return $this->m_year;
	}

	public function getMonth() {
		return $this->m_month;
	}

	public function getDay() {
		return $this->m_day;
	}

	public function getHour() {
		return $this->m_hours;
	}

	public function getMinute() {
		return $this->m_minutes;
	}

	public function getSecond() {
		return $this->m_seconds;
	}
	
	/**
	 * Returns a MW timestamp representatation of the value.
	 * 
	 * @since 1.6.2
	 * 
	 * @param $outputtype
	 */
	public function getMwTimestamp( $outputtype = TS_UNIX ) {
		return wfTimestamp(
			$outputtype,
			implode( '', array(
				str_pad( $this->m_year, 4, '0', STR_PAD_LEFT ),
				str_pad( $this->m_month, 2, '0', STR_PAD_LEFT ),
				str_pad( $this->m_day, 2, '0', STR_PAD_LEFT ),
				str_pad( $this->m_hours, 2, '0', STR_PAD_LEFT ),
				str_pad( $this->m_minutes, 2, '0', STR_PAD_LEFT ),
				str_pad( $this->m_seconds, 2, '0', STR_PAD_LEFT ),
			) )
		);
	}

	/**
	 * Get the data in the specified calendar model. This might require
	 * conversion.
	 * @note Conversion can be unreliable for very large absolute year
	 * numbers when the internal calculations hit floating point accuracy.
	 * Callers might want to avoid this (calendar models make little sense
	 * in such cases anyway).
	 * @param $calendarmodel integer one of SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @return SMWDITime
	 */
	public function getForCalendarModel( $calendarmodel ) {
		if ( $calendarmodel == $this->m_model ) {
			return $this;
		} else {
			return self::newFromJD( $this->getJD(), $calendarmodel, $this->m_precision );
		}
	}

	/**
	 * Return a number that helps comparing time data items. For
	 * dates in the Julian Day era (roughly from 4713 BCE onwards), we use
	 * the Julian Day number. For earlier dates, the (negative) year number
	 * with a fraction for the date is used (times are ignored). This
	 * avoids calculation errors that would occur for very ancient dates
	 * if the JD number was used there.
	 * @return double sortkey
	 */
	public function getSortKey() {
		$jd = ( $this->m_year >= -4713 ) ? $jd = $this->getJD() : -1;
		if ( $jd > 0 ) {
			return $jd;
		} else {
			return $this->m_year - 1 + ( $this->m_month - 1 ) / 12 + ( $this->m_day - 1 ) / 12 / 31;
		}
	}

	public function getJD() {
		return self::date2JD( $this->m_year, $this->m_month, $this->m_day, $this->m_model ) +
		       self::time2JDoffset( $this->m_hours, $this->m_minutes, $this->m_seconds );
	}

	public function getSerialization() {
		$result = strval( $this->m_model ) . '/' . strval( $this->m_year );
		if ( $this->m_precision >= self::PREC_YM ) {
			$result .= '/' . strval( $this->m_month );
		}
		if ( $this->m_precision >= self::PREC_YMD ) {
			$result .= '/' . strval( $this->m_day );
		}
		if ( $this->m_precision >= self::PREC_YMDT ) {
			$result .= '/' . strval( $this->m_hours ) . '/' . strval( $this->m_minutes ) . '/' . strval( $this->m_seconds );
		}
		return $result;
	}

	/**
	 * Create a data item from the provided serialization string and type
	 * ID.
	 * @return SMWDITime
	 */
	public static function doUnserialize( $serialization ) {
		$parts = explode( '/', $serialization, 7 );
		$values = array();
		
		for ( $i = 0; $i < 7; $i += 1 ) {
			if ( $i < count( $parts ) ) {
				if ( is_numeric( $parts[$i] ) ) {
					$values[$i] = intval( $parts[$i] );
				} else {
					throw new SMWDataItemException( "Unserialization failed: the string \"$serialization\" is no valid datetime specification." );
				}
			} else {
				$values[$i] = false;
			}
		}
		
		if ( count( $parts ) <= 1 ) {
			throw new SMWDataItemException( "Unserialization failed: the string \"$serialization\" is no valid URI." );
		}
		
		return new self( $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6] );
	}

	/**
	 * Create a new time data item from the specified Julian Day number,
	 * calendar model, presicion, and type ID.
	 * @param $jdvalue double Julian Day number
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @param $precision integer one of SMWDITime::PREC_Y, SMWDITime::PREC_YM, SMWDITime::PREC_YMD, SMWDITime::PREC_YMDT
	 * @return SMWDITime object
	 */
	public static function newFromJD( $jdvalue, $calendarmodel, $precision ) {
		list( $year, $month, $day ) = self::JD2Date( $jdvalue, $calendarmodel );
		if ( $precision <= self::PREC_YM ) {
			$day = false;
			if ( $precision == self::PREC_Y ) {
				$month = false;
			}
		}
		if ( $precision == self::PREC_YMDT ) {
			list( $hour, $minute, $second ) = self::JD2Time( $jdvalue );
		} else {
			$hour = $minute = $second = false;
		}
		return new SMWDITime( $calendarmodel, $year, $month, $day, $hour, $minute, $second );
	}

	/**
	 * Compute the Julian Day number from a given date in the specified
	 * calendar model. This calculation assumes that neither calendar
	 * has a year 0.
	 * @param $year integer representing the year
	 * @param $month integer representing the month
	 * @param $day integer representing the day
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @return float Julian Day number
	 */
	static public function date2JD( $year, $month, $day, $calendarmodel ) {
		$astroyear = ( $year < 1 ) ? ( $year + 1 ) : $year;
		if ( $calendarmodel == self::CM_GREGORIAN ) {
			$a = intval( ( 14 - $month ) / 12 );
			$y = $astroyear + 4800 - $a;
			$m = $month + 12 * $a - 3;
			return $day + floor( ( 153 * $m + 2 ) / 5 ) + 365 * $y + floor( $y / 4 ) - floor( $y / 100 ) + floor( $y / 400 ) - 32045.5;
		} else {
			$y2 = ( $month <= 2 ) ? ( $astroyear - 1 ) : $astroyear;
			$m2 = ( $month <= 2 ) ? ( $month + 12 ) : $month;
			return floor( ( 365.25 * ( $y2 + 4716 ) ) ) + floor( ( 30.6001 * ( $m2 + 1 ) ) ) + $day - 1524.5;
		}
	}

	/**
	 * Compute the offset for the Julian Day number from a given time.
	 * This computation is the same for all calendar models.
	 * @param $hours integer representing the hour
	 * @param $minutes integer representing the minutes
	 * @param $seconds integer representing the seconds
	 * @return float offset for a Julian Day number to get this time
	 */
	static public function time2JDoffset( $hours, $minutes, $seconds ) {
		return ( $hours / 24 ) + ( $minutes / ( 60 * 24 ) ) + ( $seconds / ( 3600 * 24 ) );
	}

	/**
	 * Convert a Julian Day number to a date in the given calendar model.
	 * This calculation assumes that neither calendar has a year 0.
	 * @note The algorithm may fail for some cases, in particular since the
	 * conversion to Gregorian needs positive JD. If this happens, wrong
	 * values will be returned. Avoid date conversions before 10000 BCE.
	 * @param $jdvalue float number of Julian Days
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @return array( yearnumber, monthnumber, daynumber )
	 */
	static public function JD2Date( $jdvalue, $calendarmodel ) {
		if ( $calendarmodel == self::CM_GREGORIAN ) {
			$jdvalue += 2921940; // add the days of 8000 years (this algorithm only works for positive JD)
			$j = floor( $jdvalue + 0.5 ) + 32044;
			$g = floor( $j / 146097 );
			$dg = $j % 146097;
			$c = floor( ( ( floor( $dg / 36524 ) + 1 ) * 3 ) / 4 );
			$dc = $dg - $c * 36524;
			$b = floor( $dc / 1461 );
			$db = $dc % 1461;
			$a = floor( ( ( floor( $db / 365 ) + 1 ) * 3 ) / 4 );
			$da = $db - ( $a * 365 );
			$y = $g * 400 + $c * 100 + $b * 4 + $a;
			$m = floor( ( $da * 5 + 308 ) / 153 ) - 2;
			$d = $da - floor( ( ( $m + 4 ) * 153 ) / 5 ) + 122;

			$year  = $y - 4800 + floor( ( $m + 2 ) / 12 ) - 8000;
			$month = ( ( $m + 2 ) % 12 + 1 );
			$day   = $d + 1;
		} else {
			$b = floor( $jdvalue + 0.5 ) + 1524;
			$c = floor( ( $b - 122.1 ) / 365.25 );
			$d = floor( 365.25 * $c );
			$e = floor( ( $b - $d ) / 30.6001 );

			$month = floor( ( $e < 14 ) ? ( $e - 1 ) : ( $e - 13 ) );
			$year = floor( ( $month > 2 ) ? ( $c - 4716 ) : ( $c - 4715 ) );
			$day   = ( $b - $d - floor( 30.6001 * $e ) );
		}
		$year  = ( $year < 1 ) ? ( $year - 1 ) : $year; // correct "year 0" to -1 (= 1 BC(E))
		return array( $year, $month, $day );
	}

	/**
	 * Extract the time from a Julian Day number and return it as a string.
	 * This conversion is the same for all calendar models.
	 * @param $jdvalue float number of Julian Days
	 * @return array( hours, minutes, seconds )
	 */
	static public function JD2Time( $jdvalue ) {
		$wjd = $jdvalue + 0.5;
		$fraction = $wjd - floor( $wjd );
		$time = round( $fraction * 3600 * 24 );
		$hours = floor( $time / 3600 );
		$time = $time - $hours * 3600;
		$minutes = floor( $time / 60 );
		$seconds = floor( $time - $minutes * 60 );
		return array( $hours, $minutes, $seconds );
	}

	/**
	 * Find out whether the given year number is a leap year.
	 * This calculation assumes that neither calendar has a year 0.
	 * @param $year integer year number
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @return boolean
	 */
	static public function isLeapYear( $year, $calendarmodel ) {
		$astroyear = ( $year < 1 ) ? ( $year + 1 ) : $year;
		if ( $calendarmodel == self::CM_JULIAN ) {
			return ( $astroyear % 4 ) == 0;
		} else {
			return ( ( $astroyear % 400 ) == 0 ) ||
			       ( ( ( $astroyear % 4 ) == 0 ) && ( ( $astroyear % 100 ) != 0 ) );
		}
	}

	/**
	 * Find out how many days the given month had in the given year
	 * based on the specified calendar model.
	 * This calculation assumes that neither calendar has a year 0.
	 * @param $month integer month number
	 * @param $year integer year number
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 * @return boolean
	 */
	static public function getDayNumberForMonth( $month, $year, $calendarmodel ) {
		if ( $month !== 2 ) {
			return self::$m_daysofmonths[$month];
		} elseif ( self::isLeapYear( $year, $calendarmodel ) ) {
			return 29;
		} else {
			return 28;
		}
	}

}
