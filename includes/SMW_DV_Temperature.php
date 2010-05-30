<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements unit support for measuring temperatures. This is
 * mostly an example implementation of how to realise custom unit types easily.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWTemperatureValue extends SMWNumberValue {

	/**
	 * Converts the current m_value and m_unit to the main unit, if possible.
	 * This means, it changes the fileds m_value and m_unit accordingly, and
	 * that it stores the ID of the originally given unit in $this->m_unitin.
	 * This should obviously not be done more than once, so it is advisable to
	 * first check if m_unitin is non-false. Also, it should be checked if the
	 * value is valid before trying to calculate with its contents.
	 */
	protected function convertToMainUnit() {
		if ( $this->m_unitin !== false ) return;
		if ( !$this->isValid() ) { // give up, avoid calculations with non-numbers
			$this->m_unitin = $this->m_unit;
			return;
		}

		// Find current ID and covert main values to Kelvin, if possible
		// Note: there is no error when unknown units are used.
		$this->m_unitin = $this->getUnitID( $this->m_unit );
		switch ( $this->m_unitin ) {
			case 'K':
				$this->m_unit = 'K';
			break;
			case '°C':
				$this->m_unit = 'K';
				$this->m_value = $this->m_value + 273.15;
			break;
			case '°F':
				$this->m_unit = 'K';
				$this->m_value = ( $this->m_value - 32 ) / 1.8 + 273.15;
			break;
			case '°R':
				$this->m_unit = 'K';
				$this->m_value = ( $this->m_value ) / 1.8;
			break;
			default: // unsupported unit
				// create error here, assuming that our temperature units should not be augmented by unknown units
				smwfLoadExtensionMessages( 'SemanticMediaWiki' );
				$this->addError( wfMsgForContent( 'smw_unsupportedunit', $this->m_unit ) );
				$this->m_unit = $this->m_unitin;
			break;
		}
	}

	/**
	 * This method creates an array of unit-value-pairs that should be
	 * printed. Units are the keys and should be canonical unit IDs.
	 * The result is stored in $this->m_unitvalues. Again, any class that
	 * requires effort for doing this should first check whether the array
	 * is already set (i.e. not false) before doing any work.
	 * Note that the values should be plain numbers. Output formatting is done
	 * later when needed. Also, it should be checked if the value is valid
	 * before trying to calculate with its contents.
	 * This method also must call or implement convertToMainUnit().
	 */
	protected function makeConversionValues() {
		if ( $this->m_unitvalues !== false ) return;
		$this->convertToMainUnit();
		$this->m_unitvalues = array( $this->m_unit => $this->m_value );
		if ( $this->isValid() && ( $this->m_unit == 'K' ) ) {
			$this->m_unitvalues['°C'] = $this->m_value - 273.15;
			$this->m_unitvalues['°F'] = ( $this->m_value - 273.15 ) * 1.8 + 32;
			$this->m_unitvalues['°R'] = ( $this->m_value ) * 1.8;
		}
	}

	/**
	 * This method is used when no user input was given to find the best
	 * values for m_wikivalue, m_unitin, and m_caption. After conversion,
	 * these fields will look as if they were generated from user input,
	 * and convertToMainUnit() will have been called (if not, it would be
	 * blocked by the presence of m_unitin).
	 */
	protected function makeUserValue() {
		$this->convertToMainUnit();

		$value = false;
		if ( ( $this->m_unit === 'K' ) && $this->m_outformat && ( $this->m_outformat != '-' ) ) { // try given output unit (only if conversion worked)
			$unit = $this->getUnitID( $this->normalizeUnit( $this->m_outformat ) );
			$printunit = $this->m_outformat;
			switch ( $unit ) {
				case 'K':
					$value = $this->m_value;
				break; // nothing to do
				case '°C':
					$value = $this->m_value - 273.15;
				break;
				case '°F':
					$value = ( $this->m_value - 273.15 ) * 1.8 + 32;
				break;
				case '°R':
					$value = ( $this->m_value ) * 1.8;
				break;
				// default: unit not supported
			}
		}
		if ( $value === false ) { // finally fallback to current value
			$value = $this->m_value;
			$unit = $this->m_unit;
			$printunit = $unit;
		}

		$this->m_caption = smwfNumberFormat( $value );
		if ( $printunit != '' ) {
			$this->m_caption .= '&#160;' . $printunit;
		}
		$this->m_wikivalue = $this->m_caption;
		$this->m_unitin = $unit;
	}



	/**
	 * Helper method to find the main representation of a certain unit.
	 */
	protected function getUnitID( $unit ) {
		/// TODO possibly localise some of those strings
		switch ( $unit ) {
			case '': case 'K': case 'Kelvin': case 'kelvin': case 'kelvins':
				return 'K';
			// There's a dedicated Unicode character (℃, U+2103) for degrees C.
			// Your font may or may not display it; do not be alarmed.
			case '°C': case '℃': case 'Celsius': case 'centigrade':
				return '°C';
			break;
			case '°F': case 'Fahrenheit':
				return '°F';
			break;
			case '°R': case 'Rankine':
				return '°R';
			break;
			default: // unsupported unit
				return $unit;
			break;
		}
	}

	/**
	 * Return an array of major unit strings (ids only recommended) supported by
	 * this datavalue.
	 */
	public function getUnitList() {
		return array( 'K', '°C', '°F', '°R' );
	}

}
