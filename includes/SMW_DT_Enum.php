<?php
/**
 * Typehandler class for enums.
 *
 * @author S Page
 */

/**
 * Class for managing enum types. Pretty simple,
 * just strings with check against possible values.
 */
class SMWEnumTypeHandler implements SMWTypeHandler {

	function getID() {
		return '_enu';
	}

	// Can't represent any better way than as a string
	function getXSDType() {
		return 'http://www.w3.org/2001/XMLSchema#string';
	}

	function getUnits() { //no units for enums
		return array('STDUNIT'=>false, 'ALLUNITS'=>array());
	}

	function processValue($value,&$datavalue) {
		if ($value!='') { //do not accept empty strings
			$xsdvalue = smwfXMLContentEncode($value);
			// See if string is one of the possible values.
			$possible_values = $datavalue->getPossibleValues();
			if (count($possible_values) < 1) {
				$datavalue->setError(wfMsgForContent('smw_nopossiblevalues'));
			} else {
				// TODO: I just need the sequence.
				// Maybe keys should be possible values and values should be offset?
				$offset = array_search($value, $possible_values);
				if ($offset === false) {
					$datavalue->setError(wfMsgForContent('smw_notinenum', $value, implode(', ', $possible_values)));
				} else {
					// We use a 1-based offset.
					$offset++;
					$datavalue->setProcessedValues($value, $xsdvalue, $offset);
					$datavalue->setPrintoutString($value);
					$datavalue->addQuicksearchLink();
					$datavalue->addServiceLinks(urlencode($value));
				}
			}
		} else {
			$datavalue->setError(wfMsgForContent('smw_emptystring'));
		}
		return true;
	}

	function processXSDValue($value,$unit,&$datavalue) {
		return $this->processValue($value,$datavalue);
	}

	function isNumeric() {
		return true;
	}
}

