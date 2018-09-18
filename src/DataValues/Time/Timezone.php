<?php

namespace SMW\DataValues\Time;

use DateInterval;
use DateTime;
use DateTimeZone;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class Timezone {

	/**
	 * A new TZ is expected to be added at the end of the list without changing
	 * existing ID's (those are used as internal serialization identifier).
	 *
	 * The associated offsets are in hours or fractions of hours.
	 *
	 * 'FOO' => array( ID, OffsetInSeconds, isMilitary )
	 *
	 * @var array
	 */
	private static $shortList = [
		"UTC" => [ 0, 0, false ],
		"Z" => [ 1, 0, true ],
		"A" => [ 2, 3600, true ],
		"ACDT" => [ 3, 37800, false ],
		"ACST" => [ 4, 34200, false ],
		"ADT" => [ 5, -10800, false ],
		"AEDT" => [ 6, 39600, false ],
		"AEST" => [ 7, 36000, false ],
		"AKDT" => [ 8, -28800, false ],
		"AKST" => [ 9, -32400, false ],
		"AST" => [ 10, -14400, false ],
		"AWDT" => [ 11, 32400, false ],
		"AWST" => [ 12, 28800, false ],
		"B" => [ 13, 7200, true ],
		"BST" => [ 14, 3600, false ],
		"C" => [ 15, 10800, true ],
		"CDT" => [ 16, -18000, false ],
		"CEDT" => [ 17, 7200, false ],
		"CEST" => [ 18, 7200, false ],
		"CET" => [ 19, 3600, false ],
		"CST" => [ 20, -21600, false ],
		"CXT" => [ 21, 25200, false ],
		"D" => [ 22, 14400, true ],
		"E" => [ 23, 18000, true ],
		"EDT" => [ 24, -14400, false ],
		"EEDT" => [ 25, 10800, false ],
		"EEST" => [ 26, 10800, false ],
		"EET" => [ 27, 7200, false ],
		"EST" => [ 28, -18000, false ],
		"F" => [ 29, 21600, true ],
		"G" => [ 30, 25200, true ],
		"GMT" => [ 31, 0, false ],
		"H" => [ 32, 28800, true ],
		"HAA" => [ 33, -10800, false ],
		"HAC" => [ 34, -18000, false ],
		"HADT" => [ 35, -32400, false ],
		"HAE" => [ 36, -14400, false ],
		"HAP" => [ 37, -25200, false ],
		"HAR" => [ 38, -21600, false ],
		"HAST" => [ 39, -36000, false ],
		"HAT" => [ 40, -9000, false ],
		"HAY" => [ 41, -28800, false ],
		"HNA" => [ 42, -14400, false ],
		"HNC" => [ 43, -21600, false ],
		"HNE" => [ 44, -18000, false ],
		"HNP" => [ 45, -28800, false ],
		"HNR" => [ 46, -25200, false ],
		"HNT" => [ 47, -12600, false ],
		"HNY" => [ 48, -32400, false ],
		"I" => [ 49, 32400, true ],
		"IST" => [ 50, 3600, false ],
		"K" => [ 51, 36000, true ],
		"L" => [ 52, 39600, true ],
		"M" => [ 53, 43200, true ],
		"MDT" => [ 54, -21600, false ],
		"MESZ" => [ 55, 7200, false ],
		"MEZ" => [ 56, 3600, false ],
		"MSD" => [ 57, 14400, false ],
		"MSK" => [ 58, 10800, false ],
		"MST" => [ 59, -25200, false ],
		"N" => [ 60, -3600, true ],
		"NDT" => [ 61, -9000, false ],
		"NFT" => [ 62, 41400, false ],
		"NST" => [ 63, -12600, false ],
		"O" => [ 64, -7200, true ],
		"P" => [ 65, -10800, true ],
		"PDT" => [ 66, -25200, false ],
		"PST" => [ 67, -28800, false ],
		"Q" => [ 68, -14400, true ],
		"R" => [ 69, -18000, true ],
		"S" => [ 70, -21600, true ],
		"T" => [ 71, -25200, true ],
		"U" => [ 72, -28800, true ],
		"V" => [ 73, -32400, true ],
		"W" => [ 74, -36000, true ],
		"WDT" => [ 75, 32400, false ],
		"WEDT" => [ 76, 3600, false ],
		"WEST" => [ 77, 3600, false ],
		"WET" => [ 78, 0, false ],
		"WST" => [ 79, 28800, false ],
		"X" => [ 80, -39600, true ],
		"Y" => [ 81, -43200, true ],
	];

	/**
	 * Generated from the DateTimeZone::listAbbreviations and contains "Area/Location",
	 * e.g. "America/New_York".
	 *
	 * Citing https://en.wikipedia.org/wiki/Tz_database which describes that " ...
	 * The underscore character is used in place of spaces. Hyphens are used
	 * where they appear in the name of a location ...  names have a maximum
	 * length of 14 characters ..."
	 *
	 * @var array
	 */
	private static $dateTimeZoneList = [];

	/**
	 * @var array
	 */
	private static $offsetCache = [];

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public static function listShortAbbreviations() {
		return array_keys( self::$shortList );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $identifer
	 *
	 * @return boolean
	 */
	public static function isValid( $identifer ) {

		$identifer = str_replace( ' ', '_', $identifer );

		if ( isset( self::$shortList[strtoupper( $identifer )] ) ) {
			return true;
		}

		$dateTimeZoneList = self::getDateTimeZoneList();

		if ( isset( $dateTimeZoneList[$identifer] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $abbreviation
	 *
	 * @return boolean
	 */
	public static function isMilitary( $abbreviation ) {

		$abbreviation = strtoupper( $abbreviation );

		if ( isset( self::$shortList[$abbreviation] ) ) {
			return self::$shortList[$abbreviation][2];
		}

		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $identifer
	 *
	 * @return false|integer
	 */
	public static function getIdByAbbreviation( $identifer ) {

		if ( isset( self::$shortList[strtoupper( $identifer )] ) ) {
			return self::$shortList[strtoupper( $identifer )][0];
		}

		$identifer = str_replace( ' ', '_', $identifer );
		$dateTimeZoneList = self::getDateTimeZoneList();

		if ( isset( $dateTimeZoneList[$identifer] ) ) {
			return $dateTimeZoneList[$identifer];
		}

		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $identifer
	 *
	 * @return false|string
	 */
	public static function getTimezoneLiteralById( $identifer ) {

		foreach ( self::$shortList as $abbreviation => $value ) {
			if ( is_numeric( $identifer ) && $value[0] == $identifer ) {
				return $abbreviation;
			}
		}

		$dateTimeZoneList = self::getDateTimeZoneList();

		if ( ( $abbreviation = array_search( $identifer, $dateTimeZoneList ) ) !== false ) {
			return $abbreviation;
		}

		return false;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $abbreviation
	 *
	 * @return false|string
	 */
	public static function getOffsetByAbbreviation( $abbreviation ) {

		if ( isset( self::$shortList[strtoupper( $abbreviation )] ) ) {
			return self::$shortList[strtoupper( $abbreviation )][1];
		}

		$abbreviation = str_replace( ' ', '_', $abbreviation );

		if ( isset( self::$offsetCache[$abbreviation] ) ) {
			return self::$offsetCache[$abbreviation];
		}

		$offset = false;

		try {
			$dateTimeZone = new DateTimeZone( $abbreviation );
			$offset = $dateTimeZone->getOffset( new DateTime() );
		} catch( \Exception $e ) {
			//
		}

		return self::$offsetCache[$abbreviation] = $offset;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $abbreviation
	 *
	 * @return string
	 */
	public static function getNameByAbbreviation( $abbreviation ) {

		$abbreviation = strtoupper( $abbreviation );

		if ( isset( self::$shortList[$abbreviation] ) ) {
			$name = timezone_name_from_abbr( $abbreviation );
		}

		// If the abbrevation couldn't be matched use the offset instead
		if ( !$name ) {
			$name = timezone_name_from_abbr(
				"",
				self::getOffsetByAbbreviation( $abbreviation ) * 3600,
				0
			);
		}

		return $name;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $abbreviation
	 *
	 * @return DateInterval
	 */
	public static function newDateIntervalWithOffsetFrom( $abbreviation ) {

		$minutes = 0;
		$hour = 0;

		// Here we don't care for +/-, the caller of the function
		// has to care for it
		$offsetInSeconds = abs( self::getOffsetByAbbreviation( $abbreviation ) );

		return new DateInterval( "PT{$offsetInSeconds}S" );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $abbreviation
	 *
	 * @return false|DateTimeZone
	 */
	public static function newDateTimeZone( $abbreviation ) {

		try {
			$dateTimeZone = new DateTimeZone( $abbreviation );
		} catch( \Exception $e ) {
			if ( ( $name = self::getNameByAbbreviation( $abbreviation ) ) !== false ) {
				return new DateTimeZone( $name );
			}
		}

		return false;
	}

	/**
	 * Generated from the DateTimeZone::listAbbreviations
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public static function getDateTimeZoneList() {

		if ( self::$dateTimeZoneList !== [] ) {
			return self::$dateTimeZoneList;
		}

		$list = DateTimeZone::listIdentifiers();

		foreach ( $list as $identifier ) {
			self::$dateTimeZoneList[$identifier] = $identifier;
		}

		return self::$dateTimeZoneList;
	}

	/**
	 * @since 2.5
	 *
	 * @param DateTime $dateTime
	 * @param string|integer &$tz
	 *
	 * @return DateTime
	 */
	public static function getModifiedTime( DateTime $dateTime, &$tz = 0 ) {

		if ( ( $timezoneLiteral = self::getTimezoneLiteralById( $tz ) ) === false ) {
			$tz = $timezoneLiteral;
			return $dateTime;
		}

		$dateTimeZone = null;

		if ( !self::isMilitary( $timezoneLiteral ) && self::getOffsetByAbbreviation( $timezoneLiteral ) != 0 ) {
			$dateTimeZone = self::newDateTimeZone( $timezoneLiteral );
		}

		// DI is stored in UTC time therefore find and add the offset
		if ( !$dateTimeZone instanceof DateTimeZone ) {
			$dateInterval = self::newDateIntervalWithOffsetFrom( $timezoneLiteral );

			if ( self::getOffsetByAbbreviation( $timezoneLiteral ) > 0 ) {
				$dateTime->add( $dateInterval );
			} else {
				$dateTime->sub( $dateInterval );
			}
		} else {
			$dateTime->setTimezone( $dateTimeZone );
		}

		$tz = $timezoneLiteral;

		return $dateTime;
	}

}
