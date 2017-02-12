<?php

namespace SMW\DataValues\Time;

use DateTimeZone;
use DateInterval;
use DateTime;

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
	private static $shortList = array(
		"UTC" => array( 0, 0, false ),
		"Z" => array( 1, 0, true ),
		"A" => array( 2, 3600, true ),
		"ACDT" => array( 3, 37800, false ),
		"ACST" => array( 4, 34200, false ),
		"ADT" => array( 5, -10800, false ),
		"AEDT" => array( 6, 39600, false ),
		"AEST" => array( 7, 36000, false ),
		"AKDT" => array( 8, -28800, false ),
		"AKST" => array( 9, -32400, false ),
		"AST" => array( 10, -14400, false ),
		"AWDT" => array( 11, 32400, false ),
		"AWST" => array( 12, 28800, false ),
		"B" => array( 13, 7200, true ),
		"BST" => array( 14, 3600, false ),
		"C" => array( 15, 10800, true ),
		"CDT" => array( 16, -18000, false ),
		"CEDT" => array( 17, 7200, false ),
		"CEST" => array( 18, 7200, false ),
		"CET" => array( 19, 3600, false ),
		"CST" => array( 20, -21600, false ),
		"CXT" => array( 21, 25200, false ),
		"D" => array( 22, 14400, true ),
		"E" => array( 23, 18000, true ),
		"EDT" => array( 24, -14400, false ),
		"EEDT" => array( 25, 10800, false ),
		"EEST" => array( 26, 10800, false ),
		"EET" => array( 27, 7200, false ),
		"EST" => array( 28, -18000, false ),
		"F" => array( 29, 21600, true ),
		"G" => array( 30, 25200, true ),
		"GMT" => array( 31, 0, false ),
		"H" => array( 32, 28800, true ),
		"HAA" => array( 33, -10800, false ),
		"HAC" => array( 34, -18000, false ),
		"HADT" => array( 35, -32400, false ),
		"HAE" => array( 36, -14400, false ),
		"HAP" => array( 37, -25200, false ),
		"HAR" => array( 38, -21600, false ),
		"HAST" => array( 39, -36000, false ),
		"HAT" => array( 40, -9000, false ),
		"HAY" => array( 41, -28800, false ),
		"HNA" => array( 42, -14400, false ),
		"HNC" => array( 43, -21600, false ),
		"HNE" => array( 44, -18000, false ),
		"HNP" => array( 45, -28800, false ),
		"HNR" => array( 46, -25200, false ),
		"HNT" => array( 47, -12600, false ),
		"HNY" => array( 48, -32400, false ),
		"I" => array( 49, 32400, true ),
		"IST" => array( 50, 3600, false ),
		"K" => array( 51, 36000, true ),
		"L" => array( 52, 39600, true ),
		"M" => array( 53, 43200, true ),
		"MDT" => array( 54, -21600, false ),
		"MESZ" => array( 55, 7200, false ),
		"MEZ" => array( 56, 3600, false ),
		"MSD" => array( 57, 14400, false ),
		"MSK" => array( 58, 10800, false ),
		"MST" => array( 59, -25200, false ),
		"N" => array( 60, -3600, true ),
		"NDT" => array( 61, -9000, false ),
		"NFT" => array( 62, 41400, false ),
		"NST" => array( 63, -12600, false ),
		"O" => array( 64, -7200, true ),
		"P" => array( 65, -10800, true ),
		"PDT" => array( 66, -25200, false ),
		"PST" => array( 67, -28800, false ),
		"Q" => array( 68, -14400, true ),
		"R" => array( 69, -18000, true ),
		"S" => array( 70, -21600, true ),
		"T" => array( 71, -25200, true ),
		"U" => array( 72, -28800, true ),
		"V" => array( 73, -32400, true ),
		"W" => array( 74, -36000, true ),
		"WDT" => array( 75, 32400, false ),
		"WEDT" => array( 76, 3600, false ),
		"WEST" => array( 77, 3600, false ),
		"WET" => array( 78, 0, false ),
		"WST" => array( 79, 28800, false ),
		"X" => array( 80, -39600, true ),
		"Y" => array( 81, -43200, true ),
	);

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
	private static $dateTimeZoneList = array();

	/**
	 * @var array
	 */
	private static $offsetCache = array();

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
	public static function newDateIntervalWithOffsetBy( $abbreviation ) {

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

		if ( self::$dateTimeZoneList !== array() ) {
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
	 * @param DateTime &$dateTime
	 * @param string|integer $identifer
	 *
	 * @return string
	 */
	public static function getTimezoneLiteralWithModifiedDateTime( DateTime &$dateTime, $identifer = 0 ) {

		if ( ( $timezoneLiteral = self::getTimezoneLiteralById( $identifer ) ) === false ) {
			return '';
		}

		$dateTimeZone = null;

		if ( !self::isMilitary( $timezoneLiteral ) && self::getOffsetByAbbreviation( $timezoneLiteral ) != 0 ) {
			$dateTimeZone = self::newDateTimeZone( $timezoneLiteral );
		}

		// DI is stored in UTC time therefore find and add the offset
		if ( !$dateTimeZone instanceof DateTimeZone ) {
			$dateInterval = self::newDateIntervalWithOffsetBy( $timezoneLiteral );

			if ( self::getOffsetByAbbreviation( $timezoneLiteral ) > 0 ) {
				$dateTime->add( $dateInterval );
			} else {
				$dateTime->sub( $dateInterval );
			}
		} else {
			$dateTime->setTimezone( $dateTimeZone );
		}

		return $timezoneLiteral;
	}

}
