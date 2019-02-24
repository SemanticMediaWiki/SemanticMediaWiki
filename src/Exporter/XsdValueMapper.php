<?php

namespace SMW\Exporter;

use RuntimeException;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDINumber as DINumber;
use SMWDITime as DITime;
use SMW\Exporter\Element\ExpLiteral;

/**
 * This class only maps primitive types (string, boolean, integers ) mostly to
 * be encoded as literal and all other dataitems are handled separately.
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class XsdValueMapper {

	/**
	 * @since 2.2
	 *
	 * @param DataItem $dataItem
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public static function map( DataItem $dataItem ) {

		if ( $dataItem instanceof DIBoolean ) {
			$val = self::mapBoolean( $dataItem );
		} elseif ( $dataItem instanceof DINumber ) {
			$val = self::mapNumber( $dataItem );
		} elseif ( $dataItem instanceof DIBlob ) {
			$val = self::mapString( $dataItem );
		} elseif ( $dataItem instanceof DITime && $dataItem->getCalendarModel() === DITime::CM_GREGORIAN ) {
			$val = self::mapGregorianCalendarModelTime( $dataItem );
		} else {
			throw new RuntimeException( "Cannot match the dataItem with type " . $dataItem->getDIType() );
		}

		return $val;
	}

	private static function mapString( DIBlob $dataItem ) {
		return [
			'http://www.w3.org/2001/XMLSchema#string',
			smwfHTMLtoUTF8( $dataItem->getString() )
		];
	}

	private static function mapNumber( DINumber $dataItem ) {
		return[
			'http://www.w3.org/2001/XMLSchema#double',
			strval( $dataItem->getNumber() )
		];
	}

	private static function mapBoolean( DIBoolean $dataItem ) {
		return [
			'http://www.w3.org/2001/XMLSchema#boolean',
			$dataItem->getBoolean() ? 'true' : 'false'
		];
	}

	private static function mapGregorianCalendarModelTime( DITime $dataItem  ) {

		if ( $dataItem->getYear() > 0 ) {
			$xsdvalue = str_pad( $dataItem->getYear(), 4, "0", STR_PAD_LEFT );
		} else {
			$xsdvalue = '-' . str_pad( 1 - $dataItem->getYear(), 4, "0", STR_PAD_LEFT );
		}

		$xsdtype = 'http://www.w3.org/2001/XMLSchema#gYear';

		if ( $dataItem->getPrecision() >= DITime::PREC_YM ) {
			$xsdtype = 'http://www.w3.org/2001/XMLSchema#gYearMonth';
			$xsdvalue .= '-' . str_pad( $dataItem->getMonth(), 2, "0", STR_PAD_LEFT );
			if ( $dataItem->getPrecision() >= DITime::PREC_YMD ) {
				$xsdtype = 'http://www.w3.org/2001/XMLSchema#date';
				$xsdvalue .= '-' . str_pad( $dataItem->getDay(), 2, "0", STR_PAD_LEFT );
				if ( $dataItem->getPrecision() == DITime::PREC_YMDT ) {
					$xsdtype = 'http://www.w3.org/2001/XMLSchema#dateTime';
					$xsdvalue .= 'T' .
						sprintf( "%02d", $dataItem->getHour() ) . ':' .
						sprintf( "%02d", $dataItem->getMinute()) . ':' .
						sprintf( "%02d", $dataItem->getSecond() );
				}

				// https://www.w3.org/TR/2005/NOTE-timezone-20051013/
				// "Time zone identification in the date and time types relies
				// entirely on time zone offset from UTC."
				// Zone offset Z indicates UTC
				$xsdvalue .= 'Z';
			}
		}

		return [ $xsdtype, $xsdvalue ];
	}

}
