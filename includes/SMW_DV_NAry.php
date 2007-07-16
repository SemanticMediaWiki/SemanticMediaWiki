<?php

/**
 * This DV implements the handling of n-ary relations.
 */

require_once('SMW_DataValue.php');

class SMWNAryValue extends SMWDataValue {

	/**
	 * The array of the data values within this container value
	 */
	private $m_values = Array();

	/**
	 * Is this n-ary datavalue valid?
	 */
	private $isValid;

	/**
	 * types as we received them when datafactory called us
	 */
	private $m_type = Array();

	/**
	 * Set type array. Must be done before setting any values.
	 */
	function setType($type) {
		$this->m_type = $type;
	}

	private function parseValues($commaValues) {
		return preg_split('/[\s]*;[\s]*/', $commaValues, sizeof($this->m_type->getTypeLabels()));
	}

	protected function parseUserValue($value) {
		$types = $this->m_type->getTypeValues();
		if ($value!='') {
			$values = $this->parseValues($value);
			// check if all values were specified
			if (sizeof($values) < sizeof($types)) {
				$valueindex = 0;
				// less values specified -> test for closest matchings
				for ($i = 0; $i < sizeof($types); $i++) {
					// check if enough slots available -> if not set isValid to false...
					if ((sizeof($values)-$valueindex) > ((sizeof($types)-$i))) {
						$this->isValid = false;
						$this->m_values[$i] = SMWDataValueFactory::newTypeObjectValue($types[$i], false);
					} else {
						$this->m_values[$i] = SMWDataValueFactory::newTypeObjectValue($types[$i], $values[$valueindex]);
						if ($this->m_values[$i]->isValid()) {
							$valueindex++;
						} else {
							/// TODO: Remove previously set (user)value from container!!!
						}
					}
				}
			} else {
			// 	everything specified and passed correctly
				for ($i = 0; $i < sizeof($types); $i++) {
					$this->m_values[$i] = SMWDataValueFactory::newTypeObjectValue($types[$i], $values[$i]);
				}
				$this->isValid = true;
			}
		} else {
			// no values specified -> create empty DVs
			for ($i = 0; $i < sizeof($types); $i++) {
				$this->m_values[$i] = SMWDataValueFactory::newTypeObjectValue($types[$i], false);
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
			$valueindex = 0;
			// less values specified -> test for closest matchings
			for ($i = 0; $i < sizeof($types); $i++) {
				$this->m_values[$i] = SMWDataValueFactory::newTypeObjectValue($types[$i]);
				// check if enough slots available -> if not set isValid to false...
				if ((sizeof($values)-$valueindex) > ((sizeof($types)-$i))) {
					$this->isValid = false;
				} else {
					// is value valid?
					if ($this->m_values[$i]) {
						$this->m_values[$i]->setXSDValue($values[$i], (is_array($unit)? $units[$valueindex] : null));
						if ($this->m_values[$i]->isValid()) {
							$valueindex++;
						} else {
							/// TODO!
						}
					}
				}
			}
		} else {
			// everything specified and passed correctly - set XSDValue of DV containers.
			for ($i = 0; $i < sizeof($types); $i++) {
				$this->m_values[$i] = SMWDataValueFactory::newTypeObjectValue($types[$i]);
				$this->m_values[$i]->setXSDValue($values[$i], (is_array($unit)? $units[$i] : null));
			}
		}
	}

	public function setOutputFormat($formatstring) {
		/// TODO
	}

	public function getShortWikiText($linked = NULL) {
		if ($this->m_caption !== false) {
			return $this->m_caption;
		}
		/// TODO: beautify with (...) like LongText
		$result = '';
		$first = true;
		foreach ($this->m_values as $value) {
			if ($value->getShortWikiText($linked)) {
				if ($first) {
					$first = false;
				} else {
					$result .= ", ";
				}
				$result .= $value->getShortWikiText($linked);
			}
		}
		return $result;
	}

	public function getShortHTMLText($linker = NULL) {
		if ($this->m_caption !== false) {
			return $this->m_caption;
		}
		/// TODO: beautify with (...) like LongText
		$result = '';
		$first = true;
		foreach ($this->m_values as $value) {
			if ($value->getShortHTMLText($linker)) {
				if ($first) {
					$first = false;
				} else {
					$result .= ", ";
				}
				$result .= $value->getShortHTMLText($linker);
			}
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
			$result .= $value->getLongHTMLText($linker);
		}
		return $result;
	}

	public function getXSDValue() {
		$xsdvals = Array();
		for ($i = 0; $i < sizeof($this->m_type->getTypeLabels()); $i++) {
			$xsdvals[$i] = $this->m_values[$i]->getXSDValue();
		}
		return implode(';', $xsdvals);
	}

	public function getWikiValue() {
		$result = '';
		$first = true;
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else {
				$result .= ";";
			}
			$result .= $value->getWikiValue();
		}
		return $result;
	}

	public function getNumericValue() {
		return false;
	}

	public function getUnit() {
		$units = Array();
		for ($i = 0; $i < sizeof($this->m_type->getTypeLabels()); $i++) {
			$units[$i] = $this->m_values[$i]->getUnit();
		}
		return implode(';', $units);
	}

	public function getHash() {
		$hash = '';
		foreach ($this->m_values as $value) {
			$hash .= $value->getHash();
			/// FIXME: this is wrong (different value combinations yield same hash)
		}
		return $hash;
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

