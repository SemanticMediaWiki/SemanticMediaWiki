<?php

require_once('SMW_DataValue.php');
require_once('SMW_OldDataValue.php');

/**
 * Factory class for creating SMWDataValue objects for supplied types or attributes
 * and data values.
 */
class SMWDataValueFactory {

	/**
	 * Array of class names and initialisation data for creating
	 * new SMWDataValues. Indexed by type label (without namespace).
	 * Each entry has the form 
	 *        array(included?, filepart, classname, parameters = NULL);
	 */
	static private $m_valueclasses = array();
	
	/**
	 * Cache for type specifications (type datavalues), indexed by attribute name (both without namespace prefix).
	 */
	static private $m_typelabels = array();

	/**
	 * Cache for type ids, indexed by attribute name (without namespace prefix).
	 */
	static private $m_typeids = array();

	/**
	 * Was code for handling n-ary properties already included?
	 */
	static private $m_naryincluded = false;

	/**
	 * Create a value from a string supplied by a user for a given attribute.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newAttributeValue($attstring, $value=false) {
		if(!array_key_exists($attstring,SMWDataValueFactory::$m_typelabels)) {
			$atitle = Title::newFromText($attstring, SMW_NS_ATTRIBUTE);
			if ($atitle !== NULL) {
				return SMWDataValueFactory::newAttributeObjectValue($atitle,$value);
			} else {
				return new SMWOldDataValue(new SMWErrorTypeHandler(wfMsgForContent('smw_notype')));
			}
		}
		return SMWDataValueFactory::newTypedValue(SMWDataValueFactory::$m_typelabels[$attstring], $value);
	}

	/**
	 * Create a value from a string supplied by a user for a given attribute title.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newAttributeObjectValue(Title $att, $value=false) {
		$attstring = $att->getText();
		SMWDataValueFactory::$m_typelabels['Testnary'] = SMWDataValueFactory::newSpecialValue(SMW_SP_HAS_TYPE, 'String;Integer;Wikipage;Date'); /// DEBUG
		if(!array_key_exists($attstring,SMWDataValueFactory::$m_typelabels)) {
			$typearray = smwfGetStore()->getSpecialValues($att,SMW_SP_HAS_TYPE);
			if (count($typearray)==1) {
				SMWDataValueFactory::$m_typelabels[$attstring] = $typearray[0];
				$result = SMWDataValueFactory::newTypedValue(SMWDataValueFactory::$m_typelabels[$attstring], $value);
				SMWDataValueFactory::$m_typeids[$attstring] = $result->getTypeID(); // also cache typeid
				return $result;
			} elseif (count($typearray)==0) {
				///TODO
				return new SMWOldDataValue(new SMWErrorTypeHandler(wfMsgForContent('smw_notype')));
			} else {
				///TODO
				return new SMWOldDataValue(new SMWErrorTypeHandler(wfMsgForContent('smw_manytypes')));
			}
		}
		return SMWDataValueFactory::newTypedValue(SMWDataValueFactory::$m_typelabels[$attstring], $value);
	}

	/**
	 * Create a value from a string supplied by a user for a given special
	 * property, encoded as a numeric constant.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newSpecialValue($specialprop, $value=false) {
		///TODO
		switch ($specialprop) {
			case SMW_SP_HAS_TYPE:
				global $smwgIP;
				include_once($smwgIP . '/includes/SMW_DV_Types.php');
				$result = new SMWTypesValue();
				break;
			///TODO:
			case SMW_SP_HAS_URI:
				//return new SMWURITypeHandler(SMW_URI_MODE_URI);
			case SMW_SP_MAIN_DISPLAY_UNIT: case SMW_SP_DISPLAY_UNIT: case SMW_SP_SERVICE_LINK:
				//return new SMWStringTypeHandler();
			case SMW_SP_CONVERSION_FACTOR: case SMW_SP_POSSIBLE_VALUE:
				//return new SMWStringTypeHandler();
			case SMW_SP_CONVERSION_FACTOR_SI:
				//return new SMWStringTypeHandler(); // TODO: change this into an appropriate handler
			default:
				//global $smwgContLang;
				//$specprops = $smwgContLang->getSpecialPropertiesArray();
				//return new SMWErrorTypeHandler(wfMsgForContent('smw_noattribspecial',$specprops[$special]));
				$type = SMWTypeHandlerFactory::getSpecialTypeHandler($specialprop);
				$result = new SMWOldDataValue($type);
		}

		if ($value !== false) {
			$result->setUserValue($value);
		}
		return $result;
	}

	/**
	 * Create a value from a type value (basically containing strings).
	 * If no $value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newTypedValue(SMWDataValue $typevalue, $value=false) {
		if (array_key_exists($typevalue->getWikiValue(), SMWDataValueFactory::$m_valueclasses)) {
			$vc = SMWDataValueFactory::$m_valueclasses[$typevalue->getWikiValue()];
			// check if class file was already included for this class
			if ($vc[0] == false) {
				global $smwgIP;
				if (file_exists($smwgIP . '/includes/SMW_DV_'. $vc[1] . '.php')) {
					include_once($smwgIP . '/includes/SMW_DV_'. $vc[1] . '.php');
				} else {
					///TODO: return SMWErrorValue if available
					//return new SMWErrorTypeHandler(wfMsgForContent('smw_unknowntype',$typelabel));
					return NULL;
				}
				$vc[0] = true;
			}
			$result = new $vc[2]($vc[3]);
		} else {
			// check for n-ary types
			if (count($typevalue->getTypeLabels())>1) {
				if (SMWDataValueFactory::$m_naryincluded == false) {
					global $smwgIP;
					include_once($smwgIP . '/includes/SMW_DV_NAry.php');
					SMWDataValueFactory::$m_naryincluded = true;
				}
				return new SMWNAryValue($typevalue, $value);
			} else {
				///TODO migrate to new system
				$type = SMWTypeHandlerFactory::getTypeHandlerByLabel($typevalue->getWikiValue());
				$result = new SMWOldDataValue($type);
			}
		}

		if ($value !== false) {
			$result->setUserValue($value);
		}
		return $result;
	}

	/**
	 * Quickly get the type id of some attribute without necessarily making another datavalue.
	 */
	static public function getAttributeObjectTypeID(Title $att) {
		$attstring = $att->getText();
		if (array_key_exists($attstring, SMWDataValueFactory::$m_typeids)) {
			return SMWDataValueFactory::$m_typeids[$attstring];
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
	static public function registerDataValueClass($typestring, $filepart, $classname, $param = NULL) {
		SMWDataValueFactory::$m_valueclasses[$typestring] = array(false,$filepart,$classname,$param);
	}

}

