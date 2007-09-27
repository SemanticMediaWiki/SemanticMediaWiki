<?php
/**
 * This file contains the basic float type and unit conversion methods.
 */

global $smwgContLang;

/**
 * Class for managing floating point types. Parses floating point
 * number strings, supports unit conversion in subclasses, and
 * generates appropriate error messages on failure.
 * This class implements some features that floating-point numbers 
 * don't need, so that child classes can handle units easily.
 *
 * New float datatypes that support units can be implemented in 
 * two ways:
 * 1.  For linear conversions between units, make a new wiki page to 
 *     specify this type.
 * 2.  For other unit conversions, subclass SMWFloatTypeHandler and
 *     adjust its core methods as shown in SMWTemperatureTypeHandler.
 * 
 * @author Markus Krötzsch, skierpage
 */
class SMWFloatTypeHandler implements SMWTypeHandler{

	/**#@+
	/**
	 * ID string for the datatype object
	 * @access protected
	 */
	protected $typeid = '_flt';
	/**
	 * Primary unit for this type, false if none.
	 * @see getPrimaryUnit()
	 * @access protected
	 */
	protected $primaryUnit = false;
	/**
	 * Array of other units to display.
	 * @see getOtherDisplayUnits
	 * @access protected
	 */
	protected $otherDisplayUnits = array();
	/**#@-*/
	
	/**
	 * Return $name's typeid.  SMW stores the typeid in the attribute 
	 * table and uses it to organize listings.
	 * @access public
	 */
	function getID() {
		return $this->typeid;
	}

	/**
	 * Return the XML Schema Description type for this item.
	 * @public
	 */
	function getXSDType() {
		return 'http://www.w3.org/2001/XMLSchema#float';
	}

	/**@
	 * This returns the primary unit. This is the unit into which SMW 
	 * converts values before storing in the attribute table. If not 
	 * overridden by specifying desired units, it also is the first 
	 * value that a datavalue displays.
	 * @return string for (trailing) primary unit; false if no unit.
	 * @public
	 */
	function getPrimaryUnit() {
		return $this->primaryUnit;
	}

	/**@
	 * This returns additional display units. SMW displays the value 
	 * converted into these additional units in the factbox and 
	 * tooltip. The purpose of this field is also to provide an 
	 * overview of the typical units that are supported for some 
	 * datatype.
	 * @public
	 */
	function getOtherDisplayUnits() {
		return $this->otherDisplayUnits;
	}

	function isNumeric() {
		return TRUE;
	}

	/**@
	 * For historical reasons, this returns an array with keys 
	 * 'STDUNIT' and 'ALLUNITS'. The implementation assembles this 
	 * 'from getPrimaryUnit() and  getOtherDisplayUnits().
	 * @see getPrimaryUnit()
	 * @see getOtherDisplayUnits()
	 * @return array with keys 'STDUNIT' and 'ALLUNITS'.
	 * @deprecated version - Jul 4, 2006
	 * TODO: Change interface to eliminate this.
	 * @public
	 */
	function getUnits() {
		return array('STDUNIT'=>$this->getPrimaryUnit(), 'ALLUNITS'=>$this->getOtherDisplayUnits() );
	}


    /**
	 * This common datatype method transforms the user-provided value of an
	 * attribute into several output strings (one for XML,
	 * one for printout, etc.) and reports parsing errors if
	 * the value is not valid for the given data type.
	 *
	 * Upon return from this method, the datatype should have values for
	 *  vuser, vstring, vxsd, vnum, unit (these currently set with setProcessedValues), input, and possibly tooltip
	 * unless error is set.  See docs in SMW_Datatype.php.
	 * TODO: replace with less monolithic method, this does more work than clients need.
	 *
	 * @access public
	 */
	function processValue($v,&$datavalue) {
		$result=array();
		$desiredUnits = $datavalue->getDesiredUnits();

		// TODO: Also, this isn't ?? multibyte-safe. (S)
		//  : Why not? (mak)
		$val = trim($v);

		list($preNum, $numIn, $unitIn, $errStr) = $this->parseValue($val);
		if ($errStr !== '') { // not a number
			$datavalue->setError($errStr);
			return;
		}
		$unitIn = $this->normalizeUnit($unitIn);

		// Find out whether the unit is supported and get ID:
		list($uid, $errStr) = $this->getUnitID($preNum, $unitIn);
		if ( ($errStr !== '') || ($this->primaryUnit == false) ) { // given unit not supported, return
			$doUnitProcessing = false;
			// TODO: Perhaps class SMWDataValue should have setWarning() to achieve the following output
			$datavalue->setProcessedValues($v, $numIn, $numIn, $unitIn);
			if ($errStr !== '') {
				$errStr = ' <span class="smwwarning">(' . $errStr . ')</span>';
			}
			$datavalue->setPrintoutString($this->appendUnit(smwfNumberFormat($numIn), $unitIn) . $errStr);
			$datavalue->addQuicksearchLink();
			$datavalue->addServiceLinks($numIn);
			return;
		}

		// Compute primary representation for the input value:
		list($puid, ) = $this->getUnitID('',$this->primaryUnit);
		if ( $puid == $uid ) {
			$primaryNum = $numIn;
		} else {
			list($primaryNum, $errStr) = $this->convertQty($numIn, true, $unitIn);
			if ($errStr !== '') { // conversion failed, return input
				$datavalue->setProcessedValues($v, $numIn, $numIn, $unitIn);
				$datavalue->setPrintoutString($this->appendUnit(smwfNumberFormat($numIn), $unitIn) . ' <span class="smwwarning">(' . $errStr . ')</span>');
				$datavalue->addQuicksearchLink();
				$datavalue->addServiceLinks($numIn);
				return;
			} elseif (is_infinite($primaryNum)) {
				$datavalue->setProcessedValues($v, $numIn, $numIn, $unitIn);
				$datavalue->setPrintoutString($this->appendUnit(smwfNumberFormat($numIn), $unitIn) . ' <span class="smwwarning">(' . wfMsgForContent('smw_infinite_unit', $this->primaryUnit) . ')</span>');
				$datavalue->addQuicksearchLink();
				$datavalue->addServiceLinks($numIn);
				return;
			}
		}
		$datavalue->setInput($uid);

		// Set main values:
		$datavalue->setProcessedValues($v, 
		    $primaryNum, $primaryNum, $this->primaryUnit);

		// Find units desired for display:
		if (count($desiredUnits) > 0) { // use given units for display
			$displayUnits = $desiredUnits;
			// Note: we don't display any unit that the user did not
			// request (not even primaryUnit); users need full control
		} else { // use primaryUnit and otherDisplayUnits
			$displayUnits = $this->otherDisplayUnits;
			array_unshift($displayUnits, $this->primaryUnit);
		}
		// Print all wanted units (even if some of them would be equivalent -- we obey the user's wish:
		// Note: if none of the requested units is supported, the result will be rather empty and the
		// datavalue's method getString() will resort to returning the user supplied string (i.e. our 
		// input $v).
		foreach ($displayUnits as $wantedUnit) {
			$errStr = '';
			$wantedUnit = $this->normalizeUnit($wantedUnit);
			list($wuid, $errStr) = $this->getUnitID('',$wantedUnit);
			if ( $errStr !== '' ) { // unsupported unit
				continue;
			}

			list($numOut, $errStr) = $this->convertQty($primaryNum, false, $wantedUnit);
			// Store string representations:
			if ($errStr == '') {
				if (is_infinite($numOut)) {
					$datavalue->setPrintoutString('<span class="smwwarning">' . wfMsgForContent('smw_infinite_unit', $wantedUnit) . '</span>', $wuid);
				} else {
					$datavalue->setPrintoutString($this->appendUnit(smwfNumberFormat($numOut), $wantedUnit), $wuid);
				}
			} else {
				// There is currently no way to report this error.
				// TODO: could accumulate the error in $errStr in a warning string.
			}
		}

		$datavalue->addQuicksearchLink();
		$datavalue->addServiceLinks($primaryNum);
		return;
	}

	/**
	 * This method parses the value in the XSD form that was
	 * generated by parsing some user input. It is needed since
	 * the XSD form must be compatible to XML, and thus does not
	 * respect the internationalization settings. E.g. the German
	 * input value "1,234" is translated into XSD "1.234" which,
	 * if reparsed as a user input would be misinterpreted as 1234.
	 *
	 * @access public
	 */
	function processXSDValue($value,$unit,&$datavalue) {
		//insert the local decimal separator and append unit
		return $this->processValue($this->appendUnit(str_replace('.',wfMsgForContent('smw_decseparator'),$value),$unit), $datavalue);
	}

    /**
	 * This method takes the pre- and postfixes provided by the user
	 * around some number, and returns a suitable ID for that unit.
	 * The idea is that equivalent units (e.g. m and meter) should 
	 * have the same ID, so that they can be treated similarly. Even
	 * unsupported units should have some unique ID, as e.g. the unit 
	 * string itself.
	 * This function also specifies whether a given unit is supported 
	 * or not by returning a non-empty error in unsupported cases.
	 * @param prefixIn prefix string (possibly '') to the value.
	 * @param unitIn trailing unit string (possibly '') after value.
	 * @return array with ID and error string ('' if successful).
	 * @public
	 */
	function getUnitID($prefixIn, $unitIn) {
		$errStr = '';
		// It's OK to have a trailing unit, Type:float just stores it.
		if ($prefixIn !== '') {
			// Could concatenate the prefix and the unit?
			$errStr = wfMsgForContent('smw_unsupportedprefix', $prefixIn);
		}
		return array($unitIn, $errStr);
	}

	/**
	 * This method converts a value to OR from the main (SI) 
	 * representation for the type.
	 * @param numIn incoming value
	 * @param toPrimary determines the conversion direction.
	 *   - false: convert from $unit to primary unit
	 *   - true: convert to $unit from primary unit
	 * @param unit Unit from/to which conversion is required
	 * @return array with result number and error ('' if successful).
	 * @private
	 */
	function convertQty($numIn, $toPrimary, $unit) {
		$errStr = '';
		if ($unit != '') {
			$errStr = wfMsgForContent('smw_unsupportedunit',$unit);
		}
		return array($numIn, $errStr);
	}

	/**@
	 * Parse a floating point value, possibly including prefix and 
	 * unit.
	 * @param value string
	 * @return array of prefix, float, postfix ("unit"), error string
	 */
	function parseValue($v) {
		$preNum = '';
		$num = null;  // This indicates error.
		$unit = '';

		$decseparator = wfMsgForContent('smw_decseparator');
		$kiloseparator = wfMsgForContent('smw_kiloseparator');

		// First, split off number from the rest.
		// Number is, e.g. -12,347,421.55e6
		// Note the separators might be a magic regexp value like '.', so have to escape them with backslash.
		// This rejects .1 , it needs a leading 0.
		// This rejects - 3, there can't be spaces in the number.
		$arr = preg_split('/([-+]?\d+(?:\\' . $kiloseparator . '\d+)*\\' . $decseparator . '?[\d]*(?:\s*[eE][-+]?\d+)?)[ ]*/', trim($v), 2, PREG_SPLIT_DELIM_CAPTURE);

		$arrSiz = count($arr);
		if ($arrSiz >= 1) $preNum = $arr[0];
		if ($arrSiz >= 2) $num = $arr[1];
		if ($arrSiz >= 3) $unit = $arr[2];

		if ($num !== null) {
			// sscanf doesn't like commas or other than '.' for decimal point.
			$num = str_replace($kiloseparator, '', $num);
			if ($decseparator != '.') {
				$num = str_replace($decseparator, '.', $num);
			}
			// sscanf doesn't like space between number and exponent.
			// TODO: couldn't we just delete all ' '? -- mak
			$num = preg_replace('/\s*([eE][-+]?\d+)/', '$1', $num, 1);
			
			$extra = ''; // required, a failed sscanf leaves it untouched.
			// Run sscanf to convert the number string to an actual float.
			// This also strips any leading + (relevant for LIKE search).
			list($num, $extra) = sscanf($num, "%f%s");
			
			// junk after the number after parsing indicates syntax error
			// TODO: can this happen? Isn't all junk thrown into $unit anyway? -- mak
			if ($extra != '') {
				$num = null;	// back to error state
			}

			// Clean up leading space from unit, which should be common
			$unit = preg_replace('/^(?:&nbsp;|&thinsp;|\s)+/','', $unit);
			
			if (is_infinite($num)) {
				return array($preNum, $num, $unit, wfMsgForContent('smw_infinite', $v));
			}
			return array($preNum, $num, $unit, '');
		} else {
			return array('', null, '', wfMsgForContent('smw_nofloat', $v));
		}
	}

	/**
	 * Append unit (if any) separated by HTML non-breaking space 
	 * entity.
	 */
	function appendUnit($num, $unit) {
		return ($unit !== '' and $unit !== false) ? $num . '&nbsp;' . $unit : $num ;
	}

	/**
	 * Transform a (typically unit-) string into a normalised form,
	 * so that, e.g., "km²" and "km<sup>2</sup>" do not need to be
	 * distinguished.
	 */
	function normalizeUnit($unit) {
		$unit = str_replace(array('²','<sup>2</sup>'), '&sup2;', $unit);
		$unit = str_replace(array('³','<sup>3</sup>'), '&sup3;', $unit);
		return $unit;
	}

} // End SMWFloatTypeHandler}

SMWTypeHandlerFactory::registerTypeHandler('_flt',
                        new SMWFloatTypeHandler());


/**
 * Linear converter class which is able to implement all possible
 * unit conversions that require only simple multiplications (which
 * is the case for most quatntities). Objects of this class are
 * initialised with an array of conversion factors and a type-id.
 * This class is often instantiated from user supplied conversion 
 * data, which happens in Datatype.php.
 * @see SMWTypeHandlerFactory::getTypeHandlerByLabel()
 */
class SMWLinearTypeHandler extends SMWFloatTypeHandler {

	/**#@+
	 * @access private
	 */
	var $unitFactors = array();	// gets filled in with array of numbers indexed by unit strings after parsing.

	/**#@-*/

	/**
	 * Constructor. Since the linear type is polymorphic, a 
	 * typeid is needed to be able to retrieve the right type for
	 * values stored in the database (where the typeid is used).
	 */
	function SMWLinearTypeHandler($typeid, $conversions = NULL) {
		$this->typeid = str_replace(' ', '_', $typeid);
		if ($conversions !== NULL) {
			$this->parseConversionFactors($conversions);
		}
	}

	/**
	 * Set up instance information from conversion factors.
	 * It takes an array of strings of the form "FACTOR UNIT",
	 * e.g. "1 m" or "0.001 km". The first entry with factor 1 is 
	 * used as the primary unit.
	 *
	 * To support multiple forms of a unit, you can use the same 
	 * factor multiple times or supply additional unit strings in
	 * one statement similar to
	 *	converts to:= 0.001 km, kilometer, kilometers, kilometre, kilometres
	 *	We expect that no reasonable unit will ever contain a ","
	 */
	function parseConversionFactors ($conversions) {
		$this->primaryUnit = '';
		foreach($conversions as $conversionSpec) {
			// The conversion spec looks like a regular float:
			list($preNum, $conversionFactor, $synonymList, $errStr) = $this->parseValue($conversionSpec);
			if ($errStr !== '' || $preNum !== '' || 0 == $conversionFactor ) {
				// Can't handle prefix ($preNum) yet, though it might be nice for e.g. $3.50 in currencies.
				continue;
			}
			// Split all the synonyms on the special character ","
			$unitSynonyms = preg_split('/\s*,\s*/', $synonymList);
			
			foreach ( $unitSynonyms as $unit ) {
				$unit = $this->normalizeUnit($unit);
				$this->unitFactors[$unit] = $conversionFactor;
				list($uid,$err) = $this->getUnitID('',$unit);
				if ( (1 == $conversionFactor) && ('' == $this->primaryUnit) ){
					$this->primaryUnit = $unit;
				} elseif ( (1 != $conversionFactor) && (!array_key_exists($uid, $this->otherDisplayUnits)) ) {
					$this->otherDisplayUnits[$uid] = $unit;
				}
			}
		}
	}

	function getUnitID($prefixIn, $unitIn) {
		$errStr = '';
		if ($prefixIn !== '') {
			$errStr = wfMsgForContent('smw_unsupportedunit', $prefixIn);
		}
		if ( array_key_exists($unitIn, $this->unitFactors) ) {
			$uid = strval($this->unitFactors[$unitIn]); // use factor as ID
		} else {
			$uid = $unitIn;
		}
		return array($uid, $errStr);
	}

	function convertQty($numIn, $toPrimary, $unit) {
		if (array_key_exists($unit, $this->unitFactors)) {
			$conversionFactor = $this->unitFactors[$unit];
			$numOut = $toPrimary ? $numIn/$conversionFactor : $numIn*$conversionFactor ;
			return array($numOut, '');
		} else {
			return array($numIn, wfMsgForContent('smw_unsupportedunit',$unit));
		}
	}
} // End class SMWLinearTypeHandler


/***** More linear unit conversions *****/

/**** Type:Length
 *
 * To get the former type length, just insert the following statements 
 * into the article "Type:Length":

[[corresponds to:=1 m, meter, meters, metre, metres]]

[[corresponds to:=0.001 km, kilometer, kilometers, kilometre, kilometres]]

[[corresponds to:=0.00062137119223733397 mi, ml, miles, mile]]

 ****/

/**** Type:Area
 *
 * To get the former type area, just insert the following statements 
 * into the article "Type:Area" (note that only one form of "²" is needed,
 * although other forms like "&sup2;" still are accepted.

[[corresponds to:=1 m², sqm, sqare meter, square meters, square metre, square metres]]

[[corresponds to:=0.000001 km², sqkm, sqare kilometer, square kilometers, square kilometre, square kilometres]]

[[corresponds to:=0.00001 ha, hectare, hectares]]

[[corresponds to:=3.86102158542e-7 miles², sqml, sqmi, ml², mi², square miles]]

 * For Type:Geographic area, you can also multiply all of the above values by
 * 1,000,000 such that km² becomes the standard unit as in earlier versions.
 * Note that SMW will still work with the old data, even if new values are stored 
 * in m² instead of km². So using the general purpose Type:Area in m² is 
 * recommended.
 ****/

/**** Type:Mass
 *
 * To get the former type mass, just insert the following statements 
 * into the article "Type:Mass":

[[corresponds to:=1 kg, kilogram, kilograms]]

[[corresponds to:=1000 g, gram, grams]]

[[corresponds to:=0.001 t, tonne, tonnes]]

[[corresponds to:=0.45359237 lb, lbs, pounds]]

[[corresponds to:=7.25747792 oz, ounce, ounces]]

[[corresponds to:=5000 carat, carats]]

[[corresponds to:=6.02214151134048136639e26 u, Da]]

 ****/
 
/**** Type:Time
 *
 * To get the former type time, just insert the following statements 
 * into the article "Type:Time":

[[corresponds to:=1 s, sec, secs, second, seconds]]
[[corresponds to:=0.01666666666666666667 m, min, mins, minute, minutes]]
[[corresponds to:=0.00027777777777777778 h, hour, hours]]
[[corresponds to:=1.15740740740740740741e-5 d, day, days]]
[[corresponds to:=3.16875357828003389202e-8 a, year, years]]

 * Note that the factor for years uses the current exact astronomical length
 * whereas the factor for days corresponds to the usual 60x60x24 sec. Decide
 * for yourself what conversion youreally want for your "time" -- maybe two
 * types using "simplified" and "astronomical" durations are in order.
 ****/


/***** Non-linear unit conversions *****/

/**
 * This class handles temperature unit conversions.
 * This is slightly trickier in that it involves an offset and factor,
 * so it could not be implemented in wiki pages using SMWLinearTypeHandler
 */
class SMWTemperatureTypeHandler extends SMWFloatTypeHandler {

	/**#@+
	 * @access private
	 */
	var $typeid = '_tem';
	var $primaryUnit = 'K';
	var $otherDisplayUnits = array('°C', '°F');

	function getUnitID($prefixIn, $unitIn) {
		$errStr = '';		
		if ($prefixIn !== '') {
			$errStr = wfMsgForContent('smw_unsupportedunit', $prefixIn);
		}

		// TODO: localize unit strings
		switch ( $unitIn ) {
			case '': case 'K': case 'kelvin': case 'kelvins':
				$uid='K';
				break;
			// There's a dedicated Unicode character (℃, U+2103). for degrees C.
			// My Eclipse editor doesn't display it! (S)
			// My Kate editor displays it, but it probably rather is a font issue. (mak)
			case '°C': case '℃': case 'Celsius': case 'centigrade':
				$uid='C';
				break;
			// Should really be "degrees Fahrenheit"  Should I have an UnDegree utility function? (S)
			// : If so, then it should be private to this class. (mak)
			case '°F': case 'Fahrenheit':
				$uid='F';
				break;
			default: //unsupported unit
				$uid=$unitIn;
				break;
		}
		return array($uid, $errStr);
	}

	function convertQty($numIn, $toPrimary, $unit) {
		$numOut = $numIn;
		$errStr = '';

		switch ( $unit ) {
			case '': case 'K': case 'kelvin': case 'kelvins':
				// unit is already the primary unit, so number out is the same.
				$numOut = $numIn;
				break;
			case '°C': case '℃': case 'Celsius': case 'centigrade':
				$numOut = $toPrimary ? $numIn+273.15 : $numIn-273.15 ;
				break;
			case '°F': case 'Fahrenheit':
				if ($toPrimary) {
					$numOut = ($numIn - 32) / 1.8 + 273.15;
				} else {
					$numOut = ($numIn - 273.15) * 1.8 + 32;
				}
				break;
			default: //unsupported unit
				$errStr = wfMsgForContent('smw_unsupportedunit',$unit);
				break;
		}

		return array($numOut, $errStr);
	}
} // End class SMWTemperatureTypeHandler

SMWTypeHandlerFactory::registerTypeHandler('_tem',
                       new SMWTemperatureTypeHandler());


