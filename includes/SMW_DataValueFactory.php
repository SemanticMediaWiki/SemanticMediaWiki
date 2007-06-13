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
	 * Cache for type labels, indexed by attribute name (both without namespace prefix).
	 */
	static private $m_attributelabels = array('Testnary' => 'String;Integer;Wikipage;Date'); ///DEBUG

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
		if(!array_key_exists($attstring,SMWDataValueFactory::$m_attributelabels)) {
			$atitle = Title::newFromText($attstring, SMW_NS_ATTRIBUTE);
			if ($atitle !== NULL) {
				$typearray = smwfGetStore()->getSpecialValues($atitle,SMW_SP_HAS_TYPE);
			} else { $typearray = Array(); }

			if (count($typearray)==1) {
				SMWDataValueFactory::$m_attributelabels[$attstring] = $typearray[0];
			} elseif (count($typearray)==0) {
				///TODO
				return new SMWOldDataValue(new SMWErrorTypeHandler(wfMsgForContent('smw_notype')));
			} else {
				///TODO
				return new SMWOldDataValue(new SMWErrorTypeHandler(wfMsgForContent('smw_manytypes')));
			}
		}
		return SMWDataValueFactory::newTypedValue(SMWDataValueFactory::$m_attributelabels[$attstring], $value);
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
	 * Create a value from a user-supplied string for which only a type is known
	 * (given as a string name without namespace prefix).
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 */
	static public function newTypedValue($typestring, $value=false) {
		if (array_key_exists($typestring, SMWDataValueFactory::$m_valueclasses)) {
			$vc = SMWDataValueFactory::$m_valueclasses[$typestring];
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
			return new $dv[2]($dv[3]);
		} else {
			// check for n-ary types
			$types = explode(';', $typestring);
			if (count($types)>1) {
				if (SMWDataValueFactory::$m_naryincluded == false) {
					global $smwgIP;
					include_once($smwgIP . '/includes/SMW_DV_NAry.php');
					SMWDataValueFactory::$m_naryincluded = true;
				}
				return new SMWNAryValue($types, $value);
			} else {
				///TODO
				$type = SMWTypeHandlerFactory::getTypeHandlerByLabel($typestring);
				$result = new SMWOldDataValue($type);
				if ($value !== false) {
					$result->setUserValue($value);
				}
				return $result;
			}
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

?>