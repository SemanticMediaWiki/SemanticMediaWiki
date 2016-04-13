<?php

namespace SMW\Exporter;

use RuntimeException;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDINumber as DINumber;
use SMWDITime as DITime;

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
	 * @var string
	 */
	private $xsdValue = '';

	/**
	 * @var string
	 */
	private $xsdType = '';

	/**
	 * @since 2.2
	 *
	 * @param DataItem $dataItem
	 *
	 * @throws RuntimeException
	 */
	public function process( DataItem $dataItem ) {

		if ( $dataItem instanceof DIBoolean ) {
			$this->parseToBooleanValue( $dataItem );
		} elseif ( $dataItem instanceof DINumber ) {
			$this->parseToDoubleValue( $dataItem );
		} elseif ( $dataItem instanceof DIBlob ) {
			$this->parseToStringValue( $dataItem );
		} elseif ( $dataItem instanceof DITime && $dataItem->getCalendarModel() === DITime::CM_GREGORIAN ) {
			$this->parseToTimeValueForGregorianCalendarModel( $dataItem );
		} else {
			throw new RuntimeException( "Cannot match the dataItem of type " . $dataItem->getDIType() );
		}
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getXsdValue() {
		return $this->xsdValue;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getXsdType() {
		return $this->xsdType;
	}

	private function parseToStringValue( DIBlob $dataItem ) {
		$this->xsdValue = smwfHTMLtoUTF8( $dataItem->getString() );
		$this->xsdType = 'http://www.w3.org/2001/XMLSchema#string';
	}

	private function parseToDoubleValue( DINumber $dataItem ) {
		$this->xsdValue = strval( $dataItem->getNumber() );
		$this->xsdType = 'http://www.w3.org/2001/XMLSchema#double';
	}

	private function parseToBooleanValue( DIBoolean $dataItem ) {
		$this->xsdValue = $dataItem->getBoolean() ? 'true' : 'false';
		$this->xsdType = 'http://www.w3.org/2001/XMLSchema#boolean';
	}

	private function parseToTimeValueForGregorianCalendarModel( DITime $dataItem  ) {

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

		$this->xsdValue = $xsdvalue;
		$this->xsdType = $xsdtype;
	}

}
