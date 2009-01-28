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
 * - newPropertyObjectValue
 *
 * @ingroup SMWDataValues
 */
class SMWDataValueFactory {

	/// Array of type labels indexed by type ids. Used for datatype resolution.
	static private $m_typelabels;
	/// Array of ids indexed by type aliases. Used for datatype resolution.
	static private $m_typealiases;
	/// Array of class names for creating new SMWDataValues, indexed by type id.
	static private $m_typeclasses;

	/**
	 * Create an SMWDataValue object that can hold values for the type that the
	 * given SMWTypesValue object specifies. If no $value is given, an empty container
	 * is created, the value of which can be set later on.
	 * @param $typevalue SMWTypesValue object representing the type of the object
	 * @param $value user value string, or false if unknown
	 * @param $caption user-defined caption or false if none given
	 * @param $property SMWPropertyValue property object for which this value was made, or NULL
	 */
	static public function newTypeObjectValue(SMWTypesValue $typevalue, $value=false, $caption=false, $property=NULL) {
		if (!$typevalue->isValid()) { // just return the error, pass it through
			$result = SMWDataValueFactory::newTypeIDValue('__err');
			$result->addError($typevalue->getErrors());
			return $result;
		}
		SMWDataValueFactory::initDatatypes();
		$typeid = $typevalue->getDBkey();
		if (array_key_exists($typeid, SMWDataValueFactory::$m_typeclasses)) { // basic type
			$result = new SMWDataValueFactory::$m_typeclasses[$typeid]($typeid);
		} elseif (!$typevalue->isUnary()) { // n-ary type
				$result = new SMWDataValueFactory::$m_typeclasses['__nry']('__nry');
				$result->setType($typevalue);
		} elseif (($typeid != '') && ($typeid{0} != '_')) { // custom type with linear conversion
			$result = new SMWDataValueFactory::$m_typeclasses['__lin']($typeid);
		} else { // type really unknown
			wfLoadExtensionMessages('SemanticMediaWiki');
			return new SMWErrorValue(wfMsgForContent('smw_unknowntype', $typevalue->getWikiValue() ), $value, $caption);
		}
		if ($property !== NULL) $result->setProperty($property);
		if ($value !== false) $result->setUserValue($value,$caption);
		return $result;
	}

	/**
	 * Create a value from a type id. If no $value is given, an empty container is created, the
	 * value of which can be set later on. This function is mostly a shortcut that avoids some of
	 * the more complex processing required for SMWDataValueFactory::newTypeObjectValue().
	 * @param $typeid id string for the given type
	 * @param $value user value string, or false if unknown
	 * @param $caption user-defined caption or false if none given
	 * @param $property SMWPropertyValue property object for which this value was made, or NULL
	 */
	static public function newTypeIDValue($typeid, $value=false, $caption=false, $property=NULL) {
		SMWDataValueFactory::initDatatypes();
		if (array_key_exists($typeid, SMWDataValueFactory::$m_typeclasses)) { // direct response for basic types
			$result = new SMWDataValueFactory::$m_typeclasses[$typeid]($typeid);
			if ($property !== NULL) $result->setProperty($property);
			if ($value !== false) $result->setUserValue($value,$caption);
			return $result;
		} else { // create type value first (e.g. for n-ary type ids or user-defined types)
			$typevalue = new SMWTypesValue('__typ');
			$typevalue->setDBkeys(array($typeid));
			return SMWDataValueFactory::newTypeObjectValue($typevalue, $value, $caption, $property);
		}
	}

	/**
	 * Create a value for the given property, provided as an SMWPropertyValue object.
	 * If no value is given, an empty container is created, the value of which can be
	 * set later on.
	 */
	static public function newPropertyObjectValue(SMWPropertyValue $property, $value=false, $caption=false) {
		return SMWDataValueFactory::newTypeObjectValue($property->getTypesValue(), $value, $caption, $property);
	}

	/**
	 * Gather all available datatypes and label<=>id<=>datatype associations. This method
	 * is called before most methods of this factory.
	 */
	static protected function initDatatypes() {
		if (is_array(SMWDataValueFactory::$m_typelabels)) {
			return; //init happened before
		}

		global $smwgContLang;
		SMWDataValueFactory::$m_typelabels = $smwgContLang->getDatatypeLabels();
		SMWDataValueFactory::$m_typealiases = $smwgContLang->getDatatypeAliases();
		// Setup built-in datatypes.
		// NOTE: all ids must start with underscores, where two underscores indicate
		// truly internal (non user-acessible types). All others should also get a
		// translation in the language files, or they won't be available for users.
		SMWDataValueFactory::$m_typeclasses = array(
			'_txt'  => 'SMWStringValue', // Text type
			'_cod'  => 'SMWStringValue', // Code type
			'_str'  => 'SMWStringValue', // String type
			'_ema'  => 'SMWURIValue', // Email type
			'_uri'  => 'SMWURIValue', // URL/URI type
			'_anu'  => 'SMWURIValue', // Annotation URI type
			'_wpg'  => 'SMWWikiPageValue', // Page type
			'_wpp'  => 'SMWWikiPageValue', // Property page type TODO: make available to user space
			'_wpc'  => 'SMWWikiPageValue', // Category page type TODO: make available to user space
			'_wpf'  => 'SMWWikiPageValue', // Form page type for Semantic Forms
			'_num'  => 'SMWNumberValue', // Number type
			'_tem'  => 'SMWTemperatureValue', // Temperature type
			'_dat'  => 'SMWTimeValue', // Time type
			'_geo'  => 'SMWGeoCoordsValue', // Geographic coordinates type
			'_boo'  => 'SMWBoolValue', // Boolean type
			// Special types are not avaialble directly for users (and have no local language name):
			'__typ' => 'SMWTypesValue', // Special type page type
			'__con' => 'SMWConceptValue', // Special concept page type
			'__sps' => 'SMWStringValue', // Special string type
			'__spu' => 'SMWURIValue', // Special uri type
			'__sup' => 'SMWWikiPageValue', // Special subproperty type
			'__suc' => 'SMWWikiPageValue', // Special subcategory type
			'__spf' => 'SMWWikiPageValue', // Special Form page type for Semantic Forms
			'__sin' => 'SMWWikiPageValue', // Special instance of type
			'__red' => 'SMWWikiPageValue', // Special redirect type
			'__lin' => 'SMWLinearValue', // Special linear unit conversion type
			'__nry' => 'SMWNAryValue', // Special multi-valued type
			'__err' => 'SMWErrorValue', // Special error type
			'__imp' => 'SMWImportValue', // Special import vocabulary type
			'__pro' => 'SMWPropertyValue', // Property type (possibly predefined, no always based on a page)
		);

		wfRunHooks( 'smwInitDatatypes' );
	}

	/**
	 * A function for registering/overwriting datatypes for SMW. Should be called from
	 * within the hook 'smwInitDatatypes'.
	 */
	static public function registerDatatype($id, $classname, $label=false) {
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
	static public function registerDatatypeAlias($id, $label) {
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


	/**
	 * Create a value from a string supplied by a user for a given property.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 * @deprecated This function will vanish in SMW 1.5. Use SMWDataValueFactory::newPropertyObjectValue instead.
	 */
	static public function newPropertyValue($propertyname, $value=false, $caption=false) {
		return SMWDataValueFactory::newPropertyObjectValue(SMWPropertyValue::makeUserProperty($propertyname),$value,$caption);
	}


}

