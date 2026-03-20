<?php

namespace SMW\Exporter;

use RuntimeException;
use SMW\DataItems\Blob;
use SMW\DataItems\Boolean;
use SMW\DataItems\DataItem;
use SMW\DataItems\Number;
use SMW\DataItems\Time;

/**
 * This class only maps primitive types (string, boolean, integers ) mostly to
 * be encoded as literal and all other dataitems are handled separately.
 *
 * @license GPL-2.0-or-later
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
		if ( $dataItem instanceof Boolean ) {
			$val = self::mapBoolean( $dataItem );
		} elseif ( $dataItem instanceof Number ) {
			$val = self::mapNumber( $dataItem );
		} elseif ( $dataItem instanceof Blob ) {
			$val = self::mapString( $dataItem );
		} elseif ( $dataItem instanceof Time && $dataItem->getCalendarModel() === Time::CM_GREGORIAN ) {
			$val = self::mapGregorianCalendarModelTime( $dataItem );
		} else {
			throw new RuntimeException( "Cannot match the dataItem with type " . $dataItem->getDIType() );
		}

		return $val;
	}

	private static function mapString( Blob $dataItem ): array {
		return [
			'http://www.w3.org/2001/XMLSchema#string',
			smwfHTMLtoUTF8( $dataItem->getString() )
		];
	}

	private static function mapNumber( Number $dataItem ): array {
		return [
			'http://www.w3.org/2001/XMLSchema#double',
			strval( $dataItem->getNumber() )
		];
	}

	private static function mapBoolean( Boolean $dataItem ): array {
		return [
			'http://www.w3.org/2001/XMLSchema#boolean',
			$dataItem->getBoolean() ? 'true' : 'false'
		];
	}

	private static function mapGregorianCalendarModelTime( Time $dataItem ): array {
		if ( $dataItem->getYear() > 0 ) {
			$xsdvalue = str_pad( $dataItem->getYear(), 4, "0", STR_PAD_LEFT );
		} else {
			$xsdvalue = '-' . str_pad( 1 - $dataItem->getYear(), 4, "0", STR_PAD_LEFT );
		}

		$xsdtype = 'http://www.w3.org/2001/XMLSchema#gYear';

		if ( $dataItem->getPrecision() >= Time::PREC_YM ) {
			$xsdtype = 'http://www.w3.org/2001/XMLSchema#gYearMonth';
			$xsdvalue .= '-' . str_pad( $dataItem->getMonth(), 2, "0", STR_PAD_LEFT );
			if ( $dataItem->getPrecision() >= Time::PREC_YMD ) {
				$xsdtype = 'http://www.w3.org/2001/XMLSchema#date';
				$xsdvalue .= '-' . str_pad( $dataItem->getDay(), 2, "0", STR_PAD_LEFT );
				if ( $dataItem->getPrecision() == Time::PREC_YMDT ) {
					$xsdtype = 'http://www.w3.org/2001/XMLSchema#dateTime';
					$xsdvalue .= 'T' .
						sprintf( "%02d", $dataItem->getHour() ) . ':' .
						sprintf( "%02d", $dataItem->getMinute() ) . ':' .
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
