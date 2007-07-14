<?php

global $smwgIP, $smwgContLang;
require_once($smwgIP . '/includes/SMW_DataValue.php');
require_once($smwgIP . '/includes/SMW_DV_Error.php');
require_once($smwgIP . '/includes/SMW_OldDataValue.php');

/**
 * Factory class for creating SMWDataValue objects for supplied types or attributes
 * and data values.
 *
 * The class has two main entry points:
 * - newTypeObjectValue
 * - newTypeIDValue
 * These create new DV objects, possibly with preset user values, captions and attribute names.
 * Further methods are used to conveniently create DVs for attributes and special properties:
 * - newAttributeValue
 * - newAttributeObjectValue
 * - newSpecialValue
 */
class SMWDataValueFactory {

	/**
	 * Array of class names and initialisation data for creating
	 * new SMWDataValues. Indexed by type label (without namespace).
	 * Each entry has the form 
	 *        array(included?, filepart, classname);
	 */
	static private $m_valueclasses = array();

	/**
	 * Cache for type specifications (type datavalues), indexed by attribute name (both without namespace prefix).
	 */
	static private $m_typelabels = array();

	/**
	 * Create a value from a string supplied by a user for a given attribute.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newAttributeValue($attstring, $value=false, $caption=false) {
		if(array_key_exists($attstring,SMWDataValueFactory::$m_typelabels)) { // use cache
			return SMWDataValueFactory::newTypeObjectValue(SMWDataValueFactory::$m_typelabels[$attstring], $value, $caption, $attstring);
		} // else: find type for attribute:

		$atitle = Title::newFromText($attstring, SMW_NS_ATTRIBUTE);
		if ($atitle !== NULL) {
			return SMWDataValueFactory::newAttributeObjectValue($atitle,$value,$caption);
		} else {
			$type = SMWDataValueFactory::newTypeIDValue('__typ');
			$type->setXSDValue('_wpg');
			SMWDataValueFactory::$m_typelabels[$attstring] = $type;
			return SMWDataValueFactory::newTypeIDValue('_wpg',$value,$caption,$attstring);
		}
	}

	/**
	 * Create a value from a string supplied by a user for a given attribute title.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newAttributeObjectValue(Title $att, $value=false, $caption=false) {
		$attstring = $att->getText();
		if(array_key_exists($attstring,SMWDataValueFactory::$m_typelabels)) { // use cache
			return SMWDataValueFactory::newTypeObjectValue(SMWDataValueFactory::$m_typelabels[$attstring], $value, $caption, $attstring);
		} // else: find type for attribute:

		$typearray = smwfGetStore()->getSpecialValues($att,SMW_SP_HAS_TYPE);
		if (count($typearray)==1) {
			SMWDataValueFactory::$m_typelabels[$attstring] = $typearray[0];
			$result = SMWDataValueFactory::newTypeObjectValue(SMWDataValueFactory::$m_typelabels[$attstring], $value, $caption, $attstring);
			return $result;
		} elseif (count($typearray)==0) {
			$type = SMWDataValueFactory::newTypeIDValue('__typ');
			$type->setXSDValue('_wpg');
			SMWDataValueFactory::$m_typelabels[$attstring] = $type;
			return SMWDataValueFactory::newTypeIDValue('_wpg',$value,$caption,$attstring);
		} else {
			return new SMWErrorValue(wfMsgForContent('smw_manytypes'), $value, $caption);
		}
	}

	/**
	 * Create a value from a string supplied by a user for a given special
	 * property, encoded as a numeric constant.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newSpecialValue($specialprop, $value=false, $caption=false) {
		switch ($specialprop) {
			case SMW_SP_HAS_TYPE:
				$result = SMWDataValueFactory::newTypeIDValue('__typ', $value, $caption);
				break;
			case SMW_SP_HAS_URI:
				$result = SMWDataValueFactory::newTypeIDValue('_uri', $value, $caption);
				break;
			case SMW_SP_MAIN_DISPLAY_UNIT: case SMW_SP_DISPLAY_UNIT: case SMW_SP_SERVICE_LINK:
			case SMW_SP_CONVERSION_FACTOR: case SMW_SP_POSSIBLE_VALUE:
				$result = SMWDataValueFactory::newTypeIDValue('_str', $value, $caption);
				break;
			case SMW_SP_CONVERSION_FACTOR_SI:
				$result = SMWDataValueFactory::newTypeIDValue('_str', $value, $caption);
				break; // TODO: change this into an appropriate handler
			default: // no special property
				$result = new SMWErrorValue(wfMsgForContent('smw_noattribspecial',$specprops[$special]));
		}

		if ($value !== false) {
			$result->setUserValue($value,$caption);
		}
		return $result;
	}

	/**
	 * Create a value from a type value (basically containing strings).
	 * If no $value is given, an empty container is created, the value of which
	 * can be set later on.
	 * @param $typevalue datavalue representing the type of the object
	 * @param $value user value string, or false if unknown
	 * @param $caption user-defined caption or false if none given
	 * @param $attstring text name of according attribute, or false (may be relevant for getting further parameters)
	 */
	static public function newTypeObjectValue(SMWDataValue $typevalue, $value=false, $caption=false, $attstring=false) {
		if (array_key_exists($typevalue->getXSDValue(), SMWDataValueFactory::$m_valueclasses)) {
			return SMWDataValueFactory::newTypeIDValue($typevalue->getXSDValue(), $value, $caption, $attstring);
		} else {
			if (!$typevalue->isUnary()) { // n-ary type?
				$result = SMWDataValueFactory::newTypeIDValue('__nry');
				$result->setType($typevalue);
			} else { ///TODO migrate to new system
				$type = SMWTypeHandlerFactory::getTypeHandlerByLabel($typevalue->getWikiValue());
				$result = new SMWOldDataValue($type);
			}
		}

		if ($attstring != false) {
			$result->setAttribute($attstring);
		}
		if ($value !== false) {
			$result->setUserValue($value,$caption);
		}
		return $result;
	}

	/**
	 * Create a value from a type id.
	 * If no $value is given, an empty container is created, the value of which
	 * can be set later on.
	 * @param $typeid id string for the given type
	 * @param $value user value string, or false if unknown
	 * @param $caption user-defined caption or false if none given
	 * @param $attstring text name of according attribute, or false (may be relevant for getting further parameters)
	 */
	static public function newTypeIDValue($typeid, $value=false, $caption=false, $attstring=false) {
		if (array_key_exists($typeid, SMWDataValueFactory::$m_valueclasses)) {
			$vc = SMWDataValueFactory::$m_valueclasses[$typeid];
			// check if class file was already included for this class
			if ($vc[0] == false) {
				global $smwgIP;
				if (file_exists($smwgIP . '/includes/SMW_DV_'. $vc[1] . '.php')) {
					include_once($smwgIP . '/includes/SMW_DV_'. $vc[1] . '.php');
				} else { // file for registered type missing
					new SMWErrorValue(wfMsgForContent('smw_unknowntype'), $value, $caption);
				}
				$vc[0] = true;
			}
			$result = new $vc[2]($typeid);
		} else {
			$typevalue = SMWDataValueFactory::newTypeIDValue('__typ');
			$typevalue->setXSDValue($typeid);
			return SMWDataValueFactory::newTypeObjectValue($typevalue, $value, $caption, $attstring);
		}

		if ($attstring != false) {
			$result->setAttribute($attstring);
		}
		if ($value !== false) {
			$result->setUserValue($value,$caption);
		}
		return $result;
	}

	/**
	 * Quickly get the type id of some attribute without necessarily making another datavalue.
	 */
	static public function getAttributeObjectTypeID(Title $att) {
		$attstring = $att->getText();
		if (array_key_exists($attstring, SMWDataValueFactory::$m_typelabels)) {
			return SMWDataValueFactory::$m_typelabels[$attstring]->getXSDValue();
		} else {
			return SMWDataValueFactory::newAttributeObjectValue($att)->getTypeID(); // this also triggers caching
		}
	}

	/**
	 * Create a value from a user-supplied string for which a type handler is known
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 *
	 * @DEPRECATED
	 */
	static public function newTypeHandlerValue(SMWTypeHandler $type, $value=false) {
		$result = new SMWOldDataValue($type);
		if ($value !== false) {
			$result->setUserValue($value);
		}
		return $result;
	}

	/**
	 * Register a new SMWDataValue class for dealing with some type. Will be included and
	 * instantiated dynamically if needed.
	 */
	static public function registerDataValueClass($typestring, $filepart, $classname) {
		SMWDataValueFactory::$m_valueclasses[$typestring] = array(false,$filepart,$classname);
	}

}

/// NOTE: the type constants are registered to translated labels in SMW_TypeValue.php.
/// However, types that are not available to users can also have ids for being registered here, but
/// these ids should start with two underscores.
SMWDataValueFactory::registerDataValueClass('_str','String','SMWStringValue');
// SMWDataValueFactory::registerDataValueClass('ema','URI','SMWURIValue');
// SMWDataValueFactory::registerDataValueClass('uri','URI','SMWURIValue');
// SMWDataValueFactory::registerDataValueClass('url','URI','SMWURIValue');
// SMWDataValueFactory::registerDataValueClass('anu','URI','SMWURIValue');
SMWDataValueFactory::registerDataValueClass('_wpg','WikiPage','SMWWikiPageValue');

SMWDataValueFactory::registerDataValueClass('__typ','Types','SMWTypesValue');
SMWDataValueFactory::registerDataValueClass('__nry','NAry','SMWNAryValue');
