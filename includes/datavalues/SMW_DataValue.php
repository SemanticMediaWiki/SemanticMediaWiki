<?php

/**
 * This group contains all parts of SMW that relate to the processing of datavalues
 * of various types.
 *
 * @defgroup SMWDataValues SMWDataValues
 * @ingroup SMW
 */
use SMW\ApplicationFactory;
use SMW\DataValues\InfoLinksProvider;
use SMW\DataValues\ValueFormatterRegistry;
use SMW\DataValues\ValueValidatorRegistry;
use SMW\Deserializers\DVDescriptionDeserializerRegistry;
use SMW\Localizer;
use SMW\Message;
use SMW\Options;
use SMW\Query\QueryComparator;

/**
 * Objects of this type represent all that is known about a certain user-provided
 * data value, especially its various representations as strings, tooltips,
 * numbers, etc.  Objects can be created as "emtpy" containers of a certain type,
 * but are then usually filled with data to present one particular data value.
 *
 * Data values have two chief representation forms: the user-facing syntax and the
 * internal representation. In user syntax, every value is (necessarily) a single
 * string, however complex the value is. For example, a string such as "Help:editing"
 * may represent a wiki page called "Editing" in the namespace for "Help". The
 * internal representation may be any numerical array of strings and numbers. In the
 * example, it might be array("Editing",12), where 12 is the number used for identifying
 * the namespace "Help:". Of course, the internal representation could also use a single
 * string value, such as in array("Help:Editing"), but this might be less useful for
 * certain operations (e.g. filterng by namespace). Moreover, all values that are
 * restored from the database are given in the internal format, so it wise to choose a
 * format that allows for very fast and easy processing without unnecessary parsing.
 *
 * The main functions of data value objects are:
 * - setUserValue() which triggers parseUserValue() to process a user-level string.
 *
 * In addition, there are a number of get-functions that provide useful output versions
 * for displaying and serializing the value.
 *
 * @ingroup SMWDataValues
 *
 * @author Markus Krötzsch
 */
abstract class SMWDataValue {

	const OPT_USER_LANGUAGE = 'user.language';
	const OPT_CONTENT_LANGUAGE = 'content.language';

	/**
	 * Associated data item. This is the reference to the immutable object
	 * that represents the current data content. All other data stored here
	 * is only about presentation and parsing, but is not relevant to the
	 * actual data that is represented (and stored later on).
	 *
	 * This variable must always be set to some data item, even if there
	 * have been errors in initialising the data.
	 * @var SMWDataItem
	 */
	protected $m_dataitem;

	/**
	 * The property for which this value is constructed or null if none
	 * given. Property pages are used to make settings that affect parsing
	 * and display, hence it is sometimes needed to know them.
	 *
	 * @var SMWDIProperty
	 */
	protected $m_property = null;

	/**
	 * Wiki page in the context of which the value is to be interpreted, or
	 * null if not given (or not on a page). This information is used to
	 * parse user values such as "#subsection" which only make sense when
	 * used on a certain page.
	 *
	 * @var SMWDIWikiPage
	 */
	protected $m_contextPage = null;

	/**
	 * The text label to be used for output or false if none given.
	 * @var string
	 */
	protected $m_caption;

	/**
	 * The type id for this value object.
	 * @var string
	 */
	protected $m_typeid;

	/**
	 * Output formatting string, false when not set.
	 * @see setOutputFormat()
	 * @var mixed
	 */
	protected $m_outformat = false;

	/**
	 * Array of error text messages. Private to allow us to track error insertion
	 * (PHP's count() is too slow when called often) by using $mHasErrors.
	 * @var array
	 */
	private $mErrors = array();

	/**
	 * Boolean indicating if there where any errors.
	 * Should be modified accordingly when modifying $mErrors.
	 * @var boolean
	 */
	private $mHasErrors = false;

	/**
	 * Extraneous services and object container
	 *
	 * @var array
	 */
	private $extraneousFunctions = array();

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var boolean
	 */
	protected $approximateValue = false;

	/**
	 * @var InfoLinksProvider
	 */
	private $infoLinksProvider = null;

	/**
	 * Constructor.
	 *
	 * @param string $typeid
	 */
	public function __construct( $typeid ) {
		$this->m_typeid = $typeid;
	}

///// Set methods /////

	/**
	 * Set the user value (and compute other representations if possible).
	 * The given value is a string as supplied by some user. An alternative
	 * label for printout might also be specified.
	 *
	 * The third argument was added in SMW 1.9 and should not be used from outside SMW.
	 *
	 * @param string $value
	 * @param mixed $caption
	 * @param boolean $approximateValue
	 */
	public function setUserValue( $value, $caption = false, $approximateValue = false ) {

		$this->m_dataitem = null;
		$this->mErrors = array(); // clear errors
		$this->mHasErrors = false;
		$this->getInfoLinksProvider()->init();
		$this->m_caption = is_string( $caption ) ? trim( $caption ) : false;
		$this->approximateValue = $approximateValue;


		$this->parseUserValue( $value ); // may set caption if not set yet, depending on datavalue

		// The following checks for Strip markers generated by MediaWiki to handle special content,
		// from parser and extension tags e.g. <pre>,<nowiki>,<math>,<source>.
		// See https://en.wikipedia.org/wiki/Help:Strip_markers
		// In general, we are not prepared to handle such content properly, and we
		// also have no means of obtaining the user input at this point. Hence the assignment
		// just fails, even if parseUserValue() above might not have noticed this issue.
		// Note: \x07 was used in MediaWiki 1.11.0, \x7f is used now (backwards compatiblity, b/c)
		if ( ( strpos( $value, "\x7f" ) !== false ) || ( strpos( $value, "\x07" ) !== false ) ) {
			$this->addError( wfMessage( 'smw_parseerror' )->inContentLanguage()->text() );
		}

		if ( $this->isValid() && !$approximateValue ) {
			$this->checkAllowedValues();
		}

	}

	/**
	 * Set the actual data contained in this object. The method returns
	 * true if this was successful (requiring the type of the dataitem
	 * to match the data value). If false is returned, the data value is
	 * left unchanged (the data item was rejected).
	 *
	 * @note Even if this function returns true, the data value object
	 * might become invalid if the content of the data item caused errors
	 * in spite of it being of the right basic type. False is only returned
	 * if the data item is fundamentally incompatible with the data value.
	 *
	 * @param $dataitem SMWDataItem
	 * @return boolean
	 */
	public function setDataItem( SMWDataItem $dataItem ) {
		$this->getInfoLinksProvider()->init();
		$this->m_dataitem = null;
		$this->mErrors = array();
		$this->mHasErrors = $this->m_caption = false;
		return $this->loadDataItem( $dataItem );
	}

	/**
	 * Specify the property to which this value refers. Property pages are
	 * used to make settings that affect parsing and display, hence it is
	 * sometimes needed to know them.
	 *
	 * @since 1.6
	 *
	 * @param SMWDIProperty $property
	 */
	public function setProperty( SMWDIProperty $property ) {
		$this->m_property = $property;
	}

	/**
	 * Returns the property to which this value refers.
	 *
	 * @since 1.8
	 *
	 * @return SMWDIProperty|null
	 */
	public function getProperty() {
		return $this->m_property;
	}

	/**
	 * Specify the wiki page to which this value refers. This information is
	 * used to parse user values such as "#subsection" which only make sense
	 * when used on a certain page.
	 *
	 * @since 1.7
	 *
	 * @param SMWDIWikiPage|null $contextPage
	 */
	public function setContextPage( SMWDIWikiPage $contextPage = null ) {
		$this->m_contextPage = $contextPage;

		$this->setOption(
			self::OPT_CONTENT_LANGUAGE,
			Localizer::getInstance()->getPreferredContentLanguage( $contextPage )->getCode()
		);
	}

	/**
	 * @since 2.4
	 *
	 * @return DIWikiPage|null
	 */
	public function getContextPage() {
		return $this->m_contextPage;
	}

	/**
	 * @since 2.4
	 *
	 * @return Options $options
	 */
	public function setOptions( Options $options ) {
		foreach ( $options->getOptions() as $key => $value ) {
			$this->setOption( $key, $value );
		}
	}

	/**
	 * @since 2.4
	 *
	 * @return string $key
	 * @param mxied $value
	 */
	public function setOption( $key, $value ) {

		if ( $this->options === null ) {
			$this->options = new Options();
		}

		$this->options->set( $key, $value );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 *
	 * @return mixed|false
	 */
	public function getOptionValueFor( $key ) {

		if ( $this->options !== null && $this->options->has( $key ) ) {
			return $this->options->get( $key );
		}

		return false;
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $feature
	 *
	 * @return boolean
	 */
	public function isEnabledFeature( $feature ) {
		return ( $this->getOptionValueFor( 'smwgDVFeatures' ) & $feature ) != 0;
	}

	/**
	 * Change the caption (the text used for displaying this datavalue). The given
	 * value must be a string.
	 *
	 * @param string $caption
	 */
	public function setCaption( $caption ) {
		$this->m_caption = $caption;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $caption
	 */
	public function getCaption() {
		return $this->m_caption;
	}

	/**
	 * Returns a preferred caption and may deviate from the standard caption as
	 * a subclass is permitted to override this method and provide a more
	 * contextualized display representation (language or value context etc.).
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getPreferredCaption() {
		return $this->m_caption;
	}

	/**
	 * Adds a single SMWInfolink object to the m_infolinks array.
	 *
	 * @param SMWInfolink $link
	 */
	public function addInfolink( SMWInfolink $link ) {
		$this->getInfoLinksProvider()->addInfolink( $link );
	}

	/**
	 * Define a particular output format. Output formats are user-supplied strings
	 * that the datavalue may (or may not) use to customise its return value. For
	 * example, quantities with units of measurement may interpret the string as
	 * a desired output unit. In other cases, the output format might be built-in
	 * and subject to internationalisation (which the datavalue has to implement).
	 * In any case, an empty string resets the output format to the default.
	 *
	 * There is one predefined output format that all datavalues should respect: the
	 * format '-' indicates "plain" output that is most useful for further processing
	 * the value in a template. It should not use any wiki markup or beautification,
	 * and it should also avoid localization to the current language. When users
	 * explicitly specify an empty format string in a query, it is normalized to "-"
	 * to avoid confusion. Note that empty format strings are not interpreted in
	 * this way when directly passed to this function.
	 *
	 * @param string $formatString
	 */
	public function setOutputFormat( $formatString ) {
		$this->m_outformat = $formatString; // just store it, subclasses may or may not use this
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getOutputFormat() {
		return $this->m_outformat;
	}

	/**
	 * Add a new error string or array of such strings to the error list.
	 *
	 * @note Errors should not be escaped here in any way, in contradiction to what
	 * the docs used to say here in 1.5 and before. Escaping should happen at the output.
	 *
	 * @param mixed $error A single string, or array of strings.
	 */
	public function addError( $error ) {
		if ( is_array( $error ) ) {
			$this->mErrors = array_merge( $this->mErrors, $error );
			$this->mHasErrors = $this->mHasErrors || ( count( $error ) > 0 );
		} else {
			$this->mErrors[] = $error;
			$this->mHasErrors = true;
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param $parameters
	 * @param integer|null $type
	 * @param integer|null $language
	 */
	public function addErrorMsg( $parameters, $type = null, $language = null ) {
		$this->addError( Message::get( $parameters, $type, $language ) );
	}

	/**
	 * @since 2.4
	 */
	public function clearErrors() {
		$this->mErrors = array();
		$this->mHasErrors = false;
	}

///// Abstract processing methods /////

	/**
	 * Initialise the datavalue from the given value string.
	 * The format of this strings might be any acceptable user input
	 * and especially includes the output of getWikiValue().
	 *
	 * @param string $value
	 */
	abstract protected function parseUserValue( $value );

	/**
	 * Set the actual data contained in this object. The method returns
	 * true if this was successful (requiring the type of the dataitem
	 * to match the data value). If false is returned, the data value is
	 * left unchanged (the data item was rejected).
	 *
	 * @note Even if this function returns true, the data value object
	 * might become invalid if the content of the data item caused errors
	 * in spite of it being of the right basic type. False is only returned
	 * if the data item is fundamentally incompatible with the data value.
	 *
	 * @since 1.6
	 *
	 * @param SMWDataItem $dataItem
	 *
	 * @return boolean
	 */
	abstract protected function loadDataItem( SMWDataItem $dataItem );


///// Query support /////

	/**
	 * @see DataValueDescriptionDeserializer::deserialize
	 *
	 * @note Descriptions of values need to know their property to be able to
	 * create a parsable wikitext version of a query condition again. Thus it
	 * might be necessary to call setProperty() before using this method.
	 *
	 * @param string $value
	 *
	 * @return Description
	 * @throws InvalidArgumentException
	 */
	public function getQueryDescription( $value ) {

		$descriptionDeserializer = DVDescriptionDeserializerRegistry::getInstance()->getDescriptionDeserializerFor( $this );
		$description = $descriptionDeserializer->deserialize( $value );

		foreach ( $descriptionDeserializer->getErrors() as $error ) {
			$this->addError( $error );
		}

		return $description;
	}

	/**
	 * Returns a DataValueFormatter that was matched and dispatched for the current
	 * DV instance.
	 *
	 * @since 2.4
	 *
	 * @return DataValueFormatter
	 */
	public function getDataValueFormatter() {
		return ValueFormatterRegistry::getInstance()->getDataValueFormatterFor( $this );
	}

	/**
	 * @since 2.4
	 *
	 * @return PropertySpecificationLookup
	 */
	public function getPropertySpecificationLookup() {
		return ApplicationFactory::getInstance()->getPropertySpecificationLookup();
	}

	/**
	 * @deprecated 2.3
	 * @see DescriptionDeserializer::prepareValue
	 *
	 * This method should no longer be used for direct public access, instead a
	 * DataValue is expected to register a DescriptionDeserializer with
	 * DVDescriptionDeserializerRegistry.
	 *
	 * FIXME as of 2.3, SMGeoCoordsValue still uses this method and requires
	 * migration before 3.0
	 */
	static public function prepareValue( &$value, &$comparator ) {
		$comparator = QueryComparator::getInstance()->extractComparatorFromString( $value );
	}

///// Get methods /////

	/**
	 * Get the actual data contained in this object or null if the data is
	 * not defined (due to errors or due to not being set at all).
	 * @note Most implementations ensure that a data item is always set,
	 * even if errors occurred, to avoid additional checks for not
	 * accessing null. Hence, one must not assume that a non-null return
	 * value here implies that isValid() returns true.
	 *
	 * @since 1.6
	 *
	 * @return SMWDataItem|SMWDIError
	 */
	public function getDataItem() {

		if ( $this->isValid() ) {
			return $this->m_dataitem;
		}

		return new SMWDIError( $this->mErrors );
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->getDataItem()->getSerialization();
	}

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
	abstract public function getShortWikiText( $linked = null );

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
	abstract public function getShortHTMLText( $linker = null );

	/**
	 * Return the long textual description of the value, as printed for
	 * example in the factbox. If errors occurred, return the error message
	 * The result always is a wiki-source string.
	 *
	 * The parameter $linked controls linking of values such as titles and should
	 * be non-NULL and non-false if this is desired.
	 */
	abstract public function getLongWikiText( $linked = null );

	/**
	 * Return the long textual description of the value, as printed for
	 * example in the factbox. If errors occurred, return the error message
	 * The result always is an HTML string.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (or NULL for no linking).
	 */
	abstract public function getLongHTMLText( $linker = null );

	/**
	 * Returns a short textual representation for this data value. If the value
	 * was initialised from a user supplied string, then this original string
	 * should be reflected in this short version (i.e. no normalisation should
	 * normally happen). There might, however, be additional parts such as code
	 * for generating tooltips. The output is in the specified format.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (for HTML output), or NULL for no linking.
	 */
	public function getShortText( $outputformat, $linker = null ) {
		switch ( $outputformat ) {
			case SMW_OUTPUT_WIKI:
				return $this->getShortWikiText( $linker );
			case SMW_OUTPUT_HTML:
			case SMW_OUTPUT_FILE:
			default:
				return $this->getShortHTMLText( $linker );
		}
	}

	/**
	 * Return the long textual description of the value, as printed for
	 * example in the factbox. If errors occurred, return the error message.
	 * The output is in the specified format.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (for HTML output), or NULL for no linking.
	 */
	public function getLongText( $outputformat, $linker = null ) {
		switch ( $outputformat ) {
			case SMW_OUTPUT_WIKI:
				return $this->getLongWikiText( $linker );
			case SMW_OUTPUT_HTML:
			case SMW_OUTPUT_FILE:
			default:
				return $this->getLongHTMLText( $linker );
		}
	}

	/**
	 * Return text serialisation of info links. Ensures more uniform layout
	 * throughout wiki (Factbox, Property pages, ...).
	 *
	 * @param integer $outputformat Element of the SMW_OUTPUT_ enum
	 * @param $linker
	 *
	 * @return string
	 */
	public function getInfolinkText( $outputformat, $linker = null ) {
		return $this->getInfoLinksProvider()->getInfolinkText( $outputformat, $linker );
	}

	/**
	 * Return the plain wiki version of the value, or
	 * FALSE if no such version is available. The returned
	 * string suffices to reobtain the same DataValue
	 * when passing it as an input string to setUserValue().
	 */
	abstract public function getWikiValue();

	/**
	 * Return a short string that unambiguously specify the type of this
	 * value. This value will globally be used to identify the type of a
	 * value (in spite of the class it actually belongs to, which can still
	 * implement various types).
	 */
	public function getTypeID() {
		return $this->m_typeid;
	}

	/**
	 * @since 2.1
	 */
	public function disableServiceLinks() {
		$this->getInfoLinksProvider()->disableServiceLinks();
	}

	/**
	 * Return an array of SMWLink objects that provide additional resources
	 * for the given value. Captions can contain some HTML markup which is
	 * admissible for wiki text, but no more. Result might have no entries
	 * but is always an array.
	 */
	public function getInfolinks() {

		$this->getInfoLinksProvider()->setServiceLinkParameters(
			$this->getServiceLinkParams()
		);

		return $this->getInfoLinksProvider()->createInfoLinks();
	}

	/**
	 * Overwritten by callers to supply an array of parameters that can be used for
	 * creating servicelinks. The number and content of values in the parameter array
	 * may vary, depending on the concrete datatype.
	 */
	protected function getServiceLinkParams() {
		return false;
	}

	/**
	 * Return a string that identifies the value of the object, and that can
	 * be used to compare different value objects.
	 * Possibly overwritten by subclasses (e.g. to ensure that returned
	 * value is normalized first)
	 *
	 * @return string
	 */
	public function getHash() {
		return $this->isValid() ? $this->m_dataitem->getHash() : implode( "\t", $this->mErrors );
	}

	/**
	 * Convenience method that checks if the value that is used to sort
	 * data of this type is numeric. This only works if the value is set.
	 *
	 * @return boolean
	 */
	public function isNumeric() {
		if ( isset( $this->m_dataitem ) ) {
			return is_numeric( $this->m_dataitem->getSortKey() );
		} else {
			return false;
		}
	}

	/**
	 * Return true if a value was defined and understood by the given type,
	 * and false if parsing errors occurred or no value was given.
	 *
	 * @return boolean
	 */
	public function isValid() {
		return !$this->mHasErrors && isset( $this->m_dataitem );
	}

	/**
	 * Whether a datavalue can be used or not (can be made more restrictive then
	 * isValid).
	 *
	 * @note Validity defines a processable state without any technical restrictions
	 * while usability is determined by its accessibility to a context
	 * (permission, convention etc.)
	 *
	 * @since  2.2
	 *
	 * @return boolean
	 */
	public function canUse() {
		return true;
	}

	/**
	 * @note Normally set by the DataValueFactory, or during tests
	 *
	 * @since 2.3
	 *
	 * @param array
	 */
	public function setExtraneousFunctions( array $extraneousFunctions ) {
		$this->extraneousFunctions = $extraneousFunctions;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $name
	 * @param array $parameters
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function getExtraneousFunctionFor( $name, array $parameters = array() ) {

		if ( isset( $this->extraneousFunctions[$name] ) && is_callable( $this->extraneousFunctions[$name] ) ) {
			return call_user_func_array( $this->extraneousFunctions[$name], $parameters );
		}

		throw new RuntimeException( "$name is not registered as extraneous function." );
	}

	/**
	 * Return a string that displays all error messages as a tooltip, or
	 * an empty string if no errors happened.
	 *
	 * @return string
	 */
	public function getErrorText() {
		return smwfEncodeMessages( $this->mErrors );
	}

	/**
	 * Return an array of error messages, or an empty array
	 * if no errors occurred.
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->mErrors;
	}

	/**
	 * Check if property is range restricted and, if so, whether the current value is allowed.
	 * Creates an error if the value is illegal.
	 */
	protected function checkAllowedValues() {
		ValueValidatorRegistry::getInstance()->getConstraintValueValidator()->validate( $this );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function convertDoubleWidth( $value ) {
		return Localizer::convertDoubleWidth( $value );
	}

	private function getInfoLinksProvider() {

		if ( $this->infoLinksProvider === null ) {
			$this->infoLinksProvider = new InfoLinksProvider( $this );
		}

		return $this->infoLinksProvider;
	}

}
