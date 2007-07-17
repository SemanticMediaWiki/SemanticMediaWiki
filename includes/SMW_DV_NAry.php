<?php

/**
 * The SMWDataValue in this file implements the handling of n-ary relations.
 * @author Jörg Heizmann
 * @author Markus Krötzsch
 */

require_once('SMW_DataValue.php');

class SMWNAryValue extends SMWDataValue {

	private $m_scount = 0;

	/**
	 * The array of the data values within this container value
	 */
	private $m_values = array();

	/**
	 * typevalue as we received them when datafactory called us
	 */
	private $m_type;

	/**
	 * Set type array. Must be done before setting any values.
	 */
	function setType($type) {
		$this->m_type = $type;
		$this->m_count = count($this->m_type->getTypeLabels());
		$this->m_values = array(); // careful: do not iterate to m_count if DV is not valid!
	}

	private function parseValues($commaValues) {
		return preg_split('/[\s]*;[\s]*/', trim($commaValues), $this->m_count);
	}

	protected function parseUserValue($value) {
		$this->m_values = array();
		if ($value == '') {
			$this->addError('No values specified.');
			return;
		}

		$types = $this->m_type->getTypeValues();
		$values = $this->parseValues($value);
		$vi = 0; // index in value array
		$empty = true;
		for ($i = 0; $i < $this->m_count; $i++) { // iterate over slots
			if ( (count($values) > $vi) && 
			     ( ($values[$vi] == '') || ($values[$vi] == '?') ) ) { // explicit omission
				$this->m_values[$i] = NULL;
				$vi++;
			} elseif (count($values) > $vi) { // some values left, try next slot
				$dv = SMWDataValueFactory::newTypeObjectValue($types[$i], $values[$vi]);
				if ($dv->isValid()) { // valid DV: keep
					$this->m_values[$i] = $dv;
					$vi++;
					$empty = false;
				} elseif ( (count($values)-$vi) == (count($types)-$i) ) { 
					// too many errors: keep this one to have enough slots left
					$this->m_values[$i] = $dv;
					$vi++;
				} else { // assume implicit omission, reset to NULL
					$this->m_values[$i] = NULL;
				}
			} else { // fill rest with NULLs
				$this->m_values[$i] = NULL;
			}
		}
		if ($empty) {
			$this->addError('No values specified.');
		}
	}

	protected function parseXSDValue($value, $unit) {
		$types = $this->m_type->getTypeValues();
		// Note: we can always assume this to be the form that getXSDValue returns,
		// unless it is complete junk. So be strict in parsing.
		$values = explode(';', $value, $this->m_count);
		$units = explode(';', $unit, $this->m_count);

		if (count($values) != $this->m_count) {
			$this->addError('This is not an nary value.');
			return;
		}

		$this->m_values = array();
		for ($i = 0; $i < $this->m_count; $i++) {
			if ($values[$i] == '') {
				$this->m_values[$i] = NULL;
			} else {
				$this->m_values[$i] = SMWDataValueFactory::newTypeObjectValue($types[$i], $values[$i]);
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
		return $this->makeOutputText(0, $linked);
	}

	public function getShortHTMLText($linker = NULL) {
		if ($this->m_caption !== false) {
			return $this->m_caption;
		}
		return $this->makeOutputText(1, $linker);
	}

	public function getLongWikiText($linked = NULL) {
		return $this->makeOutputText(2, $linked);
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->makeOutputText(3, $linker);
	}

	private function makeOutputText($type = 0, $linker = NULL) {
		if (!$this->isValid()) {
			return ( ($type == 0)||($type == 1) )? '' : $this->getErrorText();
		}
		$result = '';
		for ($i = 0; $i < $this->m_count; $i++) {
			if ($i == 1) {
				$result .= ' (';
			} elseif ($i > 1) {
				$result .= ", ";
			}
			if ($this->m_values[$i] !== NULL) {
				$result .= $this->makeValueOutputText($type, $i, $linker);
			} else {
				$result .= '?';
			}
			if ($i == sizeof($this->m_values) - 1) {
				$result .= ')';
			}
		}
		return $result;
	}
	
	private function makeValueOutputText($type, $index, $linker) {
		switch ($type) {
			case 0: return $this->m_values[$index]->getShortWikiText($linker);
			case 1: return $this->m_values[$index]->getShortHTMLText($linker);
			case 2: return $this->m_values[$index]->getLongWikiText($linker);
			case 3: return $this->m_values[$index]->getLongHTMLText($linker);
		}
	}

	public function getXSDValue() {
		$first = true;
		$result = '';
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else {
				$result .= ';';
			}
			if ($value !== NULL) {
				$result .= $value->getXSDValue();
			}
		}
		return $result;
	}

	public function getWikiValue() {
		$result = '';
		$first = true;
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else {
				$result .= "; ";
			}
			if ($value !== NULL) {
				$result .= $value->getWikiValue();
			} else {
				$result .= "?";
			}
		}
		return $result;
	}

	public function getNumericValue() {
		return false;
	}

	public function getUnit() {
		$first = true;
		$result = '';
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else {
				$result .= ';';
			}
			if ($value !== NULL) {
				$result .= $value->Unit();
			}
		}
		return $result;
	}

	public function getHash() {
		$first = true;
		$result = '';
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else {
				$result .= ' - ';
			}
			if ($value !== NULL) {
				$result .= str_replace('-', '--', $value->getHash());
			}
		}
		return $result;
	}

	public function isNumeric() {
		return false; // the n-ary is clearly non numeric (n-ary values cannot be ordered by numbers)
	}

////// Custom functions for n-ary attributes

	public function getDVTypeIDs() {
		return implode(';', $this->m_type->getTypeLabels());
	}

	public function getType() {
		return $this->m_type;
	}

	public function getDVs() {
		return $this->isValid() ? $this->m_values : NULL;
	}

	/**
	 * Directly set the values to the given array of values. The given values
	 * should correspond to the types and arity of the nary container, with 
	 * NULL as an indication for omitted values.
	 */
	public function setDVs($datavalues) {
		$typelabels = $this->m_type->getTypeLabels();
		for ($i = 0; $i < $this->m_count; $i++) {
			if ( ($i < count($datavalues) ) && ($datavalues[$i] !== NULL) ) {
			    //&& ($datavalues[$i]->getTypeID() == SMWTypesValue::findTypeID($typelabels[$i])) ) {
			    ///TODO: is the above typcheck required, or can we assume responsible callers?
				$this->m_values[$i] = $datavalues[$i];
			} else {
					$this->m_values[$i] = NULL;
			}
		}
	}

}

