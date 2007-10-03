<?php
/**
 * This file contains the datatype management system, some basic
 * classes for process data values given to attributes, and core
 * formatting functions. In order to create new datatypes, copy
 * the simple string or integer type classes below into a separate
 * file, modify as appropriate, add a call to the registration
 * function (SMWTypeHandlerFactory::registerTypeHandler), and include
 * your file here or in LocalSettings.php after SMW was registered.
 *
 * @author Markus Krötzsch
 * @author Kai Hüner
 */


/**@ We need to always pull in DT_Float.php because SMW treats missing types
 *   as custom units (class SMWLinear).
 * TODO: revisit this dependency and announce Float/Linear/Temperature like other datatypes?
 */
require_once('SMW_DT_Float.php');

/**@ We need to always pull in DT_URI because it is the unit of the special
 *   property Equivalent URI.
 * TODO: remove this dependency. The special property needs to be checked for that
 */
require_once('SMW_DT_URI.php');

/**
 * Static class for registering and retrieving typehandlers.
 * It also caches information about attributes found during page parsing.
 *
 * Currently, types are identified in three different ways within the
 * storage and wiki. In the wiki, users always use the full name of some
 * page in the namespace "Type:" (or whatever its localised version is
 * called like). The annotation is stored internally without the namespace
 * prefix but still using the (possibly also localised) name of the page.
 * This abbreviated name is the "label" of the type. When storing attribute
 * values, types are identified via an "id". This is an language-independent
 * identifier for that type. For instance, English "Type:Integer" has the
 * label "Integer" and the ID "int".
 *
 * For custom types, IDs must depend on the label of the type, and (to distinguish
 * IDs from those of builtin types) is always prefixed with "Type:" (ignoring any
 * localisation for the label of the type namespace). For example, a German custom
 * type "Datentyp:Länge" has the label "Länge" and the ID "Type:Länge".
 *
 * Originally, the distinction of labels and ids was introduced to make the storage
 * contents less dependent on the wikis text content and language. After the
 * extension of the type system with custom types, this design should be revised.
 * The issue is that built-in types need a internationalisation lookup for
 * identifying them from their labels. Avoiding this was the intention of the IDs,
 * but the implementation does not really achieve this at the moment (type labels
 * are not part of the message system yet, and they are registered by their labels
 * anyway suc that no second lookup is necessary).
 *
 * TODO: This is a mess. Completely revise the type registration and identification system.
 */
class SMWTypeHandlerFactory {

	static private $typeHandlersByLabel = Array();
	static private $typeHandlersByAttribute = Array();
	static private $desiredUnitsByAttribute = Array();
	static private $possibleValuesByAttribute = Array();
	static private $serviceLinksByAttribute = Array();

	/**
	 * This method registers a typehandler under a certain
	 * label. The label is the name used by the user to denote
	 * this type, without any namespace prefixes.
	 */
	static function registerTypeHandler($label, $th) {
		SMWTypeHandlerFactory::$typeHandlersByLabel[$label] = $th;
	}

	/**
	 * This method announces the existence of a typehandler under
	 * a certain label and with a certain id. The label is the name
	 * used by the user to denote this type, without any namespace
	 * prefixes.
	 */
	static function announceTypeHandler($label, $id, $filepart, $class, $param = NULL) {
		SMWTypeHandlerFactory::$typeHandlersByLabel[$label] = array($filepart, $class, $param);
	}

	/**
	 * This method returns the type handler object for a given type label
	 * (i.e. a localized type name), or an error type handler if the label
	 * is not associated with some handler. The label is usually the article
	 * name of the type, without the namepsace prefix (Type:). The optional
	 * parameter $findConversions can be used to prevent searching for custom
	 * datatypes in cases where no built-in datatype is found.
	 */
	static function getTypeHandlerByLabel($typelabel, $findConversions=true) {
		if (array_key_exists($typelabel,SMWTypeHandlerFactory::$typeHandlersByLabel)) {
			$th = SMWTypeHandlerFactory::$typeHandlersByLabel[$typelabel];
			if (is_array($th)) { //instantiate the handler first
				global $smwgIP;
				if (file_exists($smwgIP . '/includes/SMW_DT_'. $th[0] . '.php')) {
					include_once($smwgIP . '/includes/SMW_DT_'. $th[0] . '.php');
					SMWTypeHandlerFactory::$typeHandlersByLabel[$typelabel] = new $th[1]($th[2]);
				} else {
					return new SMWErrorTypeHandler(wfMsgForContent('smw_unknowntype',$typelabel));
				}
			}
			return SMWTypeHandlerFactory::$typeHandlersByLabel[$typelabel];
		}
		// Maybe a custom type? Try to find conversion factors:
		if (!$findConversions) return NULL;
		$conversionFactors = SMWTypeHandlerFactory::getConversionFactors($typelabel);
		if (count($conversionFactors) !== 0) {
			$instance = new SMWLinearTypeHandler($typelabel, $conversionFactors);
			SMWTypeHandlerFactory::$typeHandlersByLabel[$typelabel] = $instance;
			return SMWTypeHandlerFactory::$typeHandlersByLabel[$typelabel];
		}

		return new SMWErrorTypeHandler(wfMsgForContent('smw_unknowntype',$typelabel));
	}

	/**
	 * This method retrieves the desired display units list, if any,
	 * for a given attribute as an array. It gets them from two
	 * special properties.
	 *
	 * TODO: Performance (minor?): maybe combine the two database queries
	 *       for special properties into one special smwfGetDisplayUnitsTitles() query for both.
	 *
	 * @param $attribute should be in text form without preceding namespace.
	 */
	static function &getUnitsList($attribute) {
		if(!array_key_exists($attribute, SMWTypeHandlerFactory::$desiredUnitsByAttribute)) {
			global $wgContLang;
			$store = smwfGetStore();

			SMWTypeHandlerFactory::$desiredUnitsByAttribute[$attribute] = Array();
			$atitle = Title::newFromText($wgContLang->getNsText(SMW_NS_ATTRIBUTE) . ':' . $attribute);
			if ( ($atitle !== NULL) && ($atitle->exists()) ) {
				// get main display unit:
				$auprops = $store->getSpecialValues($atitle, SMW_SP_MAIN_DISPLAY_UNIT);
				if (count($auprops) > 0) { // ignore any further main units if given
					SMWTypeHandlerFactory::$desiredUnitsByAttribute[$attribute][] = $auprops[0];
				}
				// get further units:
				$auprops = $store->getSpecialValues($atitle, SMW_SP_DISPLAY_UNIT);
				foreach ($auprops as $uprop) {
					SMWTypeHandlerFactory::$desiredUnitsByAttribute[$attribute][] = $uprop;
				}
			}
		}
		return SMWTypeHandlerFactory::$desiredUnitsByAttribute[$attribute];
	}


	/**
	 * This method retrieves the possible values, if any,
	 * for a given attribute as an array.
	 *
	 * @param $attribute should be in text form without preceding namespace.
	 */
	static function &getPossibleValues($attribute) {
		if(!array_key_exists($attribute, SMWTypeHandlerFactory::$possibleValuesByAttribute)) {
			global $wgContLang;

			SMWTypeHandlerFactory::$possibleValuesByAttribute[$attribute] = Array();
			$atitle = Title::newFromText($wgContLang->getNsText(SMW_NS_PROPERTY) . ':' . $attribute);
			if ($atitle !== NULL) {
				$apvprops = smwfGetStore()->getSpecialValues($atitle, SMW_SP_POSSIBLE_VALUE);
				foreach ($apvprops as $prop ) {
					SMWTypeHandlerFactory::$possibleValuesByAttribute[$attribute][] = $prop;
				}
			}
		}
		return SMWTypeHandlerFactory::$possibleValuesByAttribute[$attribute];
	}

	/**
	 * This method retrieves the conversion factors, if any, for a
	 * given type as an array of strings. It gets them from a special
	 * property, e.g. if Attribute:Max_speed HasType Type:Velocity, then
	 * Type:Velocity page has the ConversionFactors that we have to
	 * pass to an SMWLinearTypeHandler instance.
	 *
	 * @return (possibly empty) array of conversion factors, each a string
	 * @param $type should be in text form without preceding namespace.
	 */
	static function &getConversionFactors($type) {
		global $wgContLang;

		$ttitle = Title::newFromText($wgContLang->getNsText(SMW_NS_TYPE) . ':' . $type);
		if ( ($ttitle !== NULL) && ($ttitle->exists()) ) {
			$result = smwfGetStore()->getSpecialValues($ttitle, SMW_SP_CONVERSION_FACTOR);
			if (count($result) == 0) {
				$result = array();
			}
		}
		return $result;
	}


	/**
	 * This method retrieves additional service links, if any, for a
	 * given type as an array of id strings. The ids are the back part
	 * of a MediaWiki message article constructed by prepending the
	 * string "MediaWiki:smw_service_" to the id. It is expected that
	 * the messages are resolved lazyliy if needed (at all), so they
	 * are not decomposed to strings at this stage.
	 */
	static function &getServiceLinks($attribute) {
		if(!array_key_exists($attribute, SMWTypeHandlerFactory::$serviceLinksByAttribute)) {
			global $wgContLang;
			SMWTypeHandlerFactory::$serviceLinksByAttribute[$attribute] = Array();
			$atitle = Title::newFromText($wgContLang->getNsText(SMW_NS_ATTRIBUTE) . ':' . $attribute);
			if ( ($atitle !== NULL) && ($atitle->exists()) ) {
				$auprops = smwfGetStore()->getSpecialValues($atitle, SMW_SP_SERVICE_LINK);
				if (count($auprops) > 0) { // ignore any further service link annotations if given
					SMWTypeHandlerFactory::$serviceLinksByAttribute[$attribute][] = $auprops[0];
				}
			}
		}
		return SMWTypeHandlerFactory::$serviceLinksByAttribute[$attribute];
	}

} // SMWTypeHandlerFactory

//*** Make other typehandlers known that are shipped with SMW ***//
/**
 * If you add a typehandler in a separate file from this one (SMW_Datatype.php)
 * then you must add it to this list!
 */
// Integer
SMWTypeHandlerFactory::announceTypeHandler('_int','int','Integer','SMWIntegerTypeHandler');
// URLs etc.
// SMWTypeHandlerFactory::announceTypeHandler('_ema','email','URI','SMWURITypeHandler','email');
// SMWTypeHandlerFactory::announceTypeHandler('_uri','uri','URI','SMWURITypeHandler','uri');
// SMWTypeHandlerFactory::announceTypeHandler('_anu','annouri','URI','SMWURITypeHandler','annouri');
// Dates & times
SMWTypeHandlerFactory::announceTypeHandler('_dat','datetime','DateTime','SMWDateTimeTypeHandler');
// Geographic coordinates
SMWTypeHandlerFactory::announceTypeHandler('_geo','geocoords','GeoCoords','SMWGeographicLocationTypeHandler');
// Enums
SMWTypeHandlerFactory::announceTypeHandler('_enu','enum','Enum','SMWEnumTypeHandler');
// Text
//SMWTypeHandlerFactory::announceTypeHandler('_txt','text','Text','SMWTextTypeHandler');
// Bools
// Booleans can (and more problematic: will) be modelled by two-valued enums; too much choice yields confusion (note that Categories are also addressing a simliar modelling problem already -- let's not introduce three ways of encoding this)
//SMWTypeHandlerFactory::announceTypeHandler('_boo'),'bool','Boolean','SMWBooleanTypeHandler');

/*********************************************************************/
/* Basic typehandler classes                                         */
/*********************************************************************/

/**
 * Interface (abstract class) that must be implemented by all type
 * handlers.
 *
 * Typehandlers are used for the intitialisation of datavalues.
 * Their main methods are processValue and processXSDValue which
 * intitialise a given SMWDataValue. These methods should typically
 * set the user, string, and XSD representation of the value, and
 * possibly other fields if applicable. See SMWDataValue for details.
 */
interface SMWTypeHandler {
	/**
	 * Return an internal ID for this type. This ID is used to store
	 * the type in the database, and should be a language-independent
	 * string that could be resolved unambiguously to the original
	 * type handler.
	 */
	public function getID();

	/**
	 * Return the full URI of the XSD type that is to be used when
	 * exporting values of this type. If '' (empty string) is
	 * returned, then values will be stored as object properties.
	 */
	public function getXSDType();

	/**
	 * Somewhat deprecated way of retrieving a sample of possible
	 * units that are supported.
	 * TODO: find some better way of doing this.
	 */
	public function getUnits();

    /**
	 * Main method for type handlers. It transforms the user-provided
	 * value of an attribute into several output strings (one for XML,
	 * one for printout, etc.) and initialises the given SMWDataValue
	 * accordingly. Parsing errors are reported as well.
	 */
	public function processValue($value,&$datavalue);

    /**
	 * Second main method for type handlers. It transforms the
	 * XSD-conformant value of an attribute into several output
	 * strings (one for XML, one for printout, etc.) and initialises
	 * the given SMWDataValue accordingly. Parsing errors are reported
	 * as well.
	 */
	public function processXSDValue($value,$unit,&$datavalue);

	/**
	 * Returns a boolean to indicate whether values of the given type
	 * can be ordered linearly in a natural way other than sorting their
	 * XSD versions lexicographically. If TRUE, the type handler must
	 * also supply a corresponding numerical version of the value during
	 * parsing.
	 */
	public function isNumeric();
}


/**
 * Pseudo typehandler, which returns predefined error
 * messages instead of parsing any value.
 */
class SMWErrorTypeHandler implements SMWTypeHandler {
	/**#@+
	 * @access private
	 */
	private $emsg; //the message for the user
	/**#@-*/

	/**
	 *  Constructor.
	 */
	function SMWErrorTypeHandler($errorMsg) {
		$this->emsg=$errorMsg;
	}

	function getID() {
		return 'error';
	}

	function getXSDType() {
		return 'http://www.w3.org/2001/XMLSchema#string';
	}

	function getUnits() {
		return array('STDUNIT'=>false, 'ALLUNITS'=>array());
	}

	function processValue($value,&$datavalue) {
		$datavalue->setError($this->emsg);
		return true;
	}

	function processXSDValue($value,$unit,&$datavalue) {
		return $this->processValue($value,$datavalue);
	}

	function isNumeric() {
		return FALSE;
	}
}

/**
 * Class for managing string types. Very simple.
 */
// class SMWStringTypeHandler implements SMWTypeHandler {
// 
// 	function getID() {
// 		return 'string';
// 	}
// 
// 	function getXSDType() {
// 		return 'http://www.w3.org/2001/XMLSchema#string';
// 	}
// 
// 	function getUnits() { //no units for strings
// 		return array('STDUNIT'=>false, 'ALLUNITS'=>array());
// 	}
// 
// 	function processValue($value,&$datavalue) {
// 		if ($value!='') { //do not accept empty strings
// 			$xsdvalue = smwfXMLContentEncode($value);
// 			// 255 below matches smw_attributes.value_xsd definition in smwfMakeSemanticTables()
// 			// Note that depending on database encoding and UTF-8 settings, longer or
// 			// shorter strings than this with int'l characters may exceed database field.
// 			if (strlen($xsdvalue) > 255) {
// 				$datavalue->setError(wfMsgForContent('smw_maxstring', $xsdvalue));
// 			} else {
// 				$datavalue->setProcessedValues($value, $xsdvalue);
// 				$datavalue->setPrintoutString($value);
// 				$datavalue->addQuicksearchLink();
// 				// TODO: Performance: this causes a SpecialProperties database query and some callers don't use it.
// 				$datavalue->addServiceLinks(urlencode($value));
// 			}
// 		} else {
// 			$datavalue->setError(wfMsgForContent('smw_emptystring'));
// 		}
// 		return true;
// 	}
// 
// 	function processXSDValue($value,$unit,&$datavalue) {
// 		return $this->processValue($value,$datavalue);
// 	}
// 
// 	function isNumeric() {
// 		return false;
// 	}
// }
// 
// SMWTypeHandlerFactory::registerTypeHandler('_str'),
//                        new SMWStringTypeHandler());

/**
 * This method formats a float number value according to the given
 * language and precision settings, with some intelligence to
 * produce readable output. Use it whenever you get a number that
 * was not hand-formatted by a user.
 * TODO: separate formatters for Integer and Float, maybe each should have a method.
 *
 * @param $decplaces optional positive integer, controls how many
 *                   digits after the decimal point (but not in
 *                   scientific notation)
 */
function smwfNumberFormat($value, $decplaces=3) {
	$decseparator = wfMsgForContent('smw_decseparator');
	$kiloseparator = wfMsgForContent('smw_kiloseparator');

	// If number is a trillion or more, then switch to scientific
	// notation. If number is less than 0.0000001 (i.e. twice decplaces),
	// then switch to scientific notation. Otherwise print number
	// using number_format. This may lead to 1.200, so then use trim to
	// remove trailing zeroes.
	$doScientific = false;
	//@TODO: Don't do all this magic for integers, since the formatting does not fit there
	//       correctly. E.g. one would have integers formatted as 1234e6, not as 1.234e9, right?
	//The "$value!=0" is relevant: we want to scientify numbers that are close to 0, but never 0!
	if ( ($decplaces > 0) && ($value != 0) ) {
		$absValue = abs($value);
		if ($absValue >= 1000000000) {
			$doScientific = true;
		} elseif ($absValue <= pow(10,-$decplaces)) {
			$doScientific = true;
		} elseif ($absValue < 1) {
			if ($absValue <= pow(10,-$decplaces)) {
				$doScientific = true;
			} else {
				// Increase decimal places for small numbers, e.g. .00123 should be 5 places.
				for ($i=0.1; $absValue <= $i; $i*=0.1) {
					$decplaces++;
				}
			}
		}
	}
	if ($doScientific) {
		// Should we use decimal places here?
		$value = sprintf("%1.6e", $value);
		// Make it more readable by removing trailing zeroes from n.n00e7.
		$value = preg_replace('/(\\.\\d+?)0*e/', '${1}e', $value, 1);
		//NOTE: do not use the optional $count parameter with preg_replace. We need to
		//      remain compatible with PHP 4.something.
		if ($decseparator !== '.') {
			$value = str_replace('.', $decseparator, $value);
		}
	} else {
		// Format to some level of precision;
		// this does rounding and locale formatting.
		$value = number_format($value, $decplaces, $decseparator, wfMsgForContent('smw_kiloseparator'));

		// Make it more readable by removing ending .000 from nnn.000
		//    Assumes substr is faster than a regular expression replacement.
		$end = $decseparator . str_repeat('0', $decplaces);
		$lenEnd = strlen($end);
		if (substr($value, -$lenEnd) === $end ) {
			$value = substr($value, 0, -$lenEnd);
		} else {
			// If above replacement occurred, no need to do the next one.
			// Make it more readable by removing trailing zeroes from nn.n00.
			$value = preg_replace("/(\\$decseparator\\d+?)0*$/", '$1', $value, 1);
		}
	}
	return $value;
}


