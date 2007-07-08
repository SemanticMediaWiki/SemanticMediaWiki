<?php

/**
 * This DV implements the handling of n-ary relations.
 */

require_once('SMW_DataValue.php');

class SMWNAryValue extends SMWDataValue {

	/**
	 * The array of the data values within this container value
	 */
	private $m_values = array();

	/**
	 * variable for representing error messages
	 */
	private $m_error;

	/**
	 * types as we received them when datafactory called us
	 */
	private $m_type = array();

	/**
	 * constructor to create n-ary data value types and set their initial
	 * value appropriately.
	 *
	 * TODO: move all setup of values to parseUserValue and only call setUserValue here.
	 */
	function SMWNAryValue($type, $value,$caption=false) {
		$this->m_type = $type;

		$types = $type->getTypeValues();
		$values = $this->parseValues($value);
		if (sizeof($values) < sizeof($types)) {
			///TODO - use default values for types that were not explicitly specified?

		} else {
			// everything specified and passed correctly
			for ($i = 0; $i < sizeof($types); $i++) {
				$this->m_values[$i] = SMWDataValueFactory::newTypedValue($types[$i], $values[$i]);
			}
		}
	}

	private function parseValues($commaValues) {
		return preg_split('/[\s]*;[\s]*/', $commaValues, sizeof($this->m_type->getTypeLabels()));
	}

	//
	// Methods derived from abstract class
	//

	protected function parseUserValue($value) {
		// get DVtypes
		$types = $this->m_type->getTypeValues();
		// get values supplied by user
		$values = $this->parseValues($value);
		// create user specified DVs
		if (sizeof($values) < sizeof($types)) {
			/// TODO: Actually handle this case!
		} else {
			// everything specified and passed correctly - build DV containers.
			for ($i = 0; $i < sizeof($types); $i++) {
				$this->m_values[$i] = SMWDataValueFactory::newTypedValue($types[$i], $values[$i]);
			}
		}
	}

	protected function parseXSDValue($value, $unit) {
		// get DVtypes
		$types = $this->m_type->getTypeValues();
		// get values supplied by user
		$values = $this->parseValues($value);
		$units = $this->parseValues($unit);

		// create user specified DVs
		if (sizeof($values) < sizeof($types)) {
			/// TODO: Actually handle this case!
		} else {
			// everything specified and passed correctly - set XSDValue of DV containers.
			for ($i = 0; $i < sizeof($types); $i++) {
				$this->m_values[$i]->setXSDValue($values[$i], (is_array($unit)? $units[$i] : null));
			}
		}
	}

	public function setOutputFormat($formatstring) {
		/// TODO
	}

	public function getShortWikiText($linked = NULL) {
		$result = '';
		$first = true;
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else {
				$result .= ", ";
			}
			$result .= $value->getShortWikiText($linked);
		}
		return $result;
	}

	public function getShortHTMLText($linker = NULL) {
		/// TODO: beautify with (...) like WikiText
		$result = '';
		$first = true;
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else {
				$result .= ", ";
			}
			$result .= $value->getShortHTMLText($linker);
		}
		return $result;
	}

	public function getLongWikiText($linked = NULL) {
		$result = '';
		$first = true;
		$second = true;
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else if ($second) {
				$result .= ' (';
				$second = false;
			}
			else {
				$result .= ", ";
			}
			$result .= $value->getLongWikiText($linked);
		}
		return $second ? $result : $result .= ')';
	}

	public function getLongHTMLText($linker = NULL) {
		/// TODO: beautify with (...) like WikiText
		$result = '';
		$first = true;
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else {
				$result .= ", ";
			}
			$result .= $type->getLongHTMLText($linker);
		}
		return $result;
	}

	public function getXSDValue() {
		$xsdvals = array();
		for ($i = 0; $i < sizeof($this->m_type->getTypeLabels()); $i++) {
			$xsdvals[$i] = $this->m_values[$i]->getXSDValue();
		}
		return implode(';', $xsdvals);
	}

	public function getWikiValue() {
		///TODO
		return 'TODO';
	}

	public function getNumericValue() {
		return false;
	}

	public function getUnit() {
		return ''; // empty unit
	}

	public function getError() {
		return $this->m_error;
	}

	public function getTypeID() {
		return 'nary';
	}

	public function getInfolinks() {
		return array();
	}

	public function getHash() {
		$hash = '';
		foreach ($this->m_values as $value) {
			$hash .= $value->getHash();
			/// FIXME: this is wrong (different value combinations yield same hash)
		}
		return $hash;
	}

	public function isValid() {
		return (count($this->m_values) > 0);
	}

	public function isNumeric() {
		return false; // the n-ary is clearly non numeric (n-ary values cannot be ordered by numbers)
	}

	//
	// custom functions for n-ary attributes
	//

	public function getDVTypeIDs() {
		return implode(';', $this->m_type->getTypeLabels());
	}

	public function getDVs() {
		return $this->isValid() ? $this->m_values : null;
	}

}

