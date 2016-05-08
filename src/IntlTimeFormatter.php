<?php

namespace SMW;

use Language;
use SMWDITime as DITime;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class IntlTimeFormatter {

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
	 * @return string|boolean
	 */
	public function getLocalizedFormat() {

		$dateTime = $this->dataItem->asDateTime();

		if ( !$dateTime ) {
			return false;
		}

		$extraneousLanguage = Localizer::getInstance()->getExtraneousLanguage(
			$this->language
		);

		$preferredDateFormatByPrecision = $extraneousLanguage->getPreferredDateFormatByPrecision(
			$this->dataItem->getPrecision()
		);

		return $this->formatWithLocalizedTextReplacement(
			$dateTime,
			$preferredDateFormatByPrecision
		);
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

}
