<?php
/**
 * Typehandler class for booleans.
 *
 * @author S Page
 */

/**
 * Class for managing boolean types. Pretty simple, the only trick is localizing true/false.
 */
class SMWBooleanTypeHandler implements SMWTypeHandler {

	function getID() {
		return '_boo';
	}

	function getXSDType() {
		return 'http://www.w3.org/2001/XMLSchema#boolean';
	}

	function getUnits() { //no units for booleans
		return array('STDUNIT'=>false, 'ALLUNITS'=>array());
	}

	function processValue($value, &$datavalue) {
		$xsdvalue = -1;	// initialize to failure
		// See http://en.wikipedia.org/wiki/Boolean_datatype
		// TODO: To save code, trim values before they get to processValue().

		$vlc = strtolower(trim($value));
		if ($vlc!='') { //do not accept empty strings
			// Look for universal true/false and 1/0,
			// then allow language-specific names.
			if (in_array($vlc, array('true', '1'), TRUE)) {
				$xsdvalue = 'true';
			} elseif (in_array($vlc, array('false', '0'), TRUE)) {
				$xsdvalue = 'false';
			} elseif (in_array($vlc, explode(',', wfMsgForContent('smw_true_words')), TRUE)) {
				$xsdvalue = 'true';
			} elseif (in_array($vlc, explode(',', wfMsgForContent('smw_false_words')), TRUE)) {
				$xsdvalue = 'false';
			} else {
				$datavalue->setError(wfMsgForContent('smw_noboolean', $value));
			}
		} else {
			$datavalue->setError(wfMsgForContent('smw_emptystring'));
		}

		if ($xsdvalue === 'true' || $xsdvalue === 'false') {
			// Store numeric 1 or 0 as number.
			$datavalue->setProcessedValues($value, $xsdvalue, $xsdvalue === 'true' ? 1 : 0);
			// For a boolean, "units" is really a format from an inline query
			// rather than the units of a float.
			$desiredUnits = $datavalue->getDesiredUnits();
			// Determine the user-visible string.
			if (count($desiredUnits) ==0) {
				$datavalue->setPrintoutString($xsdvalue);
			} else {
				// The units is a string for 'true', a comma, and a string for 'false'.
				foreach ($desiredUnits as $wantedFormat) {
					list($true_text, $false_text) = explode(',', $wantedFormat, 2);
					$datavalue->setPrintoutString($xsdvalue === 'true' ? $true_text : $false_text);
				}
			}
			$datavalue->addQuicksearchLink();
		}
		return true;
	}

	function processXSDValue($value,$unit,&$datavalue) {
		return $this->processValue($value,$datavalue);
	}

	// true/false can be sorted
	function isNumeric() {
		return true;
	}
}

