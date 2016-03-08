<?php

namespace SMW;


use SMWDITime as DITime;
use Language;

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
	 * Permitted formatting options are specified by http://php.net/manual/en/function.date.php
	 *
	 * @since 2.4
	 *
	 * @param string $format
	 *
	 * @return string
	 */
	public function format( $format ) {

		$dateTime = $this->dataItem->asDateTime();

		if ( !$dateTime ) {
			return false;
		}

		$output = $this->getFormattedOutputWithTextualRepresentationReplacement(
			$dateTime,
			$format
		);

		if ( $this->dataItem->getCalendarModel() !== DITime::CM_GREGORIAN && $this->containsDateFormatRule( $format ) ) {
			$output .= ' ' . $this->dataItem->getCalendarModelLiteral();
		}

		return $output;
	}

	private function containsDateFormatRule( $format ) {

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
	private function getFormattedOutputWithTextualRepresentationReplacement( $dateTime, $format ) {

		$output = $dateTime->format( $format );

		$monthNumber = $dateTime->format( 'n' );
		$dayNumber = $dateTime->format( 'N' );

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
