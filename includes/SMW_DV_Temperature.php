<?php

/**
 * This datavalue implements unit support for measuring temperatures. This is
 * mostly an example implementation of how to realise custom unit types easily.
 *
 * @author Markus Krötzsch
 * @note AUTOLOADED
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
		if ($this->m_unitin !== false) return;
		if (!$this->isValid()) { // give up, avoid calculations with non-numbers
			$this->m_unitin = $this->m_unit;
			return;
		}

		// Find current ID and covert main values to Kelvin, if possible
		// Note: there is no error when unknown units are used.
		/// TODO possibly localise some of those strings
		switch ( $this->m_unit ) {
			case '': case 'K': case 'Kelvin': case 'kelvin': case 'kelvins':
				$this->m_unitin = 'K';
			break;
			// There's a dedicated Unicode character (℃, U+2103) for degrees C.
			// Your font may or may not display it; do not be alarmed.
			case '°C': case '℃': case 'Celsius': case 'centigrade':
				$this->m_unitin = '°C';
				$this->m_unit = 'K';
				$this->m_value = $this->m_value + 273.15;
			break;
			case '°F': case 'Fahrenheit':
				$this->m_unitin ='°F';
				$this->m_unit = 'K';
				$this->m_value = ($this->m_value - 32) /1.8 + 273.15;
			break;
			default: //unsupported unit
				$this->m_unitin = $this->m_unit;
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
		if ($this->m_unitvalues !== false) return;
		$this->convertToMainUnit();
		$this->m_unitvalues = array($this->m_unit => $this->m_value);
		if ($this->isValid() && ($this->m_unit == 'K')) {
			$this->m_unitvalues['°C'] = $this->m_value - 273.15;
			$this->m_unitvalues['°F'] = ($this->m_value - 273.15) * 1.8 + 32;
		}
	}

}
