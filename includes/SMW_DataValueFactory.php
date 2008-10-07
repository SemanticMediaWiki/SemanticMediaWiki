<?php
/**
 * @file
 * @ingroup SMWDataValues
 */

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
 *
 * @ingroup SMWDataValues
 */
class SMWDataValueFactory {

	/**
	 * Array of type labels indexed by type ids. Used for datatype
	 * resolution.
	 */
	static private $m_typelabels;

	/**
	 * Array of ids indexed by type aliases. Used for datatype
	 * resolution.
	 */
	static private $m_typealiases;

	/**
	 * Array of class names for creating new SMWDataValues, indexed by
	 * type id.
	 */
	static private $m_typeclasses;

	/**
	 * Cache for type specifications (type datavalues), indexed by property name (both without namespace prefix).
	 */
	static private $m_typebyproperty = array();

	/**
	 * Create a value from a string supplied by a user for a given property.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newPropertyValue($propertyname, $value=false, $caption=false) {
		global $smwgPDefaultType;
		wfProfileIn("SMWDataValueFactory::newPropertyValue (SMW)");
		if(array_key_exists($propertyname,SMWDataValueFactory::$m_typebyproperty)) { // use cache
			$result = SMWDataValueFactory::newTypeObjectValue(SMWDataValueFactory::$m_typebyproperty[$propertyname], $value, $caption, $propertyname);
			wfProfileOut("SMWDataValueFactory::newPropertyValue (SMW)");
			return $result;
		} // else: find type for property:

		$ptitle = Title::newFromText($propertyname, SMW_NS_PROPERTY);
		if ($ptitle !== NULL) {
			$result = SMWDataValueFactory::newPropertyObjectValue($ptitle,$value,$caption);
		} else {
			$type = SMWDataValueFactory::newTypeIDValue('__typ');
			$type->setXSDValue($smwgPDefaultType);
			SMWDataValueFactory::$m_typebyproperty[$propertyname] = $type;
			$result = SMWDataValueFactory::newTypeIDValue($smwgPDefaultType,$value,$caption,$propertyname);
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
		global $smwgPDefaultType;
		$propertyname = $property->getText();
		if(array_key_exists($propertyname,SMWDataValueFactory::$m_typebyproperty)) { // use cache
			return SMWDataValueFactory::newTypeObjectValue(SMWDataValueFactory::$m_typebyproperty[$propertyname], $value, $caption, $propertyname);
		} // else: find type for property:

		$typearray = smwfGetStore()->getSpecialValues($property,SMW_SP_HAS_TYPE);
		if (count($typearray)==1) {
			SMWDataValueFactory::$m_typebyproperty[$propertyname] = current($typearray);
			$result = SMWDataValueFactory::newTypeObjectValue(SMWDataValueFactory::$m_typebyproperty[$propertyname], $value, $caption, $propertyname);
			return $result;
		} elseif (count($typearray)==0) {
			$type = SMWDataValueFactory::newTypeIDValue('__typ');
			$type->setXSDValue($smwgPDefaultType);
			SMWDataValueFactory::$m_typebyproperty[$propertyname] = $type;
			return SMWDataValueFactory::newTypeIDValue($smwgPDefaultType,$value,$caption,$propertyname);
		} else {
			wfLoadExtensionMessages('SemanticMediaWiki');
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
			case SMW_SP_DISPLAY_UNITS: case SMW_SP_SERVICE_LINK:
			case SMW_SP_CONVERSION_FACTOR: case SMW_SP_POSSIBLE_VALUE:
				$result = SMWDataValueFactory::newTypeIDValue('_str', $value, $caption);
				break;
			case SMW_SP_SUBPROPERTY_OF: 
				$result = SMWDataValueFactory::newTypeIDValue('_pro', $value, $caption);
				break;
			case SMW_SP_SUBCLASS_OF: case SMW_SP_REDIRECTS_TO: 
			case SMW_SP_INSTANCE_OF:
				$result = SMWDataValueFactory::newTypeIDValue('_wpg', $value, $caption);
				break;
			case SMW_SP_CONCEPT_DESC:
				$result = SMWDataValueFactory::newTypeIDValue('__con', $value, $caption);
				break;
			case SMW_SP_IMPORTED_FROM:
				$result = SMWDataValueFactory::newTypeIDValue('__imp', $value, $caption);
				break;
			default:
				/// NOTE: unstable hook, future versions might have better ways of enabling extensions to add properties
				wfRunHooks('smwNewSpecialValue', array($specialprop, $value, $caption, &$result));
				if (!isset($result)) { // special property was created but not added here; this is bad but we still are nice
					$result = SMWDataValueFactory::newTypeIDValue('_str', $value, $caption);
				}
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
		SMWDataValueFactory::initDatatypes();
		$typeid = $typevalue->getXSDValue();
		if (array_key_exists($typeid, SMWDataValueFactory::$m_typeclasses)) {
			$result = new SMWDataValueFactory::$m_typeclasses[$typeid]($typeid);
		} elseif (!$typevalue->isUnary()) { // n-ary type?
				$result = SMWDataValueFactory::newTypeIDValue('__nry');
				$result->setType($typevalue);
		} elseif (($typeid != '') && ($typeid{0} != '_')) { // custom type with linear conversion
			$result = new SMWDataValueFactory::$m_typeclasses['__lin']($typeid);
		} else { // type really unknown
			wfLoadExtensionMessages('SemanticMediaWiki');
			return new SMWErrorValue(wfMsgForContent('smw_unknowntype', $typevalue->getWikiValue() ), $value, $caption);
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
		SMWDataValueFactory::initDatatypes();
		if (array_key_exists($typeid, SMWDataValueFactory::$m_typeclasses)) {
			$result = new SMWDataValueFactory::$m_typeclasses[$typeid]($typeid);
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
		if ($property->getNamespace() != SMW_NS_PROPERTY) { // somebody made a mistake ...
			return false;
		}
		$propertyname = $property->getText();
		if (array_key_exists($propertyname, SMWDataValueFactory::$m_typebyproperty)) {
			if (SMWDataValueFactory::$m_typebyproperty[$propertyname]->isUnary() ) {
				return SMWDataValueFactory::$m_typebyproperty[$propertyname]->getXSDValue();
			} else {
				return '__nry';
			}
		} else {
			return SMWDataValueFactory::newPropertyObjectValue($property)->getTypeID(); // this also triggers caching
		}
	}

	/**
	 * Quickly get the type value of some property without necessarily making another datavalue.
	 * @note The internal error type id is returned if this method failed to find the type.
	 * @bug This method is not implemented efficiently.
	 * @todo This method is mainly used in the processing of multi-valued properties. Revise all its uses.
	 */
	static public function getPropertyObjectTypeValue(Title $property) {
		$propertyname = $property->getText();
		SMWDataValueFactory::newPropertyObjectValue($property);
		if (array_key_exists($propertyname, SMWDataValueFactory::$m_typebyproperty)) {
			return SMWDataValueFactory::$m_typebyproperty[$propertyname];
		} else { // no type found
			return new SMWTypesValue('__err');
		}
	}

	/**
	 * Signal the class that the type of some property has changed. Clearing this
	 * is crucial to let subsequent jobs work properly.
	 */
	static public function clearTypeCache(Title $property) {
		$propertyname = $property->getText();
		if (array_key_exists($propertyname, SMWDataValueFactory::$m_typebyproperty)) {
			unset(SMWDataValueFactory::$m_typebyproperty[$propertyname]);
		}
	}

	/**
	 * Gather all available datatypes and label<=>id<=>datatype associations. This method 
	 * is called before most methods of this factory.
	 */
	static protected function initDatatypes() {
		if (is_array(SMWDataValueFactory::$m_typelabels)) {
			return; //init happened before
		}

		global $smwgContLang, $smwgIP, $wgAutoloadClasses;
		SMWDataValueFactory::$m_typelabels = $smwgContLang->getDatatypeLabels();
		SMWDataValueFactory::$m_typealiases = $smwgContLang->getDatatypeAliases();
		// Setup built-in datatypes.
		// NOTE: all ids must start with underscores, where two underscores indicate
		// truly internal (non user-acessible types). All others should also get a
		// translation in the language files, or they won't be available for users.
		SMWDataValueFactory::$m_typeclasses = array(
			'_txt'  => 'SMWStringValue',
			'_cod'  => 'SMWStringValue',
			'_str'  => 'SMWStringValue',
			'_ema'  => 'SMWURIValue',
			'_uri'  => 'SMWURIValue',
			'_anu'  => 'SMWURIValue',
			'_wpg'  => 'SMWWikiPageValue',
			'_pro'  => 'SMWPropertyValue',
			'_num'  => 'SMWNumberValue',
			'_tem'  => 'SMWTemperatureValue',
			'_dat'  => 'SMWTimeValue',
			'_geo'  => 'SMWGeoCoordsValue',
			'_boo'  => 'SMWBoolValue',
			'__typ' => 'SMWTypesValue',
			'__lin' => 'SMWLinearValue',
			'__nry' => 'SMWNAryValue',
			'__err' => 'SMWErrorValue',
			'__con' => 'SMWConceptValue',
			'__imp'  => 'SMWImportValue',
		);

		wfRunHooks( 'smwInitDatatypes' );
	}

	/**
	 * A function for registering/overwriting datatypes for SMW. Should be called from 
	 * within the hook 'smwInitDatatypes'.
	 */
	static function registerDatatype($id, $classname, $label=false) {
		SMWDataValueFactory::$m_typeclasses[$id] = $classname;
		if ($label != false) {
			SMWDataValueFactory::$m_typelabels[$id] = $label;
		}
	}

	/**
	 * Add a new alias label to an existing datatype id. Note that every ID should have a primary
	 * label, either provided by SMW or registered with registerDatatype. This function should be 
	 * called from within the hook 'smwInitDatatypes'.
	 */
	static function registerDatatypeAlias($id, $label) {
		SMWDataValueFactory::$m_typealiases[$label] = $id;
	}

	/**
	 * Look up the ID that identifies the datatype of the given label internally.
	 * This id is used for all internal operations. Compound types are not supported
	 * by this method (decomposition happens earlier). Custom types get their DBkeyed 
	 * label as id. All ids are prefixed by an underscore in order to distinguish them 
	 * from custom types.
	 *
	 * This method may or may not take aliases into account. For unknown labels, the 
	 * normalised (DB-version) label is used as an ID.
	 */
	static public function findTypeID($label, $useAlias = true) {
		SMWDataValueFactory::initDatatypes();
		$id = array_search($label, SMWDataValueFactory::$m_typelabels);
		if ($id !== false) {
			return $id;
		} elseif ( ($useAlias) && (array_key_exists($label, SMWDataValueFactory::$m_typealiases)) ) {
			return SMWDataValueFactory::$m_typealiases[$label];
		} else {
			return str_replace(' ', '_', $label);
		}
	}

	/**
	 * Get the translated user label for a given internal ID. If the ID does
	 * not have a label associated with it in the current language, the ID itself
	 * is transformed into a label (appropriate for user defined types).
	 */
	static public function findTypeLabel($id) {
		SMWDataValueFactory::initDatatypes();
		if ($id{0} === '_') {
			if (array_key_exists($id, SMWDataValueFactory::$m_typelabels)) {
				return SMWDataValueFactory::$m_typelabels[$id];
			} else { //internal type without translation to user space; 
			    //might also happen for historic types after upgrade --
			    //alas, we have no idea what the former label would have been
				return str_replace('_', ' ', $id);
			}
		} else { // non-builtin type, use id as label
			return str_replace('_', ' ', $id);
		}
	}

	/**
	 * Return an array of all labels that a user might specify as the type of
	 * a property, and that are internal (i.e. not user defined). No labels are
	 * returned for internal types without user labels (e.g. the special types for
	 * some special properties), and for user defined types.
	 */
	static public function getKnownTypeLabels() {
		SMWDataValueFactory::initDatatypes();
		return SMWDataValueFactory::$m_typelabels;
	}

}

