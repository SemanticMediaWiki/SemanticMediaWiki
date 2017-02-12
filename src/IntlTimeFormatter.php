<?php

namespace SMW;

use Language;
use SMWDITime as DITime;
use DateTimeZone;
use SMW\DataValues\Time\Timezone;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class IntlTimeFormatter {

	const LOCL_DEFAULT = 0;
	const LOCL_TIMEZONE = 0x2;

	/**
	 * @var DITime
	 */
	private $dataItem;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @since 2.4
	 *
	 * @param DITime $dataItem
	 * @param Language|null $language
	 */
	public function __construct( DITime $dataItem, Language $language = null ) {
		$this->dataItem = $dataItem;
		$this->language = $language;

		if ( $this->language === null ) {
			$this->language = Localizer::getInstance()->getContentLanguage();
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $formatFlag
	 *
	 * @return string|boolean
	 */
	public function getLocalizedFormat( $formatFlag = self::LOCL_DEFAULT ) {

		$dateTime = $this->dataItem->asDateTime();
		$timezoneLiteral = '';

		if ( !$dateTime ) {
			return false;
		}

		if ( $formatFlag === self::LOCL_TIMEZONE ) {
			$timezoneLiteral = $this->getTimezoneLiteralWithModifiedDateTime( $dateTime );
		}

		$extraneousLanguage = Localizer::getInstance()->getExtraneousLanguage(
			$this->language
		);

		$precision = $this->dataItem->getPrecision();

		// Look for the Z precision which indicates the position of the TZ
		if ( $precision === SMW_PREC_YMDT && $timezoneLiteral !== '' ) {
			$precision = SMW_PREC_YMDTZ;
		}

		$preferredDateFormatByPrecision = $extraneousLanguage->getPreferredDateFormatByPrecision(
			$precision
		);

		// Mark the position since we cannot use DateTime::setTimezone in case
		// it is a military zone
		$preferredDateFormatByPrecision = str_replace( 'T', '**', $preferredDateFormatByPrecision );

		$dateString = $this->formatWithLocalizedTextReplacement(
			$dateTime,
			$preferredDateFormatByPrecision
		);

		return str_replace( '**', $timezoneLiteral, $dateString );
	}

	/**
	 * Permitted formatting options are specified by http://php.net/manual/en/function.date.php
	 *
	 * @since 2.4
	 *
	 * @param string $format
	 *
	 * @return string|boolean
	 */
	public function format( $format ) {

		$dateTime = $this->dataItem->asDateTime();

		if ( !$dateTime ) {
			return false;
		}

		$output = $this->formatWithLocalizedTextReplacement(
			$dateTime,
			$format
		);

		return $output;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $format
	 *
	 * @return boolean
	 */
	public function containsValidDateFormatRule( $format ) {

		foreach ( str_split( $format ) as $value ) {
			if ( in_array( $value, array( 'd', 'D', 'j', 'l', 'N', 'w', 'W', 'F', 'M', 'm', 'n', 't', 'L', 'o', 'Y', 'y', "c", 'r' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * DateTime generally outputs English textual representation
	 *
	 * - D	A textual representation of a day, three letters
	 * - l (lowercase 'L'), A full textual representation of the day of the week
	 * - F	A full textual representation of a month, such as January or March
	 * - M	A short textual representation of a month, three letters
	 * - a	Lowercase Ante meridiem and Post meridiem am or pm
	 * - A	Uppercase Ante meridiem and Post meridiem
	 */
	private function formatWithLocalizedTextReplacement( $dateTime, $format ) {

		$output = $dateTime->format( $format );

		// (n) DateTime => 1 through 12
		$monthNumber = $dateTime->format( 'n' );

		// (N) DateTime => 1 (for Monday) through 7 (for Sunday)
		// (w) DateTime => 0 (for Sunday) through 6 (for Saturday)
		// MW => 1 (for Sunday) through 7 (for Saturday)
		$dayNumber = $dateTime->format( 'w' ) + 1;

		if ( strpos( $format, 'F' ) !== false ) {
			$output = str_replace(
				$dateTime->format( 'F' ),
				$this->language->getMonthName( $monthNumber ),
				$output
			);
		}

		if ( strpos( $format, 'M' ) !== false ) {
			$output = str_replace(
				$dateTime->format( 'M' ),
				$this->language->getMonthAbbreviation( $monthNumber ),
				$output
			);
		}

		if ( strpos( $format, 'l' ) !== false ) {
			$output = str_replace(
				$dateTime->format( 'l' ),
				$this->language->getWeekdayName( $dayNumber ),
				$output
			);
		}

		if ( strpos( $format, 'D' ) !== false ) {
			$output = str_replace(
				$dateTime->format( 'D' ),
				$this->language->getWeekdayAbbreviation( $dayNumber ),
				$output
			);
		}

		return $output;
	}

	private function getTimezoneLiteralWithModifiedDateTime( &$dateTime ) {

		$timezone = 0;

		// If the date/time is in the UTC form then it is assumed that no other
		// TZ was selected to modify a value output hence it would be possible
		// to match a user preference with the `timecorrection` setting and use
		// it as an input for those dates to make the display take into account
		// a user ontext.
		//
		// - Inject/use a setter for the `timecorrection` preference as it depends
		// on a User object.
		// - The ParserCache doesn't recognizes changes for users with different
		// `timecorrection` settings therefore this needs to be dealt with before
		// otherwise a change by one user will remain active in the ParserCache
		// even though a different user has a different `timecorrection` setting.
		// Changes to the output is only triggered when the ParserCache is evicted or
		// purged manually.
		if ( $this->dataItem->getTimezone() === '0' ) {
		//	$parts = explode( '|', $GLOBALS['wgUser']->getOption( 'timecorrection' ) );
		//	$timezone = count( $parts ) == 3 ? $parts[2] : false;
		} else {
			$timezone = $this->dataItem->getTimezone();
		}

		return Timezone::getTimezoneLiteralWithModifiedDateTime( $dateTime, $timezone );
	}

}
