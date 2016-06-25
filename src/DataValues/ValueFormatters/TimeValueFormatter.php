<?php

namespace SMW\DataValues\ValueFormatters;

use RuntimeException;
use SMW\IntlTimeFormatter;
use SMW\Localizer;
use SMWDataValue as DataValue;
use SMWDITime as DITime;
use SMWTimeValue as TimeValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 * @author Markus Krötzsch
 * @author Fabian Howahl
 * @author Terry A. Hurlbut
 */
class TimeValueFormatter extends DataValueFormatter {

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function isFormatterFor( DataValue $dataValue ) {
		return $dataValue instanceof TimeValue;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function format( $type, $linker = null ) {

		if ( !$this->dataValue instanceof TimeValue ) {
			throw new RuntimeException( "The formatter is missing a valid TimeValue object" );
		}

		if (
			$this->dataValue->isValid() &&
			( $type === self::WIKI_SHORT || $type === self::HTML_SHORT ) ) {
			return ( $this->dataValue->getCaption() !== false ) ? $this->dataValue->getCaption() : $this->getPreferredCaption();
		}

		if (
			$this->dataValue->isValid() &&
			( $type === self::WIKI_LONG || $type === self::HTML_LONG ) ) {
			return $this->getPreferredCaption();
		}

		// #1074
		return $this->dataValue->getCaption() !== false ? $this->dataValue->getCaption() : '';
	}

	/**
	 * @private
	 *
	 * Compute a string representation that largely follows the ISO8601 standard
	 * of representing dates. Large year numbers may have more than 4 digits,
	 * which is not strictly conforming to the standard. The date includes year,
	 * month, and day regardless of the input precision, but will only include
	 * time when specified.
	 *
	 * Conforming to the 2000 version of ISO8601, year 1 BC(E) is represented
	 * as "0000", year 2 BC(E) as "-0001" and so on.
	 *
	 * @since 2.4
	 *
	 * @param DITime $dataItem
	 * @param boolean $mindefault determining whether values below the
	 * precision of our input should be completed with minimal or maximal
	 * conceivable values
	 *
	 * @return string
	 */
	public function getISO8601Date( $mindefault = true ) {

		$dataItem = $this->dataValue->getDataItemForCalendarModel( DITime::CM_GREGORIAN );
		$precision = $dataItem->getPrecision();

		$result = $dataItem->getYear() > 0 ? '' : '-';
		$result .= str_pad( $dataItem->getYear(), 4, "0", STR_PAD_LEFT );

		$monthnum = $precision >= DITime::PREC_YM ? $dataItem->getMonth() : ( $mindefault ? 1 : 12 );
		$result .= '-' . str_pad( $monthnum, 2, "0", STR_PAD_LEFT );

		$day = $dataItem->getDay();

		if ( !$mindefault && $precision < DITime::PREC_YMD ) {
			$day = DITime::getDayNumberForMonth( $monthnum, $dataItem->getYear(), DITime::CM_GREGORIAN );
		}

		$result .= '-' . str_pad( $day, 2, "0", STR_PAD_LEFT );

		if ( $precision === DITime::PREC_YMDT ) {
			$result .= 'T' . $this->getTimeString( ( $mindefault ? '00:00:00' : '23:59:59' ) );
		}

		return $result;
	}

	/**
	 * @private
	 *
	 * Use MediaWiki's date and time formatting. It can't handle all inputs
	 * properly, but has superior i18n support.
	 *
	 * @since 2.4
	 *
	 * @param DITime $dataItem
	 *
	 * @return string
	 */
	public function getMediaWikiDate() {

		$dataItem = $this->dataValue->getDataItemForCalendarModel( DITime::CM_GREGORIAN );
		$precision = $dataItem->getPrecision();

		$language = Localizer::getInstance()->getLanguage(
			$this->dataValue->getOptionValueFor( 'user.language' )
		);

		$year = $dataItem->getYear();

		if ( $year < 0 || $year > 9999 ) {
			$year = '0000';
		}

		$year = str_pad( $year, 4, "0", STR_PAD_LEFT );

		if ( $precision <= DITime::PREC_Y ) {
			return $language->formatNum( $year, true );
		}

		$month = str_pad( $dataItem->getMonth(), 2, "0", STR_PAD_LEFT );
		$day = str_pad( $dataItem->getDay(), 2, "0", STR_PAD_LEFT );

		// date/timeanddate options:
		// 1. Whether to adjust the time output according to the user
		// configured offset ($timecorrection)
		// 2. format, if it's false output the default one (default true)
		// 3. timecorrection, the time offset as returned from
		// Special:Preferences

		if ( $precision <= DITime::PREC_YMD ) {
			return $language->date( "$year$month$day" . '000000', false, true, false );
		}

		$time = str_replace( ':', '', $this->getTimeString() );

		return $language->timeanddate( "$year$month$day$time", false, true, false );
	}

	/**
	 * @private
	 *
	 * @todo Internationalize the CE and BCE strings.
	 *
	 * Compute a suitable string to display the given date item.
	 *
	 * @note MediaWiki's date functions are not applicable for the range of
	 * historic dates we support.
	 *
	 * @since 2.4
	 *
	 * @param DITime $dataitem
	 *
	 * @return string
	 */
	public function getCaptionFromDataItem( DITime $dataItem ) {

		// If the language code is empty then the content language code is used
		$extraneousLanguage = Localizer::getInstance()->getExtraneousLanguage(
			Localizer::getInstance()->getContentLanguage()
		);

		// https://en.wikipedia.org/wiki/Anno_Domini
		// "...placing the "AD" abbreviation before the year number ... BC is
		// placed after the year number (for example: AD 2016, but 68 BC)..."
		// Chicago Manual of Style 2010, pp. 476â€“7; Goldstein 2007, p. 6.

		if ( $dataItem->getYear() > 0 ) {
			$cestring = $dataItem->getEra() > 0 ? 'AD' : '';
			$result = ( $cestring ? ( $cestring . ' ' ) : '' ) . number_format( $dataItem->getYear(), 0, '.', '' );
		} else {
			$bcestring = 'BC';
			$result = number_format( -( $dataItem->getYear() ), 0, '.', '' ) . ( $bcestring ? ( ' ' . $bcestring ) : '' );
		}

		if ( $dataItem->getPrecision() >= DITime::PREC_YM ) {
			$result = $extraneousLanguage->getMonthLabel( $dataItem->getMonth() ) . " " . $result;
		}

		if ( $dataItem->getPrecision() >= DITime::PREC_YMD ) {
			$result = $dataItem->getDay() . " " . $result;
		}

		if ( $dataItem->getPrecision() >= DITime::PREC_YMDT ) {
			$result .= " " . $this->getTimeString();
		}

		$result .= $this->hintCalendarModel( $dataItem );

		return $result;
	}

	/**
	 * @private
	 *
	 * Return the time as a string. The time string has the format HH:MM:SS,
	 * without any timezone information (see class documentation for details
	 * on current timezone handling).
	 * The parameter $default optionally specifies the value returned
	 * if the date is valid but has no explicitly specified time. It can
	 * also be set to false to detect this situation.
	 *
	 * @since  2.4
	 *
	 * @param string $default
	 *
	 * @return string
	 */
	public function getTimeString( $default = '00:00:00' ) {

		$dataItem = $this->dataValue->getDataItemForCalendarModel( DITime::CM_GREGORIAN );

		if ( $dataItem->getPrecision() < DITime::PREC_YMDT ) {
			return $default;
		}

		return sprintf( "%02d", $dataItem->getHour() ) . ':' .
		       sprintf( "%02d", $dataItem->getMinute() ) . ':' .
		       sprintf( "%02d", $dataItem->getSecond() );
	}

	/**
	 * @since 2.4
	 *
	 * @param  DITime|null $dataItem
	 *
	 * @return string
	 */
	public function getCaptionFromFreeFormat( DITime $dataItem = null ) {

		$language = Localizer::getInstance()->getLanguage(
			$this->dataValue->getOptionValueFor( 'user.language' )
		);

		// Prehistory dates are not supported when using this output format
		// Only match options encapsulated by [ ... ]
		if (
			$dataItem !== null &&
			$dataItem->getYear() > DITime::PREHISTORY &&
			preg_match("/\[([^\]]*)\]/", $this->dataValue->getOutputFormat(), $matches ) ) {
			$intlTimeFormatter = new IntlTimeFormatter( $dataItem, $language );

			if ( ( $caption = $intlTimeFormatter->format( $matches[1] ) ) !== false ) {

				if ( $intlTimeFormatter->containsValidDateFormatRule( $matches[1] ) ) {
					$caption .= $this->hintCalendarModel( $dataItem );
				}

				return $caption;
			}
		}

		return $this->getISO8601Date();
	}

	/**
	 * @private
	 *
	 * @since 2.4
	 *
	 * @param  DITime|null $dataItem
	 *
	 * @return string
	 */
	public function getLocalizedFormat( DITime $dataItem = null ) {

		$language = Localizer::getInstance()->getLanguage(
			$this->dataValue->getOptionValueFor( 'user.language' )
		);

		if ( $dataItem !== null && $dataItem->getYear() > DITime::PREHISTORY ) {

			$intlTimeFormatter = new IntlTimeFormatter(
				$dataItem,
				$language
			);

			return $intlTimeFormatter->getLocalizedFormat() . $this->hintCalendarModel( $dataItem );
		}

		return $this->getISO8601Date();
	}


	/**
	 * Compute a suitable string to display this date, taking into account the
	 * output format and the preferrable calendar models for the data.
	 *
	 * @note MediaWiki's date functions are not applicable for the range
	 * of historic dates we support.
	 *
	 * @return string
	 */
	protected function getPreferredCaption() {

		$dataItem = $this->dataValue->getDataItem();
		$format = strtoupper( $this->dataValue->getOutputFormat() );

		if ( $format == 'ISO' || $this->dataValue->getOutputFormat() == '-' ) {
			return $this->getISO8601Date();
		} elseif ( $format == 'MEDIAWIKI' ) {
			return $this->getMediaWikiDate();
		} elseif ( $format == 'SORTKEY' ) {
			return $dataItem->getSortKey();
		} elseif ( $format == 'JD' ) {
			return $dataItem->getJD();
		}

		// Does the formatting require calendar conversion?
		$model = $dataItem->getCalendarModel();

		if (
			( strpos( $format, 'JL' ) !== false ) ||
			( $dataItem->getJD() < TimeValue::J1582 && strpos( $format, 'GR' ) === false ) ) {
			$model = DITime::CM_JULIAN;
		} elseif ( strpos( $format, 'GR' ) !== false ) {
			$model = DITime::CM_GREGORIAN;
		}

		if ( strpos( $format, '-F[' ) !== false ) {
			return $this->getCaptionFromFreeFormat( $this->dataValue->getDataItemForCalendarModel( $model ) );
		} elseif ( $format == 'LOCL' ) {
			return $this->getLocalizedFormat( $this->dataValue->getDataItemForCalendarModel( $model ) );
		} elseif ( $dataItem->getYear() > TimeValue::PREHISTORY && $dataItem->getPrecision() >= DITime::PREC_YM ) {
			// Do not convert between Gregorian and Julian if only
			// year is given (years largely overlap in history, but
			// assuming 1 Jan as the default date, the year number
			// would change in conversion).
			// Also do not convert calendars in prehistory: not
			// meaningful (getDataItemForCalendarModel may return null).
			return $this->getCaptionFromDataItem( $this->dataValue->getDataItemForCalendarModel( $model ) );
		}

		return $this->getCaptionFromDataItem( $dataItem );
	}

	private function hintCalendarModel( $dataItem ) {

		if ( $this->dataValue->isEnabledFeature( SMW_DV_TIMEV_CM ) && $dataItem->getCalendarModel() !== DITime::CM_GREGORIAN ) {
			return ' ' . \Html::rawElement( 'sup', array(), $dataItem->getCalendarModelLiteral() );
		}

		return '';
	}

}
