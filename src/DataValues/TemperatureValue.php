<?php

namespace SMW\DataValues;

use SMWDINumber as DINumber;
use SMWNumberValue as NumberValue;

/**
 * This datavalue implements unit support for measuring temperatures. This is
 * mostly an example implementation of how to realise custom unit types easily.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class TemperatureValue extends NumberValue {

	/**
	 * DV identifier
	 */
	const TYPE_ID = '_tem';

	/**
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( self::TYPE_ID );
	}

	/**
	 * NumberValue::convertToMainUnit
	 */
	protected function convertToMainUnit( $number, $unit ) {

		$this->m_unitin = $this->getUnitID( $unit );

		if ( ( $value = $this->convertToKelvin( $number, $this->m_unitin ) ) === false ) {
			return false;
		}

		$this->m_dataitem = new DINumber( $value );

		return true;
	}

	/**
	 * NumberValue::makeConversionValues
	 */
	protected function makeConversionValues() {

		if ( $this->m_unitvalues !== false ) {
			return; // do this only once
		}

		$this->m_unitvalues = [];

		if ( !$this->isValid() ) {
			return $this->m_unitvalues;
		}

		$displayUnit = $this->getPreferredDisplayUnit();
		$number = $this->m_dataitem->getNumber();

		$unitvalues = [
			'K'  => $number,
			'°C' => $number - 273.15,
			'°F' => ( $number - 273.15 ) * 1.8 + 32,
			'°R' => ( $number ) * 1.8
		];

		if ( isset( $unitvalues[$displayUnit] ) ) {
			$this->m_unitvalues[$displayUnit] = $unitvalues[$displayUnit];
		}

		$this->m_unitvalues += $unitvalues;
	}

	/**
	 * NumberValue::makeUserValue
	 */
	protected function makeUserValue() {

		if ( ( $this->m_outformat ) && ( $this->m_outformat != '-' ) &&
		     ( $this->m_outformat != '-n' ) && ( $this->m_outformat != '-u' ) ) { // first try given output unit
			$printUnit = $this->normalizeUnit( $this->m_outformat );
			$this->m_unitin = $this->getUnitID( $printUnit );
		} else {
			$this->m_unitin = $this->getPreferredDisplayUnit();
			$printUnit = $this->m_unitin;
		}

		$value =$this->convertToUnit(
			$this->m_dataitem->getNumber(),
			$this->m_unitin
		);

		// -u is the format for displaying the unit only
		if ( $this->m_outformat == '-u' ) {
			$this->m_caption = '';
		} elseif ( ( $this->m_outformat != '-' ) && ( $this->m_outformat != '-n' ) ) {
			$this->m_caption = $this->getLocalizedFormattedNumber( $value );
			$this->m_caption .= '&#160;';
		} else {
			$this->m_caption = $this->getNormalizedFormattedNumber( $value );
			$this->m_caption .=  ' ';
		}

		// -n is the format for displaying the number only
		if ( $this->m_outformat == '-n' ) {
			$printUnit = '';
		}

		$this->m_caption .= $printUnit;
	}

	/**
	 * Helper method to find the main representation of a certain unit.
	 */
	protected function getUnitID( $unit ) {
		/// TODO possibly localise some of those strings
		switch ( $unit ) {
			case '':
			case 'K':
			case 'Kelvin':
			case 'kelvin':
			case 'kelvins':
			return 'K';
			// There's a dedicated Unicode character (℃, U+2103) for degrees C.
			// Your font may or may not display it; do not be alarmed.
			case '°C':
			case '℃':
			case 'Celsius':
			case 'centigrade':
			return '°C';
			case '°F':
			case 'Fahrenheit':
			return '°F';
			case '°R':
			case 'Rankine':
			return '°R';
			default:
			return false;
		}
	}

	/**
	 * NumberValue::getUnitList
	 */
	public function getUnitList() {
		return [ 'K', '°C', '°F', '°R' ];
	}

	/**
	 * NumberValue::getUnit
	 */
	public function getUnit() {
		return 'K';
	}

	private function getPreferredDisplayUnit() {

		$unit = $this->getUnit();

		if ( $this->getProperty() === null ) {
			return $unit;
		}

		$units = $this->dataValueServiceFactory->getPropertySpecificationLookup()->getDisplayUnits(
			$this->getProperty()
		);

		if ( $units !== null && $units !== [] ) {
			$unit = $this->getUnitID( end( $units ) );
		}

		return $this->getUnitID( $unit );
	}

	private function convertToKelvin( $number, $unit ) {

		switch ( $unit ) {
			case 'K':
				return $number;
			break;
			case '°C':
				return $number + 273.15;
			break;
			case '°F':
				return ( $number - 32 ) / 1.8 + 273.15;
			break;
			case '°R':
				return ( $number ) / 1.8;
		}

		return false; // unsupported unit
	}

	private function convertToUnit( $number, $unit ) {

		switch ( $unit ) {
			case 'K':
				return $number;
			break;
			case '°C':
				return $number - 273.15;
			break;
			case '°F':
				return ( $number - 273.15 ) * 1.8 + 32;
			break;
			case '°R':
				return ( $number ) * 1.8;
			break;
			// default: unit not supported
		}

		return 0;
	}

}
