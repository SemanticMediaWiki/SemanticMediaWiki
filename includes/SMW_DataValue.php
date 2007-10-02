<?php

/**
 * Objects of this type represent all that is known about
 * a certain user-provided data value, especially its various
 * representations as strings, tooltips, numbers, etc.
 */
abstract class SMWDataValue {

	protected $m_property = false; /// The text label of the respective property or false if none given
	protected $m_caption = false;   /// The text label to be used for output or false if none given
	protected $m_errors = array();  /// Array of error text messages
	protected $m_isset = false;     /// True if a value was set.
	protected $m_typeid;            /// The type id for this value object
	protected $m_infolinks = array(); /// Array of infolink objects

	private $m_hasssearchlink;    /// used to control the addition of the standard search link

	public function SMWDataValue($typeid) {
		$this->m_typeid = $typeid;
	}

///// Set methods /////

	/**
	 * Set the user value (and compute other representations if possible).
	 * The given value is a string as supplied by some user. An alternative
	 * label for printout might also be specified.
	 */
	public function setUserValue($value, $caption = false) {
		wfProfileIn('SMWDataValue::setUserValue (SMW)');
		$this->m_errors = array(); // clear errors
		$this->m_infolinks = array(); // clear links
		$this->m_hasssearchlink = false;
		if ($caption !== false) {
			$this->m_caption = $caption;
		}
		$this->parseUserValue($value); // may set caption if not set yet, depending on datavalue
		$this->m_isset = true;
		wfProfileOut('SMWDataValue::setUserValue (SMW)');
	}

	/**
	 * Set the xsd value (and compute other representations if possible).
	 * The given value is a string that was provided by getXSDValue() (all
	 * implementations should support round-tripping).
	 */
	public function setXSDValue($value, $unit = '') {
		wfProfileIn('SMWDataValue::setXSDValue (SMW)');
		$this->m_errors = array(); // clear errors
		$this->m_infolinks = array(); // clear links
		$this->m_hasssearchlink = false;
		$this->m_caption = false;
		$this->parseXSDValue($value, $unit);
		$this->m_isset = true;
		wfProfileOut('SMWDataValue::setXSDValue (SMW)');
	}

	/**
	 * Set the property to which this value refers. Used to generate search links and
	 * to find custom settings that relate to the property.
	 * The property is given as a simple wiki text title, without namespace prefix.
	 */
	public function setProperty($propertyname) {
		$this->m_property = $propertyname;
	}

	public function addInfoLink(SMWInfoLink $link) {
		$this->m_infolinks[] = $link;
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

	/**
	 * Add a new error string to the error list. All error string must be wiki and
	 * html-safe! No further escaping will happen!
	 */
	public function addError($errorstring) {
		$this->m_errors[] = $errorstring;
	}

///// Abstract processing methods /////

	/**
	 * Initialise the datavalue from the given value string.
	 * The format of this strings might be any acceptable user input
	 * and especially includes the output of getWikiValue().
	 */
	abstract protected function parseUserValue($value);

	/**
	 * Initialise the datavalue from the given value string and unit.
	 * The format of both strings strictly corresponds to the output
	 * of this implementation for getXSDValue() and getUnit().
	 */
	abstract protected function parseXSDValue($value, $unit);

///// Get methods /////

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
	 * Return a short string that unambiguously specify the type of this value.
	 * This value will globally be used to identify the type of a value (in spite
	 * of the class it actually belongs to, which can still implement various types).
	 */
	public function getTypeID() {
		return $this->m_typeid;
	}

	/**
	 * Return an array of SMWLink objects that provide additional resources
	 * for the given value.
	 * Captions can contain some HTML markup which is admissible for wiki
	 * text, but no more. Result might have no entries but is always an array.
	 */
	public function getInfolinks() {
		global $smwgIP;
		include_once($smwgIP . '/includes/SMW_Infolink.php');
		if (!$this->m_hasssearchlink && $this->isValid() && $this->m_property) {
			$this->m_hasssearchlink = true;
			$this->m_infolinks[] = SMWInfolink::newPropertySearchLink('+', $this->m_property, $this->getWikiValue());
		}
		return $this->m_infolinks;
	}

	/**
	 * Return a string that identifies the value of the object, and that can
	 * be used to compare different value objects.
	 */
	abstract public function getHash();

	/**
	 * Return TRUE if values of the given type generally have a numeric version.
	 */
	abstract public function isNumeric();

	/**
	 * Return TRUE if a value was defined and understood by the given type,
	 * and false if parsing errors occured or no value was given.
	 */
	public function isValid() {
		return ( (count($this->m_errors) == 0) && $this->m_isset );
	}

	/**
	 * Return a string that displays all error messages as a tooltip, or
	 * an empty string if no errors happened.
	 */
	public function getErrorText() {
		return smwfEncodeMessages($this->m_errors);
	}

	/**
	 * Return an array of error messages, or an empty array
	 * if no errors occurred.
	 */
	public function getErrors() {
		return $this->m_errors;
	}
	
	/**
	 * Exports the datavalue to RDF (i.e. it returns a string that consists
	 * of the lines that, in RDF/XML, can be fitted between the object-tags.
	 * This should be overwritten.
	 * QName -- the qualified name that the data value should use for exporting,
	 * since it may be an imported name.
	 * Exporter -- the exporting object
	 * TODO make it an abstract function? Not sure.
	 */
	public function exportToRDF($QName, ExportRDF $exporter) {
		$type = $this->getTypeID();
		return "\t\t<!-- Sorry, unknown how to export type '$type'. -->\n";
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
	 * Return error string or an empty string if no error occured.
	 * @DEPRECATED
	 */
	public function getError() {
		trigger_error("getError is no longer available. Use getErrorText or getErrors.", E_USER_NOTICE);
	}

}


