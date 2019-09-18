<?php

use SMW\DataValues\Time\CalendarModel;
use SMW\DataValues\Time\JulianDay;
use SMW\Exception\DataItemException;

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
class SMWDITime extends SMWDataItem implements CalendarModel {

	const PREC_Y    = SMW_PREC_Y;
	const PREC_YM   = SMW_PREC_YM;
	const PREC_YMD  = SMW_PREC_YMD;
	const PREC_YMDT = SMW_PREC_YMDT;

	/**
	 * The year before which we do not accept anything but year numbers and
	 * largely discourage calendar models.
	 */
	const PREHISTORY = -10000;

	/**
	 * Maximal number of days in a given month.
	 * @var array
	 */
	protected static $m_daysofmonths = [ 1 => 31, 2 => 29, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31 ];

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
	 * @var integer
	 */
	protected $timezone;

	/**
	 * @var integer|null
	 */
	protected $era = null;

	/**
	 * @var integer
	 */
	protected $julianDay = null;

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
	 * @param integer|false $timezone
	 *
	 * @todo Implement more validation here.
	 */
	public function __construct( $calendarmodel, $year, $month = false, $day = false,
	                             $hour = false, $minute = false, $second = false, $timezone = false ) {

		if ( ( $calendarmodel != self::CM_GREGORIAN ) && ( $calendarmodel != self::CM_JULIAN ) ) {
			throw new DataItemException( "Unsupported calendar model constant \"$calendarmodel\"." );
		}

		if ( $year == 0 ) {
			throw new DataItemException( "There is no year 0 in Gregorian and Julian calendars." );
		}

		$this->m_model   = $calendarmodel;
		$this->m_year    = intval( $year );
		$this->m_month   = $month != false ? intval( $month ) : 1;
		$this->m_day     = $day != false ? intval( $day ) : 1;
		$this->m_hours   = $hour !== false ? intval( $hour ) : 0;
		$this->m_minutes = $minute !== false ? intval( $minute ) : 0;
		$this->m_seconds = $second !== false ? floatval( $second ) : 0;

		$this->timezone = $timezone !== false ? $timezone : 0;
		$year = strval( $year );
		$this->era      = $year[0] === '+' ? 1 : ( $year[0] === '-' ? -1 : 0 );

		if ( $this->isOutOfBoundsBySome() ) {
			throw new DataItemException( "Part of the date is out of bounds." );
		}

		if ( $this->isOutOfBoundsByDayNumberOfMonth() ) {
			throw new DataItemException( "Month {$this->m_month} in year {$this->m_year} did not have {$this->m_day} days in this calendar model." );
		}

		$this->setPrecisionLevelBy( $month, $day, $hour );
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getDIType() {
		return SMWDataItem::TYPE_TIME;
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getCalendarModel() {
		return $this->m_model;
	}

	/**
	 * @since 2.5
	 *
	 * @return integer
	 */
	public function getTimezone() {
		return $this->timezone;
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getPrecision() {
		return $this->m_precision;
	}

	/**
	 * Indicates whether a user explicitly used an era marker even for a positive
	 * year.
	 *
	 * - [-1] indicates BC(E)
	 * - [0]/null indicates no era marker
	 * - [1] indicates AD/CE was used
	 *
	 * @since 2.4
	 *
	 * @return integer
	 */
	public function getEra() {
		return $this->era;
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getYear() {
		return $this->m_year;
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getMonth() {
		return $this->m_month;
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getDay() {
		return $this->m_day;
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getHour() {
		return $this->m_hours;
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getMinute() {
		return $this->m_minutes;
	}

	/**
	 * @since 1.6
	 *
	 * @return integer
	 */
	public function getSecond() {
		return $this->m_seconds;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getCalendarModelLiteral() {

		$literal = [
			self::CM_GREGORIAN => '',
			self::CM_JULIAN    => 'JL'
		];

		return $literal[$this->m_model];
	}

	/**
	 * @since 2.4
	 *
	 * @param DateTime $dateTime
	 *
	 * @return self
	 * @throws DataItemException
	 */
	public static function newFromDateTime( DateTime $dateTime ) {

		$calendarModel = self::CM_JULIAN;

		$year = $dateTime->format( 'Y' );
		$month = $dateTime->format( 'm' );
		$day = $dateTime->format( 'd' );

		if ( ( $year > 1582 ) ||
			( ( $year == 1582 ) && ( $month > 10 ) ) ||
			( ( $year == 1582 ) && ( $month == 10 ) && ( $day > 4 ) ) ) {
			$calendarModel = self::CM_GREGORIAN;
		}

		return self::doUnserialize( $calendarModel . '/' . $dateTime->format( 'Y/m/d/H/i/s.u' ) );
	}

	/**
	 * @since 2.4
	 *
	 * @return DateTime
	 */
	public function asDateTime() {

		$year = str_pad( $this->m_year, 4, '0', STR_PAD_LEFT );

		// Avoid "Failed to parse time string (-900-02-02 00:00:00) at
		// position 7 (-): Double timezone specification"
		if ( $this->m_year < 0 ) {
			$year = '-' . str_pad( $this->m_year * -1, 4, '0', STR_PAD_LEFT );
		}

		// Avoid "Failed to parse time string (1300-11-02 12:03:25.888499949) at
		// at position 11 (1): The timezone could not ..."
		$seconds = number_format( str_pad( $this->m_seconds, 2, '0', STR_PAD_LEFT ), 7, '.', '' );

		$time = $year . '-' .
			str_pad( $this->m_month, 2, '0', STR_PAD_LEFT )     . '-' .
			str_pad( $this->m_day, 2, '0', STR_PAD_LEFT )       . ' ' .
			str_pad( $this->m_hours, 2, '0', STR_PAD_LEFT )     . ':' .
			str_pad( $this->m_minutes, 2, '0', STR_PAD_LEFT )   . ':' .
			$seconds;

		return new DateTime( $time );
	}

	/**
	 * Creates and returns a new instance of SMWDITime from a MW timestamp.
	 *
	 * @since 1.8
	 *
	 * @param string $timestamp must be in format
	 *
	 * @return self|false
	 */
	public static function newFromTimestamp( $timestamp ) {
		$timestamp = wfTimestamp( TS_MW, (string)$timestamp );

		if ( $timestamp === false ) {
			return false;
		}

		return new self(
			self::CM_GREGORIAN,
			substr( $timestamp, 0, 4 ),
			substr( $timestamp, 4, 2 ),
			substr( $timestamp, 6, 2 ),
			substr( $timestamp, 8, 2 ),
			substr( $timestamp, 10, 2 ),
			substr( $timestamp, 12, 2 )
		);
	}

	/**
	 * Returns a MW timestamp representation of the value.
	 *
	 * @since 1.6.2
	 *
	 * @param $outputtype
	 *
	 * @return mixed
	 */
	public function getMwTimestamp( $outputtype = TS_UNIX ) {
		return wfTimestamp(
			$outputtype,
			implode( '', [
				str_pad( $this->m_year, 4, '0', STR_PAD_LEFT ),
				str_pad( $this->m_month, 2, '0', STR_PAD_LEFT ),
				str_pad( $this->m_day, 2, '0', STR_PAD_LEFT ),
				str_pad( $this->m_hours, 2, '0', STR_PAD_LEFT ),
				str_pad( $this->m_minutes, 2, '0', STR_PAD_LEFT ),
				str_pad( $this->m_seconds, 2, '0', STR_PAD_LEFT ),
			] )
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
	 * @return self
	 */
	public function getForCalendarModel( $calendarmodel ) {
		if ( $calendarmodel == $this->m_model ) {
			return $this;
		}

		return self::newFromJD( $this->getJD(), $calendarmodel, $this->m_precision );
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
		}

		return $this->m_year - 1 + ( $this->m_month - 1 ) / 12 + ( $this->m_day - 1 ) / 12 / 31;
	}

	/**
	 * @since 1.6
	 *
	 * @return double
	 */
	public function getJD() {

		if ( $this->julianDay !== null ) {
			return $this->julianDay;
		}

		$this->julianDay = JulianDay::getJD(
			$this->getCalendarModel(),
			$this->getYear(),
			$this->getMonth(),
			$this->getDay(),
			$this->getHour(),
			$this->getMinute(),
			$this->getSecond()
		);

		return $this->julianDay;
	}

	/**
	 * @since 1.6
	 *
	 * @return string
	 */
	public function getSerialization() {
		$result = strval( $this->m_model ) . '/' . ( $this->era > 0 ? '+' : '' ) . strval( $this->m_year );

		if ( $this->m_precision >= self::PREC_YM ) {
			$result .= '/' . strval( $this->m_month );
		}

		if ( $this->m_precision >= self::PREC_YMD ) {
			$result .= '/' . strval( $this->m_day );
		}

		if ( $this->m_precision >= self::PREC_YMDT ) {
			$result .= '/' . strval( $this->m_hours ) . '/' . strval( $this->m_minutes ) . '/' . strval( $this->m_seconds ) . '/' . strval( $this->timezone );
		}

		return $result;
	}

	/**
	 * Create a data item from the provided serialization string.
	 *
	 * @return self
	 */
	public static function doUnserialize( $serialization ) {
		$parts = explode( '/', $serialization, 8 );
		$values = [];

		if ( count( $parts ) <= 1 ) {
			throw new DataItemException( "Unserialization failed: the string \"$serialization\" is no valid URI." );
		}

		for ( $i = 0; $i < 8; $i += 1 ) {

			$values[$i] = false;

			// Can contain something like '1/1970/1/12/11/43/0/Asia/Tokyo'
			if ( $i == 7 && isset( $parts[$i] ) ) {
				$values[$i] = strval( $parts[$i] );
				continue;
			}

			if ( $i < count( $parts ) ) {

				if ( $parts[$i] !== '' && !is_numeric( $parts[$i] ) ) {
					throw new DataItemException( "Unserialization failed: the string \"$serialization\" is no valid datetime specification." );
				}

				// 6 == seconds, we want to keep microseconds
				$values[$i] = $i == 6 ? floatval( $parts[$i] ) : intval( $parts[$i] );

				// Find out whether the input contained an explicit AD/CE era marker
				if ( $i == 1 ) {
					$values[$i] = ( $parts[1][0] === '+' ? '+' : '' ) . $values[$i];
				}
			}
		}

		return new self( $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7] );
	}

	/**
	 * Create a new time dataItem from a specified Julian Day number,
	 * calendar model, presicion.
	 *
	 * @param double $jdValue
	 * @param integer|null $calendarmodel
	 * @param integer|null $precision
	 *
	 * @return self
	 */
	public static function newFromJD( $jdValue, $calendarModel = null, $precision = null, $timezone = false ) {

		$hour = $minute = $second = false;
		$year = $month = $day = false;
		$jdValue = JulianDay::format( $jdValue );

		if ( $precision === null ) {
			$precision = strpos( strval( $jdValue ), '.5' ) !== false ? self::PREC_YMD : self::PREC_YMDT;
		}

		list( $calendarModel, $year, $month, $day ) = JulianDay::JD2Date( $jdValue, $calendarModel );

		if ( $precision <= self::PREC_YM ) {
			$day = false;
			if ( $precision === self::PREC_Y ) {
				$month = false;
			}
		}

		if ( $precision === self::PREC_YMDT ) {
			list( $hour, $minute, $second ) = JulianDay::JD2Time( $jdValue );
		}

		return new self( $calendarModel, $year, $month, $day, $hour, $minute, $second, $timezone );
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

	public function equals( SMWDataItem $di ) {
		if ( $di->getDIType() !== SMWDataItem::TYPE_TIME ) {
			return false;
		}

		return $di->getSortKey() === $this->getSortKey();
	}

	private function isOutOfBoundsBySome() {
		return ( $this->m_hours < 0 ) || ( $this->m_hours > 23 ) ||
		( $this->m_minutes < 0 ) || ( $this->m_minutes > 59 ) ||
		( $this->m_seconds < 0 ) || ( $this->m_seconds > 59 ) ||
		( $this->m_month < 1 ) || ( $this->m_month > 12 );
	}

	private function isOutOfBoundsByDayNumberOfMonth() {
		return $this->m_day > self::getDayNumberForMonth( $this->m_month, $this->m_year, $this->m_model );
	}

	private function setPrecisionLevelBy( $month, $day, $hour ) {
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

}
