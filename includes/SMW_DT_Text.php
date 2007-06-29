<?php
/**
 * This file contains a typehandler for accepting potentially long texts.
 *
 * @author Markus Krötzsch
 */


/**
 * A typehandler for long texts. Values of this kind cannot be searched but
 * can be much longer than values of Type:String (255 bytes only).
 */
class SMWTextTypeHandler implements SMWTypeHandler {

	function getID() {
		return 'text';
	}

	function getXSDType() {
		return 'http://www.w3.org/2001/XMLSchema#string';
	}

	function getUnits() { //no units for strings
		return array('STDUNIT'=>false, 'ALLUNITS'=>array());
	}

	function processValue($value,&$datavalue) {
		if ($value!='') { //do not accept empty strings
			$xsdvalue = smwfXMLContentEncode($value);
			if (  mb_strlen($xsdvalue) >  255  ) {
				$shortvalue = mb_substr($value, 0, 127) . ' <span class="smwwarning">[&hellip;]</span> ' . mb_substr($value, mb_strlen($xsdvalue) - 127);
			} else {
				$shortvalue = $value;
			}
			$datavalue->setProcessedValues($value, $xsdvalue);
			$datavalue->setPrintoutString($shortvalue);
			// Note: no quick search or service links for this datatype
		} else {
			$datavalue->setError(wfMsgForContent('smw_emptystring'));
		}
		return true;
	}

	function processXSDValue($value,$unit,&$datavalue) {
		return $this->processValue($value,$datavalue);
	}

	function isNumeric() {
		return false;
	}
}

