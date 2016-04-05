<?php

namespace SMW;

use RuntimeException;
use SMWDITime as DITime;

/**
 * Julian dates (abbreviated JD) are a continuous count of days and fractions
 * since noon Universal Time on January 1, 4713 BCE (on the Julian calendar).
 *
 * It is assumed that the changeover from the Julian calendar to the Gregorian
 * calendar occurred in October of 1582.
 *
 * For dates on or before 4 October 1582, the Julian calendar is used; for dates
 * on or after 15 October 1582, the Gregorian calendar is used.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author Markus Krötzsch
 */
class JulianDay {

	/**
	 * Moment of switchover to Gregorian calendar.
	 */
	const J1582 = 2299160.5;

	/**
	 * Offset of Julian Days for Modified JD inputs.
	 */
	const MJD = 2400000.5;

	/**
	 * Create a new time dataItem from a specified Julian Day number,
	 * calendar model, presicion.
	 *
	 * @param double $jdValue
	 * @param integer|null $calendarmodel
	 * @param integer|null $precision
	 *
	 * @return DITime object
	 */
	public static function newDiFromJD( $jdValue, $calendarModel = null, $precision = null ) {

		if ( $calendarModel === null ) { // 1582/10/15
			$calendarModel = $jdValue < self::J1582 ? DITime::CM_JULIAN : DITime::CM_GREGORIAN;
		}

		if ( $precision === null ) {
			$precision = strpos( strval( $jdValue ), '.5' ) !== false ? DITime::PREC_YMD : DITime::PREC_YMDT;
		}

		list( $year, $month, $day ) = self::JD2Date( $jdValue, $calendarModel );

		if ( $precision <= DITime::PREC_YM ) {
			$day = false;
			if ( $precision === DITime::PREC_Y ) {
				$month = false;
			}
		}

		if ( $precision === DITime::PREC_YMDT ) {
			list( $hour, $minute, $second ) = self::JD2Time( $jdValue );
		} else {
			$hour = $minute = $second = false;
		}

		return new DITime( $calendarModel, $year, $month, $day, $hour, $minute, $second );
	}

	/**
	 * @since 2.4
	 *
	 * @param DITime $dataItem
	 *
	 * @return float
	 */
	public static function get( DITime $dataItem ) {

		$jdValue = self::date2JD(
			$dataItem->getYear(),
			$dataItem->getMonth(),
			$dataItem->getDay(),
			$dataItem->getCalendarModel()
		) + self::time2JDoffset(
			$dataItem->getHour(),
			$dataItem->getMinute(),
			$dataItem->getSecond()
		);

		// Keep microseconds to a certain degree distinguishable
		return floatval( number_format( $jdValue, 7, '.', '' ) );
	}

	/**
	 * The MJD has a starting point of midnight on November 17, 1858 and is
	 * computed by MJD = JD - 2400000.5
	 *
	 * @since 2.4
	 *
	 * @param DITime $dataItem
	 *
	 * @return float
	 */
	public static function getModifiedJulianDate( DITime $dataItem ) {
		return self::get( $dataItem ) - self::MJD;
	}

	/**
	 * Compute the Julian Day number from a given date in the specified
	 * calendar model. This calculation assumes that neither calendar
	 * has a year 0.
	 *
	 * @param $year integer representing the year
	 * @param $month integer representing the month
	 * @param $day integer representing the day
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 *
	 * @return float Julian Day number
	 * @throws RuntimeException
	 */
	protected static function date2JD( $year, $month, $day, $calendarmodel ) {
		$astroyear = ( $year < 1 ) ? ( $year + 1 ) : $year;

		if ( $calendarmodel === DITime::CM_GREGORIAN ) {
			$a = intval( ( 14 - $month ) / 12 );
			$y = $astroyear + 4800 - $a;
			$m = $month + 12 * $a - 3;
			return $day + floor( ( 153 * $m + 2 ) / 5 ) + 365 * $y + floor( $y / 4 ) - floor( $y / 100 ) + floor( $y / 400 ) - 32045.5;
		} elseif ( $calendarmodel === DITime::CM_JULIAN ) {
			$y2 = ( $month <= 2 ) ? ( $astroyear - 1 ) : $astroyear;
			$m2 = ( $month <= 2 ) ? ( $month + 12 ) : $month;
			return floor( ( 365.25 * ( $y2 + 4716 ) ) ) + floor( ( 30.6001 * ( $m2 + 1 ) ) ) + $day - 1524.5;
		}

		throw new RuntimeException( "Unsupported calendar model ($calendarmodel)" );
	}

	/**
	 * Compute the offset for the Julian Day number from a given time.
	 * This computation is the same for all calendar models.
	 *
	 * @param $hours integer representing the hour
	 * @param $minutes integer representing the minutes
	 * @param $seconds integer representing the seconds
	 *
	 * @return float offset for a Julian Day number to get this time
	 */
	protected static function time2JDoffset( $hours, $minutes, $seconds ) {
		return ( $hours / 24 ) + ( $minutes / ( 60 * 24 ) ) + ( $seconds / ( 3600 * 24 ) );
	}

	/**
	 * Convert a Julian Day number to a date in the given calendar model.
	 * This calculation assumes that neither calendar has a year 0.
	 * @note The algorithm may fail for some cases, in particular since the
	 * conversion to Gregorian needs positive JD. If this happens, wrong
	 * values will be returned. Avoid date conversions before 10000 BCE.
	 *
	 * @param $jdvalue float number of Julian Days
	 * @param $calendarmodel integer either SMWDITime::CM_GREGORIAN or SMWDITime::CM_JULIAN
	 *
	 * @return array( yearnumber, monthnumber, daynumber )
	 * @throws RuntimeException
	 */
	protected static function JD2Date( $jdvalue, $calendarmodel ) {

		if ( $calendarmodel === DITime::CM_GREGORIAN ) {
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
		} elseif ( $calendarmodel === DITime::CM_JULIAN ) {
			$b = floor( $jdvalue + 0.5 ) + 1524;
			$c = floor( ( $b - 122.1 ) / 365.25 );
			$d = floor( 365.25 * $c );
			$e = floor( ( $b - $d ) / 30.6001 );

			$month = floor( ( $e < 14 ) ? ( $e - 1 ) : ( $e - 13 ) );
			$year = floor( ( $month > 2 ) ? ( $c - 4716 ) : ( $c - 4715 ) );
			$day   = ( $b - $d - floor( 30.6001 * $e ) );
		} else {
			throw new RuntimeException( "Unsupported calendar model ($calendarmodel)" );
		}

		$year  = ( $year < 1 ) ? ( $year - 1 ) : $year; // correct "year 0" to -1 (= 1 BC(E))

		return array( $year, $month, $day );
	}

	/**
	 * Extract the time from a Julian Day number and return it as a string.
	 * This conversion is the same for all calendar models.
	 *
	 * @param $jdvalue float number of Julian Days
	 * @return array( hours, minutes, seconds )
	 */
	protected static function JD2Time( $jdvalue ) {
		$wjd = $jdvalue + 0.5;
		$fraction = $wjd - floor( $wjd );
		$time = round( $fraction * 3600 * 24 );
		$hours = floor( $time / 3600 );
		$time = $time - $hours * 3600;
		$minutes = floor( $time / 60 );
		$seconds = floor( $time - $minutes * 60 );
		return array( $hours, $minutes, $seconds );
	}

}
