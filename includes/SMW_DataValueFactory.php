<?php

/**
 * Factory class for creating SMWDataValue objects for supplied types or properties
 * and data values.
 *
 * The class has two main entry points:
 * - newTypeObjectValue
 * - newTypeIDValue
 * These create new DV objects, possibly with preset user values, captions and property names.
 * Further methods are used to conveniently create DVs for properties and special properties:
 * - newPropertyValue
 * - newPropertyObjectValue
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
	 * Cache for type specifications (type datavalues), indexed by property name (both without namespace prefix).
	 */
	static private $m_typelabels = array();

	/**
	 * Create a value from a string supplied by a user for a given property.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newPropertyValue($propertyname, $value=false, $caption=false) {
		wfProfileIn("SMWDataValueFactory::newPropertyValue (SMW)");
		if(array_key_exists($propertyname,SMWDataValueFactory::$m_typelabels)) { // use cache
			$result = SMWDataValueFactory::newTypeObjectValue(SMWDataValueFactory::$m_typelabels[$propertyname], $value, $caption, $propertyname);
			wfProfileOut("SMWDataValueFactory::newPropertyValue (SMW)");
			return $result;
		} // else: find type for property:

		$ptitle = Title::newFromText($propertyname, SMW_NS_PROPERTY);
		if ($ptitle !== NULL) {
			$result = SMWDataValueFactory::newPropertyObjectValue($ptitle,$value,$caption);
		} else {
			$type = SMWDataValueFactory::newTypeIDValue('__typ');
			$type->setXSDValue('_wpg');
			SMWDataValueFactory::$m_typelabels[$propertyname] = $type;
			$result = SMWDataValueFactory::newTypeIDValue('_wpg',$value,$caption,$propertyname);
		}
		wfProfileOut("SMWDataValueFactory::newPropertyValue (SMW)");
		return $result;
	}

	/**
	 * Create a value from a string supplied by a user for a given property title.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newPropertyObjectValue(Title $property, $value=false, $caption=false) {
		$propertyname = $property->getText();
		if(array_key_exists($propertyname,SMWDataValueFactory::$m_typelabels)) { // use cache
			return SMWDataValueFactory::newTypeObjectValue(SMWDataValueFactory::$m_typelabels[$propertyname], $value, $caption, $propertyname);
		} // else: find type for property:

		$typearray = smwfGetStore()->getSpecialValues($property,SMW_SP_HAS_TYPE);
		if (count($typearray)==1) {
			SMWDataValueFactory::$m_typelabels[$propertyname] = $typearray[0];
			$result = SMWDataValueFactory::newTypeObjectValue(SMWDataValueFactory::$m_typelabels[$propertyname], $value, $caption, $propertyname);
			return $result;
		} elseif (count($typearray)==0) {
			$type = SMWDataValueFactory::newTypeIDValue('__typ');
			$type->setXSDValue('_wpg');
			SMWDataValueFactory::$m_typelabels[$propertyname] = $type;
			return SMWDataValueFactory::newTypeIDValue('_wpg',$value,$caption,$propertyname);
		} else {
			global $smwgIP;
			include_once($smwgIP . '/includes/SMW_DV_Error.php');
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
			case SMW_SP_SUBPROPERTY_OF:
				$result = SMWDataValueFactory::newTypeIDValue('_wpg', $value, $caption);
				break;
			default: // special property was created but not added here; this is bad but we still are nice
				$result = SMWDataValueFactory::newTypeIDValue('_str', $value, $caption);
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
	 * @param $propertyname text name of according property, or false (may be relevant for getting further parameters)
	 */
	static public function newTypeObjectValue(/*SMWDataValue*/ $typevalue, $value=false, $caption=false, $propertyname=false) {
		if (array_key_exists($typevalue->getXSDValue(), SMWDataValueFactory::$m_valueclasses)) {
			return SMWDataValueFactory::newTypeIDValue($typevalue->getXSDValue(), $value, $caption, $propertyname);
		} else {
			if (!$typevalue->isUnary()) { // n-ary type?
				$result = SMWDataValueFactory::newTypeIDValue('__nry');
				$result->setType($typevalue);
			} else { ///TODO migrate to new system
				global $smwgIP;
				include_once($smwgIP . '/includes/SMW_OldDataValue.php');
				$type = SMWTypeHandlerFactory::getTypeHandlerByLabel($typevalue->getWikiValue());
				$result = new SMWOldDataValue($type);
			}
		}

		if ($propertyname != false) {
			$result->setProperty($propertyname);
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
	 * @param $propertyname text name of according property, or false (may be relevant for getting further parameters)
	 */
	static public function newTypeIDValue($typeid, $value=false, $caption=false, $propertyname=false) {
		if (array_key_exists($typeid, SMWDataValueFactory::$m_valueclasses)) {
			$vc = SMWDataValueFactory::$m_valueclasses[$typeid];
			// check if class file was already included for this class
			if ($vc[0] == false) {
				global $smwgIP;
				if (file_exists($smwgIP . '/includes/SMW_DV_'. $vc[1] . '.php')) {
					include_once($smwgIP . '/includes/SMW_DV_'. $vc[1] . '.php');
				} else { // file for registered type missing
					include_once($smwgIP . '/includes/SMW_DV_Error.php');
					new SMWErrorValue(wfMsgForContent('smw_unknowntype'), $value, $caption);
				}
				$vc[0] = true;
			}
			$result = new $vc[2]($typeid);
		} else {
			$typevalue = SMWDataValueFactory::newTypeIDValue('__typ');
			$typevalue->setXSDValue($typeid);
			return SMWDataValueFactory::newTypeObjectValue($typevalue, $value, $caption, $propertyname);
		}

		if ($propertyname != false) {
			$result->setProperty($propertyname);
		}
		if ($value !== false) {
			$result->setUserValue($value,$caption);
		}
		return $result;
	}

	/**
	 * Quickly get the type id of some property without necessarily making another datavalue.
	 */
	static public function getPropertyObjectTypeID(Title $property) {
		$propertyname = $property->getText();
		if (array_key_exists($propertyname, SMWDataValueFactory::$m_typelabels)) {
			if (SMWDataValueFactory::$m_typelabels[$propertyname]->isUnary() ) {
				return SMWDataValueFactory::$m_typelabels[$propertyname]->getXSDValue();
			} else {
				return '__nry';
			}
		} else {
			return SMWDataValueFactory::newPropertyObjectValue($property)->getTypeID(); // this also triggers caching
		}
	}

	/**
	 * Quickly get the type value of some property without necessarily making another datavalue.
	 * FIXME not efficient
	 */
	static public function getPropertyObjectTypeValue(Title $property) {
		$propertyname = $property->getText();
		SMWDataValueFactory::newPropertyObjectValue($property);
		if (array_key_exists($propertyname, SMWDataValueFactory::$m_typelabels)) {
			return SMWDataValueFactory::$m_typelabels[$propertyname];
		} else { // no type found
			return NULL;
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
		global $smwgIP;
		include_once($smwgIP . '/includes/SMW_OldDataValue.php');
		$result = new SMWOldDataValue($type);
		if ($value !== false) {
			$result->setUserValue($value);
		}
		return $result;
	}

	/**
	 * @DEPRECATED
	 */
	static public function newAttributeValue($property, $value=false, $caption=false) {
		trigger_error("Function newAttributeValue is deprecated. Use new property methods.", E_USER_NOTICE);
		return SMWDataValueFactory::newPropertyValue($property, $value, $caption);
	}

	/**
	 * @DEPRECATED
	 */
	static public function newAttributeObjectValue(Title $property, $value=false, $caption=false) {
		trigger_error("Function newAttributeObjectValue is deprecated. Use new property methods.", E_USER_NOTICE);
		return SMWDataValueFactory::newPropertyObjectValue($property, $value, $caption);
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
SMWDataValueFactory::registerDataValueClass('_txt','String','SMWStringValue');
SMWDataValueFactory::registerDataValueClass('_str','String','SMWStringValue');
// SMWDataValueFactory::registerDataValueClass('ema','URI','SMWURIValue');
// SMWDataValueFactory::registerDataValueClass('uri','URI','SMWURIValue');
// SMWDataValueFactory::registerDataValueClass('url','URI','SMWURIValue');
// SMWDataValueFactory::registerDataValueClass('anu','URI','SMWURIValue');
SMWDataValueFactory::registerDataValueClass('_wpg','WikiPage','SMWWikiPageValue');

SMWDataValueFactory::registerDataValueClass('__typ','Types','SMWTypesValue');
SMWDataValueFactory::registerDataValueClass('__nry','NAry','SMWNAryValue');
SMWDataValueFactory::registerDataValueClass('__err','Error','SMWErrorValue');
