<?php

require_once('SMW_DataValueFactory.php');

/**
 * Objects of this type represent all that is known about
 * a certain user-provided data value, especially its various
 * representations as strings, tooltips, numbers, etc.
 */
abstract class SMWDataValue {

	protected $m_attribute = false;

	/*********************************************************************/
	/* Static methods for initialisation                                 */
	/*********************************************************************/

	/**
	 * Create a value from a string supplied by a user for a given attribute.
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 * 
	 * @DEPRECATED
	 */
	static function newAttributeValue($attribute, $value=false) {
		trigger_error("The function SMWDataValue::newAttributeValue() is deprecated.", E_USER_NOTICE);
		return SMWDataValueFactory::newAttributeValue($attribute, $value);
	}

	/**
	 * Create a value from a string supplied by a user for a given special
	 * property, encoded as a numeric constant. 
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 *
	 * @DEPRECATED
	 */
	static function newSpecialValue($specialprop, $value=false) {
		trigger_error("The function SMWDataValue::newSpecialValue() is deprecated.", E_USER_NOTICE);
		return SMWDataValueFactory::newSpecialValue($specialprop, $value);
	}

	/**
	 * Create a value from a user-supplied string for which a type handler is known
	 * If no value is given, an empty container is created, the value of which
	 * can be set later on.
	 * 
	 * @DEPRECATED
	 */
	static function newTypedValue(SMWTypeHandler $type, $value=false) {
		trigger_error("The function SMWDataValue::newTypedValue() is deprecated.", E_USER_NOTICE);
		return SMWDataValueFactory::newTypeHandlerValue($type, $value);
	}
	
	/*********************************************************************/
	/* Legacy methods for compatiblity                                   */
	/*********************************************************************/

	/**
	 * @DEPRECATED
	 */
	public function getUserValue() {
		trigger_error("The function SMWDataValue::getUserValue() is deprecated.", E_USER_NOTICE);
		return $this->getShortWikiText();
	}
	
	/**
	 * @DEPRECATED
	 */
	public function getValueDescription() {
		trigger_error("The function SMWDataValue::getValueDescription() is deprecated.", E_USER_NOTICE);
		return $this->getLongWikiText();
	}
	
	/**
	 * @DEPRECATED
	 */
	public function getTooltip() {
		//trigger_error("The function SMWDataValue::getTooltip() is deprecated.", E_USER_NOTICE);
		return '';
	}

	/*********************************************************************/
	/* Set methods                                                       */
	/*********************************************************************/

	/**
	 * Set the user value (and compute other representations if possible).
	 * The given value is a string as supplied by some user.
	 */
	abstract public function setUserValue($value);

	/**
	 * Set the xsd value (and compute other representations if possible).
	 * The given value is a string that was provided by getXSDValue() (all
	 * implementations should support round-tripping).
	 */
	abstract public function setXSDValue($value, $unit);

	/**
	 * Set the attribute to which this value refers. Used to generate search links and
	 * to find custom settings that relate to the attribute.
	 * The attribute is given as a simple wiki text title, without namespace prefix.
	 */
	public function setAttribute($attstring) {
		$this->m_attribute = $attstring;
	}

	/**
	 * Define a particular output format. Output formats are user-supplied strings
	 * that the datavalue may (or may not) use to customise its return value. For
	 * example, quantities with units of measurement may interpret the string as 
	 * a desired output unit. In other cases, the output format might be built-in 
	 * and subject to internationalisation (which the datavalue has to implement).
	 * In any case, an empty string resets the output format to the default.
	 */
	abstract public function setOutputFormat($formatstring);

	/*********************************************************************/
	/* Get methods                                                       */
	/*********************************************************************/

	/**
	 * Returns a short textual representation for this data value. If the value
	 * was initialised from a user supplied string, then this original string
	 * should be reflected in this short version (i.e. no normalisation should
	 * normally happen). There might, however, be additional parts such as code
	 * for generating tooltips. The output is in wiki text.
	 *
	 * The parameter $linked controls linking of values such as titles and should
	 * be non-NULL and non-false if this is desired.
	 */
	abstract public function getShortWikiText($linked = NULL);

	/**
	 * Returns a short textual representation for this data value. If the value
	 * was initialised from a user supplied string, then this original string
	 * should be reflected in this short version (i.e. no normalisation should
	 * normally happen). There might, however, be additional parts such as code
	 * for generating tooltips. The output is in HTML text.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (or NULL for no linking).
	 */
	abstract public function getShortHTMLText($linker = NULL);

	/**
	 * Return the long textual description of the value, as printed for
	 * example in the factbox. If errors occurred, return the error message
	 * The result always is a wiki-source string.
	 *
	 * The parameter $linked controls linking of values such as titles and should
	 * be non-NULL and non-false if this is desired.
	 */
	abstract public function getLongWikiText($linked = NULL);

	/**
	 * Return the long textual description of the value, as printed for
	 * example in the factbox. If errors occurred, return the error message
	 * The result always is an HTML string.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (or NULL for no linking).
	 */
	abstract public function getLongHTMLText($linker = NULL);

	/**
	 * Return the XSD compliant version of the value, or
	 * FALSE if parsing the value failed and no XSD version
	 * is available. If the datatype has units, then this
	 * value is given in the unit provided by getUnit().
	 */
	abstract public function getXSDValue();

	/**
	 * Return the plain wiki version of the value, or
	 * FALSE if no such version is available. The returned
	 * string suffices to reobtain the same DataValue 
	 * when passing it as an input string to setUserValue().
	 * Thus it also includes units, if any.
	 */
	abstract public function getWikiValue();

	/**
	 * Return the numeric representation of the value, or FALSE
	 * is none is available. This representation is used to
	 * compare values of scalar types more efficiently, especially
	 * for sorting queries. If the datatype has units, then this
	 * value is to be interpreted wrt. the unit provided by getUnit().
	 */
	abstract public function getNumericValue();

	/**
	 * Return the unit in which the returned value is to be interpreted.
	 * This string is a plain UTF-8 string without wiki or html markup.
	 * Returns the empty string if no unit is given for the value.
	 */
	abstract public function getUnit();

	/**
	 * Return error string or an empty string if no error occured.
	 */
	abstract public function getError();

	/**
	 * Return a short string that unambiguously specify the type of this value.
	 * This value will globally be used to identify the type of a value (in spite
	 * of the class it actually belongs to, which can still implement various types).
	 */
	abstract public function getTypeID();

	/**
	 * Return an array of SMWLink objects that provide additional resources
	 * for the given value.
	 * Captions can contain some HTML markup which is admissible for wiki
	 * text, but no more. Result might have no entries but is always an array.
	 */
	abstract public function getInfolinks();

	/**
	 * Return a string that identifies the value of the object, and that can
	 * be used to compare different value objects.
	 */
	abstract public function getHash();

	/**
	 * Return TRUE if a value was defined and understood by the given type,
	 * and false if parsing errors occured or no value was given.
	 */
	abstract public function isValid();

	/**
	 * Return TRUE if values of the given type generally have a numeric version.
	 */
	abstract public function isNumeric();

}


