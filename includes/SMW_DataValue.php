<?php

require_once('SMW_DataValueFactory.php');

/**
 * Objects of this type represent all that is known about
 * a certain user-provided data value, especially its various
 * representations as strings, tooltips, numbers, etc.
 */
abstract class SMWDataValue {

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
		return SMWDataValueFactory::newTypeHandlerValue($type, $value);
	}
	
	/*********************************************************************/
	/* Legacy methods for compatiblity                                   */
	/*********************************************************************/

	/**
	 * @DEPRECATED
	 */
	public function getUserValue() {
		return $this->getShortWikiText();
	}
	
	/**
	 * @DEPRECATED
	 */
	public function getValueDescription() {
		return $this->getLongWikiText();
	}
	
	/**
	 * @DEPRECATED
	 */
	public function getTooltip() {
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
	 * Set the attribute to which this value refers. Used to generate search links.
	 * The atriubte is given as a simple wiki text title, without namespace prefix.
	 */
	abstract public function setAttribute($attribute);

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
	 * Return the numeric representation of the value, or NULL
	 * is none is available. This representation is used to
	 * compare values of scalar types more efficiently, especially
	 * for sorting queries. If the datatype has units, then this
	 * value is to be interpreted wrt. the unit provided by getUnit().
	 */
	abstract public function getNumericValue();

	/**
	 * Return the unit in which the returned value is to be interpreted.
	 * This string is a plain UTF-8 string without wiki or html markup.
	 * Returns FALSE if no unit is given for the value.
	 */
	abstract public function getUnit();

	/**
	 * Return error string or an empty string if no error occured.
	 */
	abstract public function getError();

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

?>
