<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements unit support for measuring temperatures. This is
 * mostly an example implementation of how to realise custom unit types easily.
 * The implementation lacks support for localization and for selecting
 * "display units" on the property page as possible for the types with linear
 * unit conversion.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTemperatureValue extends SMWNumberValue {

	protected function convertToMainUnit( $number, $unit ) {
		// Find current ID and covert main values to Kelvin, if possible
		// Note: there is no error when unknown units are used.
		$this->m_unitin = $this->getUnitID( $unit );
		switch ( $this->m_unitin ) {
			case 'K':
				$value = $number;
			break;
			case '°C':
				$value = $number + 273.15;
			break;
			case '°F':
				$value = ( $number - 32 ) / 1.8 + 273.15;
			break;
			case '°R':
				$value = ( $number ) / 1.8;
			break;
			default: return false; // unsupported unit
		}
		$this->m_dataitem = new SMWDINumber( $value, $this->m_typeid );
		return true;
	}

	protected function makeConversionValues() {
		/// NOTE This class currently ignores display units.
		if ( $this->m_unitvalues !== false ) return; // do this only once
		if ( !$this->isValid() ) {
			$this->m_unitvalues = array();
		} else {
			$this->m_unitvalues = array( 'K' => $this->m_dataitem->getNumber(),
						'°C' => $this->m_dataitem->getNumber() - 273.15,
						'°F' => ( $this->m_dataitem->getNumber() - 273.15 ) * 1.8 + 32,
						'°R' => ( $this->m_dataitem->getNumber() ) * 1.8 );
		}
	}

	protected function makeUserValue() {
		$value = false;
		if ( ( $this->m_outformat ) && ( $this->m_outformat != '-' ) &&
		     ( $this->m_outformat != '-n' ) && ( $this->m_outformat != '-u' ) ) { // first try given output unit
			$printunit = SMWNumberValue::normalizeUnit( $this->m_outformat );
			$this->m_unitin = $this->getUnitID( $printunit );
			switch ( $this->m_unitin ) {
				case 'K':
					$value = $this->m_dataitem->getNumber();
				break;
				case '°C':
					$value = $this->m_dataitem->getNumber() - 273.15;
				break;
				case '°F':
					$value = ( $this->m_dataitem->getNumber() - 273.15 ) * 1.8 + 32;
				break;
				case '°R':
					$value = ( $this->m_dataitem->getNumber() ) * 1.8;
				break;
				// default: unit not supported
			}
		}
		if ( $value === false ) { // no valid output unit requested
			$value = $this->m_dataitem->getNumber();
			$this->m_unitin = 'K';
			$printunit = 'K';
		}

		$this->m_caption = '';
		if ( $this->m_outformat != '-u' ) { // -u is the format for displaying the unit only
			$this->m_caption .= ( ( $this->m_outformat != '-' ) && ( $this->m_outformat != '-n' ) ? smwfNumberFormat( $value ) : $value );
		}
		if ( ( $printunit !== '' ) && ( $this->m_outformat != '-n' ) ) { // -n is the format for displaying the number only
			if ( $this->m_outformat != '-u' ) {
				$this->m_caption .=  ( $this->m_outformat != '-' ? '&#160;' : ' ' );
			}
			$this->m_caption .= $printunit;
		}
	}

	/**
	 * Helper method to find the main representation of a certain unit.
	 */
	protected function getUnitID( $unit ) {
		/// TODO possibly localise some of those strings
		switch ( $unit ) {
			case '': case 'K': case 'Kelvin': case 'kelvin': case 'kelvins': return 'K';
			// There's a dedicated Unicode character (℃, U+2103) for degrees C.
			// Your font may or may not display it; do not be alarmed.
			case '°C': case '℃': case 'Celsius': case 'centigrade': return '°C';
			case '°F': case 'Fahrenheit': return '°F';
			case '°R': case 'Rankine': return '°R';
			default: return false;
		}
	}

	public function getUnitList() {
		return array( 'K', '°C', '°F', '°R' );
	}

	public function getUnit() {
		return 'K';
	}

}
