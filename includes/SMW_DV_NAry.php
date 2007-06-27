<?php

/**
 * This DV implements the handling of n-ary relations.
 */

require_once('SMW_DataValue.php');

class SMWNAryValue extends SMWDataValue {

	/**
	 * The original string as specified by a user, if provided to
	 * initialise this object. Otherwise a generated user-friendly string
	 * (no xsd). Wikitext.
	 */
	private $uvalue;

	/**
	 * XML Schema representation of single data value as stored in the DB.
	 * This value is important for processing, but might be completely different
	 * from the representations used for printout.
	 * Plain xml-compatible text. FALSE if value could not be determined.
	 */
	private $vxsd;

	/**
	 * The array of the data values within this container value
	 */
	private $m_values = array();

	/**
	 * variable for representing error messages
	 */
	private $m_error;

	/**
	 * constructor to create n-ary data value types and set their initial
	 * value appropriately.
	 */
	function SMWNAryValue($type, $value) {
		$values = explode(';', $value);
		$types = $type->getTypeValues();
		if (sizeof($types) > sizeof($values)) {
			$this->m_error = "[relation only supports " . (sizeof($types) . " parameters. However, you supplied " . sizeof($values)) . " parameters]"; /// TODO: internationalise
		} else if (sizeof($types) < sizeof($values)) {
			///TODO - use default values for types that were not explicitly specified?
		} else {
			// everything specified and passed correctly
			for ($i = 0; $i < sizeof($types); $i++) {
				$this->m_values[$i] = SMWDataValueFactory::newTypedValue($types[$i], $values[$i]);
			}
		}
	}

	public function setUserValue($value) {
		$this->uvalue = $value;
		/// TODO: acutally *set* the value, including $this->m_values!
	}

	public function setXSDValue($value, $unit) {
		// ignore parameter $unit
		$this->vxsd = $value;
		/// TODO: acutally *set* the value, including $this->m_values!
	}

	public function setAttribute($attribute) {
		/// TODO
	}
	
	public function setOutputFormat($formatstring) {
		/// TODO
	}

	public function getShortWikiText($linked = NULL) {
		if ($this->uvalue) {
			return $this->uvalue;
		}
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
		$result = '';
		$first = true;
		$second = true;
		foreach ($this->m_values as $value) {
			if ($first) {
				$first = false;
			} else {
				$result .= "; ";
			}
			$result .= $value->getXSDValue();
		}
		return $result;
	}

	public function getWikiValue() {
		///TODO
		return 'TODO';
	}

	public function getNumericValue() {
		return false;
	}

	public function getUnit() {
		return false;
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
		return md5($hash);
	}

	public function isValid() {
		return (count($this->m_values) > 0);
	}

	public function isNumeric() {
		return false; // the n-ary is clearly non numeric (n-ary values cannot be ordered by numbers)
	}
}

?>