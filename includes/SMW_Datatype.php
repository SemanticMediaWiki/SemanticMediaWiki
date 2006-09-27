<?php
/**
 * This file contains the datatype management systemsm, some basic
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

/*********************************************************************/
/* Code for registering datatype handlers                            */
/*********************************************************************/

require_once('SMW_Storage.php');

/**@ TODO: Performance: Note that the code for every type handler is pulled
 *         in, even though most pages do not use all types. 
 *         Better to only load datatype PHP files as needed!?
 */
require_once('SMW_DT_Float.php');

/**
 * Static class for registerig and retrieving typehandlers.
 */
class SMWTypeHandlerFactory {

	static private $typeHandlersByLabel = Array();
	static private $typeHandlersByAttribute = Array();	
	static private $typeLabelsByID = Array();
	static private $desiredUnitsByAttribute = Array();

	/**
	 * This method registers a typehandler under a certain
	 * label. The label is the name used by the user to denote
	 * this type, without any namespace prefixes.
	 */
	static function registerTypeHandler($label, $th) {
		SMWTypeHandlerFactory::$typeHandlersByLabel[$label] = $th;
		SMWTypeHandlerFactory::$typeLabelsByID[$th->getID()] = $label;
	}

	/**
	 * This method announces the existence of a typehandler under
	 * a certain label and with a certain id. The label is the name 
	 * used by the user to denote this type, without any namespace 
	 * prefixes.
	 */
	static function announceTypeHandler($label, $id, $filepart, $class, $param = NULL) {
		SMWTypeHandlerFactory::$typeHandlersByLabel[$label] = array($filepart, $class, $param);
		SMWTypeHandlerFactory::$typeLabelsByID[$id] = $label;
	}

	/**
	 * This method returns the XSD datatype identifier
	 * for the type of the given id, or NULL if id is not
	 * known. Since this is used a lot during RDF export, it
	 * should be reasonably light-weight.
	 */
	static function getXSDTypeByID($typeid) {
		if (array_key_exists($typeid,SMWTypeHandlerFactory::$typeLabelsByID)) {
			$label = SMWTypeHandlerFactory::$typeLabelsByID[$typeid];
			$th = SMWTypeHandlerFactory::getTypeHandlerByLabel($label, false);
			if ($th !== NULL) return $th->getXSDType();
		}
		// maybe a custom type?
		if ( (mb_substr($typeid,0,5) == 'Type:') ||
			('geoarea' == $typeid) || ('geolength' == $typeid) ) { // compatibility with <0.5 DB entries
			// Reuse existing float type handler:
			SMWTypeHandlerFactory::$typeLabelsByID[$typeid] = SMWTypeHandlerFactory::$typeLabelsByID['float'];
			$th = SMWTypeHandlerFactory::getTypeHandlerByLabel(SMWTypeHandlerFactory::$typeLabelsByID['float'], false);
			return $th->getXSDType();
		}
		return NULL;
	}

	/**
	 * This method returns a typehandler object
	 * for the type of the given id, or NULL if id is not
	 * known. Since this is used a lot during RDF export, it
	 * should be reasonably light-weight.
	 */
	static function getTypeHandlerByID($typeid) {
		if (array_key_exists($typeid,SMWTypeHandlerFactory::$typeLabelsByID)) {
			$label = SMWTypeHandlerFactory::$typeLabelsByID[$typeid];
			$th = SMWTypeHandlerFactory::getTypeHandlerByLabel($label);
			return $th;
		}
		return NULL;
	}

	/**
	 * This method returns a typehandler label
	 * for the type of the given id, or NULL if id is not
	 * known.
	 */
	static function getTypeLabelByID($typeid) {
		if (array_key_exists($typeid,SMWTypeHandlerFactory::$typeLabelsByID)) {
			return SMWTypeHandlerFactory::$typeLabelsByID[$typeid];
		}
		return NULL;
	}

	/**
	 * This method returns an array of the labels of built-in types (those
	 * announced in code rather than user-defined types with custom units).
	 * Needed since typeHandlersByLabel is private.
	 */
	static function getTypeLabels() {
		return array_keys(SMWTypeHandlerFactory::$typeHandlersByLabel);
	}

	/**
	 * This method retrieves a type handler object for a given
	 * special property, provided as a constant identifier. Not
	 * every special property has a type (some are relations),
	 * and an error might be returned in certain cases.
	 */
	static function getSpecialTypeHandler($special) {
		switch ($special) {
			case SMW_SP_HAS_URI:
				return new SMWURITypeHandler(SMW_URI_MODE_URI);
			case SMW_SP_MAIN_DISPLAY_UNIT: case SMW_SP_DISPLAY_UNIT:
				return new SMWStringTypeHandler();
			case SMW_SP_CONVERSION_FACTOR:
				return new SMWStringTypeHandler();
			default:
				global $smwgContLang;
				$specprops = $smwgContLang->getSpecialPropertiesArray();
				return new SMWErrorTypeHandler(wfMsgForContent('smw_noattribspecial',$specprops[$special]));
		}
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
			$instance = new SMWLinearTypeHandler('Type:' . $typelabel, $conversionFactors); // no localisation needed -- "Type:" is just a disamb. string in the DB
			SMWTypeHandlerFactory::$typeHandlersByLabel[$typelabel] = $instance;
			return SMWTypeHandlerFactory::$typeHandlersByLabel[$typelabel];
		}
		return new SMWErrorTypeHandler(wfMsgForContent('smw_unknowntype',$typelabel));
	}

	/**
	 * This method retrieves a type handler object for a given
	 * attribute. The attribute is expected to be in text
	 * form without preceding namespace.
	 */
	static function getTypeHandler($attribute) {
		if(!array_key_exists($attribute,SMWTypeHandlerFactory::$typeHandlersByAttribute)) {
			global $wgContLang;
			$atitle = Title::newFromText($wgContLang->getNsText(SMW_NS_ATTRIBUTE) . ':' . $attribute);
			if ( ($atitle !== NULL) && ($atitle->exists()) ) {
				$typearray = &smwfGetSpecialProperties($atitle,SMW_SP_HAS_TYPE,NULL);
			} else { $typearray = Array(); }

			if (count($typearray)==1) {
				SMWTypeHandlerFactory::$typeHandlersByAttribute[$attribute] = 
				     SMWTypeHandlerFactory::getTypeHandlerByLabel($typearray[0][2]);
			} elseif (count($typearray)==0) {
				SMWTypeHandlerFactory::$typeHandlersByAttribute[$attribute] = 
				     new SMWErrorTypeHandler(wfMsgForContent('smw_notype'));
			} else {
				SMWTypeHandlerFactory::$typeHandlersByAttribute[$attribute] = 
				     new SMWErrorTypeHandler(wfMsgForContent('smw_manytypes'));
			}
		}
		return SMWTypeHandlerFactory::$typeHandlersByAttribute[$attribute];
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

			SMWTypeHandlerFactory::$desiredUnitsByAttribute[$attribute] = Array();
			$atitle = Title::newFromText($wgContLang->getNsText(SMW_NS_ATTRIBUTE) . ':' . $attribute);
			if ( ($atitle !== NULL) && ($atitle->exists()) ) {
				// get main display unit:
				$auprops = &smwfGetSpecialProperties($atitle, SMW_SP_MAIN_DISPLAY_UNIT, NULL);
				if (count($auprops) > 0) { // ignore any further main units if given
					SMWTypeHandlerFactory::$desiredUnitsByAttribute[$attribute][] = $auprops[0][2];
				}
				// get further units:
				$auprops = smwfGetSpecialProperties($atitle, SMW_SP_DISPLAY_UNIT, NULL);
				foreach ($auprops as $uprops) {
					SMWTypeHandlerFactory::$desiredUnitsByAttribute[$attribute][] = $uprops[2];
				}
			}
		}
		return SMWTypeHandlerFactory::$desiredUnitsByAttribute[$attribute];
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

		$result = array();
		$ttitle = Title::newFromText($wgContLang->getNsText(SMW_NS_TYPE) . ':' . $type);
		if ( ($ttitle !== NULL) && ($ttitle->exists()) ) {
			$tprops = &smwfGetSpecialProperties($ttitle, SMW_SP_CONVERSION_FACTOR, NULL);
			foreach ($tprops as $uprops) {
				// uprops[2] has the value_string we want, append to array.
				$result[] = $uprops[2];
			}
		}
		return $result;
	}

} // SMWTypeHandlerFactory

//*** Make other typehandlers known that are shipped with SMW ***//

// Integer
SMWTypeHandlerFactory::announceTypeHandler($smwgContLang->getDatatypeLabel('smw_int'),'int','Integer','SMWIntegerTypeHandler');
// URLs etc.
SMWTypeHandlerFactory::announceTypeHandler($smwgContLang->getDatatypeLabel('smw_email'),'email','URI','SMWURITypeHandler','email');
SMWTypeHandlerFactory::announceTypeHandler($smwgContLang->getDatatypeLabel('smw_url'),'url','URI','SMWURITypeHandler','url');
SMWTypeHandlerFactory::announceTypeHandler($smwgContLang->getDatatypeLabel('smw_uri'),'uri','URI','SMWURITypeHandler','uri');
SMWTypeHandlerFactory::announceTypeHandler($smwgContLang->getDatatypeLabel('smw_annouri'),'annouri','URI','SMWURITypeHandler','annouri');
// Dates & times
SMWTypeHandlerFactory::announceTypeHandler($smwgContLang->getDatatypeLabel('smw_datetime'),'datetime','DateTime','SMWDateTimeTypeHandler');
// Geographic coordinates
SMWTypeHandlerFactory::announceTypeHandler($smwgContLang->getDatatypeLabel('smw_geocoordinate'),'geocoords','GeoCoords','SMWGeographicLocationTypeHandler');



/*********************************************************************/
/* Helper classes for storing output of type handlers                */
/*********************************************************************/

/**
 * This class mainly is a container to store URLs for the factbox in a 
 * clean way. The class provides methods for creating source code for
 * realising them in wiki or html contexts.
 */
class SMWInfolink {
	/**#@+
	 * @access private
	 */
	private $URL;     // the actual link target
	private $caption; // the label for the link
	private $style;   // CSS class of a span to embedd the link into, or 
	                  // FALSE if no extra style is required
	/**#@-*/
	
	function SMWInfolink($linkURL, $linkCaption, $linkStyle=false) {
		$this->URL = $linkURL;
		$this->caption = $linkCaption;
		$this->style = $linkStyle;
	}
	
	/**
	 * Static function to construct attribute search URLs
	 * @access public
	 * @static
	 */
	static function makeAttributeSearchURL($attribute,$value,$skin) {
		global $wgServer;
		return $wgServer . $skin->makeSpecialUrl('SearchTriple','attribute=' . urlencode($attribute) . '&value=' . urlencode($value) . '&do=' . urlencode('Search Attributes'));
	}

	/**
	 * Static function to construct relation search URLs
	 * @access public
	 * @static
	 */
	static function makeRelationSearchURL($relation,$object,$skin) {
		global $wgServer;
		return $wgServer . $skin->makeSpecialUrl('SearchTriple','relation=' . urlencode($relation) . '&object=' . urlencode($object) . '&do=' . urlencode('Search Relations'));
	}

	/**
	 * Return hyperlink for this infolink in HTML format.
	 * @access public
	 */
	function getHTML() {
		if ($this->style !== FALSE) {
			return "<span class=\"$this->style\"><a href=\"$this->URL\">$this->caption</a></span>";
		} else {
			return "<a href=\"$this->URL\">$this->caption</a>";
		}
	}
	
	/**
	 * Return hyperlink for this infolink in wiki format.
	 * @access public
	 */
	function getWikiText() {
		if ($this->style !== FALSE) {
			return "<span class=\"$this->style\">[$this->URL $this->caption]</span>";
		} else {
			return "[$this->URL $this->caption]";
		}
	}
}
	
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
class SMWStringTypeHandler implements SMWTypeHandler {

	function getID() {
		return 'string';
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
			// 255 below matches smw_attributes.value_xsd definition in smwfMakeSemanticTables()
			// Note that depending on database encoding and UTF-8 settings, longer or
			// shorter strings than this with int'l characters may exceed database field.
			if (strlen($xsdvalue) > 255) {
				$datavalue->setError(wfMsgForContent('smw_maxstring', $xsdvalue));
			} else {
				$datavalue->setProcessedValues($value, $xsdvalue);
				$datavalue->setPrintoutString($value);
				$datavalue->addQuicksearchLink();
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
		return false;
	}
}

SMWTypeHandlerFactory::registerTypeHandler($smwgContLang->getDatatypeLabel('smw_string'),
                       new SMWStringTypeHandler());


/**
 * This method formats a float number value according to the given 
 * language and precision settings, with some intelligence to 
 * produce readable output. Use it whenever you get a number that 
 * was not hand-formatted by a user.
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

?>
