<?php

namespace SMW\DataValues\ValueFormatters;

use SMW\Localizer;
use SMWDataValue as DataValue;
use SMWTimeValue as TimeValue;
use SMWDITime as DITime;
use RuntimeException;

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
		return $dataValue instanceOf TimeValue;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function format( $type, $linker = null ) {

		if ( !$this->dataValue instanceOf TimeValue ) {
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

		$contentLanguage = Localizer::getInstance()->getContentLanguage();

		$year = $dataItem->getYear();

		if ( $year < 0 || $year > 9999 ) {
			$year = '0000';
		}

		$year = str_pad( $year, 4, "0", STR_PAD_LEFT );

		if ( $precision <= DITime::PREC_Y ) {
			return $contentLanguage->formatNum( $year, true );
		}

		$month = str_pad( $dataItem->getMonth(), 2, "0", STR_PAD_LEFT );
		$day = str_pad( $dataItem->getDay(), 2, "0", STR_PAD_LEFT );

		if ( $precision <= DITime::PREC_YMD ) {
			return $contentLanguage->date( "$year$month$day" . '000000', false, false );
		}

		$time = str_replace( ':', '', $this->getTimeString() );

		return $contentLanguage->timeanddate( "$year$month$day$time", false, false );
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

		$extraneousLanguage = Localizer::getInstance()->getExtraneousLanguage();

		if ( $dataItem->getYear() > 0 ) {
			$cestring = '';
			$result = number_format( $dataItem->getYear(), 0, '.', '' ) . ( $cestring ? ( ' ' . $cestring ) : '' );
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
		} elseif ( $dataItem->getYear() > TimeValue::PREHISTORY && $dataItem->getPrecision() >= DITime::PREC_YM ) {
			// Do not convert between Gregorian and Julian if only
			// year is given (years largely overlap in history, but
			// assuming 1 Jan as the default date, the year number
			// would change in conversion).
			// Also do not convert calendars in prehistory: not
			// meaningful (getDataItemForCalendarModel may return null).
			if ( ( $format == 'JL' ) ||
				( $dataItem->getJD() < TimeValue::J1582
				  && $format != 'GR' ) ) {
				$model = DITime::CM_JULIAN;
			} else {
				$model = DITime::CM_GREGORIAN;
			}
			return $this->getCaptionFromDataItem( $this->dataValue->getDataItemForCalendarModel( $model ) );
		}

		return $this->getCaptionFromDataItem( $dataItem );
	}

}
