<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue implements numerical datavalues, and supports optional
 * unit conversions. It parses and manages unit strings, since even plain
 * numbers may have (not further specified) units that are stored. However,
 * only subclasses implement full unit conversion by extending the methods
 * convertToMainUnit() and makeConversionValues().
 *
 * Units work as follows: a unit is a string, but many such strings might
 * refer to the same unit of measurement. There is always one string, that
 * canonically represents the unit, and we will call this version of writing
 * the unit the /unit id/. IDs for units are needed for tasks like duplicate
 * avoidance. If no conversion information is given, any unit is its own ID.
 * In any case, units are /normalised/, i.e. given a more standardised meaning
 * before being processed. All units, IDs or otherwise, should be suitable for
 * printout in wikitext, and main IDs should moreover be suitable for printout
 * in HTML.
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 *
 * @todo Wiki-HTML-conversion for unit strings must be revisited, as the current
 * solution might be unsafe.
 * @todo Respect desired output unit (relevant for queries).
 */
class SMWNumberValue extends SMWDataValue {

	protected $m_wikivalue = ''; // local language value, user input if given
	protected $m_value = ''; // numerical value, in $m_unit
	protected $m_unit = '';  // HTML-safe unit string, if any
	protected $m_unitin; // if set, specifies the originally given input unit in a standard writing
	protected $m_unitvalues; // array with entries unit=>value

	protected function parseUserValue($value) {
		$this->m_wikivalue = $value;
		$this->m_unitin = false;
		$this->m_unitvalues = false;

		wfLoadExtensionMessages('SemanticMediaWiki');

		// Parse to find value and unit
		$decseparator = wfMsgForContent('smw_decseparator');
		$kiloseparator = wfMsgForContent('smw_kiloseparator');

		$parts = preg_split('/([-+]?\s*\d+(?:\\' . $kiloseparator . '\d\d\d)*' .
		                      '(?:\\' . $decseparator . '\d+)?\s*(?:[eE][-+]?\d+)?)/u',
		                      trim(str_replace(array('&nbsp;','&thinsp;', ' '), '', $value)),
		                      2, PREG_SPLIT_DELIM_CAPTURE);

		if (count($parts) >= 2) {
			$numstring = str_replace($kiloseparator, '', preg_replace('/\s*/u', '', $parts[1])); // simplify
			if ($decseparator != '.') {
				$numstring = str_replace($decseparator, '.', $numstring);
			}
			list($this->m_value) = sscanf($numstring, "%f");
		}
		if (count($parts) >= 3) $this->m_unit = $this->normalizeUnit($parts[2]);

		if ( (count($parts) == 1) || ($numstring == '') ) { // no number found
			$this->addError(wfMsgForContent('smw_nofloat', $value));
		} elseif (is_infinite($this->m_value)) {
			 wfMsgForContent('smw_infinite', $value);
		}

		// Set caption
		if ($this->m_caption === false) {
			$this->m_caption = $value;
		}
		return true;
	}

	protected function parseDBkeys($args) {
		$this->m_value = $args[0];
		$this->m_unit = array_key_exists(1,$args)?$args[1]:'';
		$this->m_unitin = false;
		$this->m_stubdata = false;
		$this->makeUserValue();
		$this->m_unitvalues = false;
	}

	public function setOutputFormat($formatstring) {
		$oldformat = $this->m_outformat;
		$this->m_outformat = $formatstring;
		if ( ($formatstring != $oldformat) && $this->isValid() ) {
			// recompute conversion if outputformat is changed after initialisation
			$this->m_stubdata = array($this->m_value, $this->m_unit);
		}
	}

	public function getShortWikiText($linked = NULL) {
		$this->unstub();
		if (($linked === NULL) || ($linked === false) || ($this->m_outformat == '-') ) {
			return $this->m_caption;
		}
		$this->makeConversionValues();
		$tooltip = '';
		$i = 0;
		$sep = '';
		foreach ($this->m_unitvalues as $unit => $value) {
			if ( $unit != $this->m_unitin ) {
				$tooltip .= $sep . smwfNumberFormat($value);
				if ($unit != '') {
					$tooltip .= '&nbsp;' . $unit;
				}
				$sep = ' <br />';
				$i++;
				if ($i >= 5) { // limit number of printouts in tooltip
					break;
				}
			}
		}
		if ($tooltip != '') {
			SMWOutputs::requireHeadItem(SMW_HEADER_TOOLTIP);
			return '<span class="smwttinline">' . $this->m_caption . '<span class="smwttcontent">' . $tooltip . '</span></span>';
		} else {
			return $this->m_caption;
		}
	}

	public function getShortHTMLText($linker = NULL) {
		return $this->getShortWikiText($linker);
	}

	public function getLongWikiText($linked = NULL) {
		$this->unstub();
		if (!$this->isValid()) {
			return $this->getErrorText();
		} else {
			$this->makeConversionValues();
			$result = '';
			$i = 0;
			foreach ($this->m_unitvalues as $unit => $value) {
				if ($i == 1) {
					$result .= ' (';
				} elseif ($i > 1) {
					$result .= ', ';
				}
				$result .= ($this->m_outformat != '-'?smwfNumberFormat($value):$value);
				if ($unit != '') {
					$result .= '&nbsp;' . $unit;
				}
				$i++;
				if ($this->m_outformat == '-') { // no further conversions for plain output format
					break;
				}
			}
			if ($i > 1) {
				$result .= ')';
			}
			return $result;
		}
	}

	public function getLongHTMLText($linker = NULL) {
		return $this->getLongWikiText($linker);
	}

	public function getDBkeys() {
		$this->unstub();
		$this->convertToMainUnit();
		return array($this->m_value, $this->m_unit);
	}

	public function getWikiValue(){
		$this->unstub();
		return $this->m_wikivalue;
	}

	public function getNumericValue() {
		$this->unstub();
		$this->convertToMainUnit();
		return $this->m_value;
	}

	public function getUnit() {
		$values = $this->getDBkeys();
		return $values[1];
	}

	public function getHash() {
		$this->unstub();
		if ($this->isValid()) {
			$this->convertToMainUnit();
			return $this->m_value . $this->m_unit;
		} else {
			return implode("\t", $this->m_errors);
		}
	}

	protected function getServiceLinkParams() {
		$this->unstub();
		// Create links to mapping services based on a wiki-editable message. The parameters
		// available to the message are:
		// $1: string of numerical value in English punctuation
		// $2: string of integer version of value, in English punctuation
		// $3: string of unit (if any)
		return array((string)$this->m_value, (string)round($this->m_value), $this->m_unit);
	}

	public function isNumeric() {
		return true;
	}

	public function getExportData() {
		$this->unstub();
		if ($this->isValid()) {
			$lit = new SMWExpLiteral($this->m_value, $this, 'http://www.w3.org/2001/XMLSchema#double');
			return new SMWExpData($lit);
		} else {
			return NULL;
		}
	}

	/**
	 * Transform a (typically unit-) string into a normalised form,
	 * so that, e.g., "km²" and "km<sup>2</sup>" do not need to be
	 * distinguished.
	 */
	protected function normalizeUnit($unit) {
		$unit = str_replace(array('[[',']]'), '', trim($unit)); // allow simple links to be used inside annotations
		$unit = str_replace(array('²','<sup>2</sup>'), '&sup2;', $unit);
		$unit = str_replace(array('³','<sup>3</sup>'), '&sup3;', $unit);
		return smwfXMLContentEncode($unit);
	}

	/**
	 * Converts the current m_value and m_unit to the main unit, if possible.
	 * This means, it changes the fileds m_value and m_unit accordingly, and
	 * that it stores the ID of the originally given unit in $this->m_unitin.
	 * This should obviously not be done more than once, so it is advisable to
	 * first check if m_unitin is non-false. Also, it should be checked if the
	 * value is valid before trying to calculate with its contents.
	 *
	 * Overwritten by subclasses that support units.
	 */
	protected function convertToMainUnit() {
		$this->m_unitin = $this->m_unit; // just use unit as ID (no check needed, we can do this as often as desired)
	}

	/**
	 * This method creates an array of unit-value-pairs that should be
	 * printed. Units are the keys and should be canonical unit IDs.
	 * The result is stored in $this->m_unitvalues. Again, any class that
	 * requires effort for doing this should first check whether the array
	 * is already set (i.e. not false) before doing any work.
	 * Note that the values should be plain numbers. Output formatting is done
	 * later when needed.  Also, it should be checked if the value is valid
	 * before trying to calculate with its contents.
	 * This method also must call or implement convertToMainUnit().
	 *
	 * Overwritten by subclasses that support units.
	 */
	protected function makeConversionValues() {
		$this->convertToMainUnit();
		$this->m_unitvalues = array($this->m_unit => $this->m_value);
	}

	/**
	 * This method is used when no user input was given to find the best
	 * values for m_wikivalue, m_unitin, and m_caption. After conversion,
	 * these fields will look as if they were generated from user input,
	 * and convertToMainUnit() will have been called (if not, it would be
	 * blocked by the presence of m_unitin).
	 *
	 * Overwritten by subclasses that support units.
	 */
	protected function makeUserValue() {
		$this->convertToMainUnit();
		$this->m_caption = ($this->m_outformat != '-'?smwfNumberFormat($this->m_value):$this->m_value);
		if ($this->m_unit != '') {
			$this->m_caption .= '&nbsp;' . $this->m_unit;
		}
		$this->m_wikivalue = $this->m_caption;
		$this->m_unitin = $this->m_unit;
	}

	/**
	 * Return an array of major unit strings (ids only recommended) supported by
	 * this datavalue.
	 *
	 * Overwritten by subclasses that support units.
	 */
	public function getUnitList() {
		return array();
	}

}
