<?php

/**
 * This group contains all parts of SMW that relate to the processing of datavalues
 * of various types.
 *
 * @defgroup SMWDataValues SMWDataValues
 * @ingroup SMW
 */
use SMW\DataValueFactory;
use SMW\Query\QueryComparator;
use SMW\Deserializers\DVDescriptionDeserializerFactory;

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
	 * Array of SMWInfolink objects.
	 * @var array
	 */
	protected $m_infolinks = array();

	/**
	 * Output formatting string, false when not set.
	 * @see setOutputFormat()
	 * @var mixed
	 */
	protected $m_outformat = false;

	/**
	 * Used to control the addition of the standard search link.
	 * @var boolean
	 */
	private $mHasSearchLink;

	/**
	 * Used to control service link creation.
	 * @var boolean
	 */
	private $mHasServiceLinks;

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
	 * @var boolean
	 */
	private $serviceLinksRenderState = true;

	/**
	 * Extraneous services and object container
	 *
	 * @var array
	 */
	private $extraneousFunctions = array();

	/**
	 * Indicates whether a value is being used by a query condition or not which
	 * can lead to a modified validation of a value.
	 *
	 * @var boolean
	 */
	protected $isUsedByQueryCondition = false;

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
	 * @param boolean $ignoreAllowedValues
	 */
	public function setUserValue( $value, $caption = false, $ignoreAllowedValues = false ) {

		$this->m_dataitem = null;
		$this->mErrors = array(); // clear errors
		$this->mHasErrors = false;
		$this->m_infolinks = array(); // clear links
		$this->mHasSearchLink = false;
		$this->mHasServiceLinks = false;
		$this->m_caption = is_string( $caption ) ? trim( $caption ) : false;


		$this->parseUserValue( $value ); // may set caption if not set yet, depending on datavalue

		// The following checks for markers generated by MediaWiki to handle special content,
		// e.g. math. In general, we are not prepared to handle such content properly, and we
		// also have no means of obtaining the user input at this point. Hence the assignement
		// just fails, even if parseUserValue() above might not have noticed this issue.
		// Note: \x07 was used in MediaWiki 1.11.0, \x7f is used now (backwards compatiblity, b/c)
		if ( ( strpos( $value, "\x7f" ) !== false ) || ( strpos( $value, "\x07" ) !== false ) ) {
			$this->addError( wfMessage( 'smw_parseerror' )->inContentLanguage()->text() );
		}

		if ( $this->isValid() && !$ignoreAllowedValues ) {
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
		$this->m_dataitem = null;
		$this->mErrors = $this->m_infolinks = array();
		$this->mHasErrors = $this->mHasSearchLink = $this->mHasServiceLinks = $this->m_caption = false;
		return $this->loadDataItem( $dataItem );
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $usedByQueryCondition
	 */
	public function setQueryConditionUsage( $usedByQueryCondition ) {
		$this->isUsedByQueryCondition = (bool)$usedByQueryCondition;
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
	 * @param SMWDIWikiPage $contextPage
	 */
	public function setContextPage( SMWDIWikiPage $contextPage ) {
		$this->m_contextPage = $contextPage;
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
	 * Adds a single SMWInfolink object to the m_infolinks array.
	 *
	 * @param SMWInfolink $link
	 */
	public function addInfolink( SMWInfolink $link ) {
		$this->m_infolinks[] = $link;
	}

	/**
	 * Servicelinks are special kinds of infolinks that are created from
	 * current parameters and in-wiki specification of URL templates. This
	 * method adds the current property's servicelinks found in the
	 * messages. The number and content of the parameters is depending on
	 * the datatype, and the service link message is usually crafted with a
	 * particular datatype in mind.
	 */
	public function addServiceLinks() {
		if ( $this->mHasServiceLinks ) {
			return;
		}

		if ( !is_null( $this->m_property ) ) {
			$propertyDiWikiPage = $this->m_property->getDiWikiPage();
		}

		if ( is_null( $this->m_property ) || is_null( $propertyDiWikiPage ) ) {
			return; // no property known, or not associated with a page
		}

		$args = $this->getServiceLinkParams();

		if ( $args === false ) {
			return; // no services supported
		}

		array_unshift( $args, '' ); // add a 0 element as placeholder
		$servicelinks = \SMW\StoreFactory::getStore()->getPropertyValues( $propertyDiWikiPage, new SMWDIProperty( '_SERV' ) );

		foreach ( $servicelinks as $dataItem ) {
			if ( !( $dataItem instanceof SMWDIBlob ) ) {
				continue;
			}

			$args[0] = 'smw_service_' . str_replace( ' ', '_', $dataItem->getString() ); // messages distinguish ' ' from '_'
			// @todo FIXME: Use wfMessage/Message class here.
			$text = call_user_func_array( 'wfMsgForContent', $args );
			$links = preg_split( "/[\n][\s]?/u", $text );

			foreach ( $links as $link ) {
				$linkdat = explode( '|', $link, 2 );

				if ( count( $linkdat ) == 2 ) {
					$this->addInfolink( SMWInfolink::newExternalLink( $linkdat[0], trim( $linkdat[1] ) ) );
				}
			}
		}
		$this->mHasServiceLinks = true;
	}

	/**
	 * Define a particular output format. Output formats are user-supplied strings
	 * that the datavalue may (or may not) use to customise its return value. For
	 * example, quantities with units of measurement may interpret the string as
	 * a desired output unit. In other cases, the output format might be built-in
	 * and subject to internationalisation (which the datavalue has to implement).
	 * In any case, an empty string resets the output format to the default.
	 *
	 * There is one predeeind output format that all datavalues should respect: the
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
	 * Clear error messages. This function is provided temporarily to allow
	 * n-ary to do this.
	 * properly so that this function will vanish again.
	 * @note Do not use this function in external code.
	 * @todo Check if we can remove this function again.
	 */
	protected function clearErrors() {
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

		$dvDescriptionDeserializerFactory = DVDescriptionDeserializerFactory::getInstance()->getDescriptionDeserializerFor( $this );
		$description = $dvDescriptionDeserializerFactory->deserialize( $value );

		foreach ( $dvDescriptionDeserializerFactory->getErrors() as $error ) {
			$this->addError( $error );
		}

		return $description;
	}

	/**
	 * @deprecated 2.3
	 * @see DescriptionDeserializer::prepareValue
	 *
	 * This method is no longer to be used for direct public access, instead a
	 * DataValue is expected to register a DescriptionDeserializer with
	 * DVDescriptionDeserializerFactory.
	 *
	 * FIXME as of 2.3, SMGeoCoordsValue still uses this method and requires
	 * migration before 3.0
	 */
	static public function prepareValue( &$value, &$comparator ) {
		// Loop over the comparators to determine which one is used and what the actual value is.
		foreach ( QueryComparator::getInstance()->getComparatorStrings() as $string ) {
			if ( strpos( $value, $string ) === 0 ) {
				$comparator = QueryComparator::getInstance()->getComparatorFromString( substr( $value, 0, strlen( $string ) ) );
				$value = substr( $value, strlen( $string ) );
				break;
			}
		}
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
		$result = '';
		$first = true;
		$extralinks = array();

		switch ( $outputformat ) {
			case SMW_OUTPUT_WIKI:
				foreach ( $this->getInfolinks() as $link ) {
					if ( $first ) {
						$result .= '<!-- -->  ' . $link->getWikiText();
							// the comment is needed to prevent MediaWiki from linking URL-strings together with the nbsps!
						$first = false;
					} else {
						$extralinks[] = $link->getWikiText();
					}
				}
				break;

			case SMW_OUTPUT_HTML: case SMW_OUTPUT_FILE: default:
				foreach ( $this->getInfolinks() as $link ) {
					if ( $first ) {
						$result .= '&#160;&#160;' . $link->getHTML( $linker );
						$first = false;
					} else {
						$extralinks[] = $link->getHTML( $linker );
					}
				}
				break;
		}

		if ( count( $extralinks ) > 0 ) {
			$result .= smwfEncodeMessages( $extralinks, 'service', '', false );
		}

		return $result;
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
	 * @param boolean $renderState
	 */
	public function setServiceLinksRenderState( $renderState = true ) {
		$this->serviceLinksRenderState = $renderState;
	}

	/**
	 * Return an array of SMWLink objects that provide additional resources
	 * for the given value. Captions can contain some HTML markup which is
	 * admissible for wiki text, but no more. Result might have no entries
	 * but is always an array.
	 */
	public function getInfolinks() {
		if ( $this->isValid() && !is_null( $this->m_property ) ) {
			if ( !$this->mHasSearchLink ) { // add default search link
				$this->mHasSearchLink = true;
				$this->m_infolinks[] = SMWInfolink::newPropertySearchLink( '+',
					$this->m_property->getLabel(), $this->getWikiValue() );
			}

			if ( !$this->mHasServiceLinks && $this->serviceLinksRenderState ) { // add further service links
				$this->addServiceLinks();
			}
		}

		return $this->m_infolinks;
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
		if ( !is_null( $this->m_property ) ) {
			$propertyDiWikiPage = $this->m_property->getDiWikiPage();
		}

		if ( is_null( $this->m_property ) || is_null( $propertyDiWikiPage ) ||
			!isset( $this->m_dataitem ) ) {
			return; // no property known, or no data to check
		}

		$allowedvalues = \SMW\StoreFactory::getStore()->getPropertyValues(
			$propertyDiWikiPage,
			new SMWDIProperty( '_PVAL' )
		);

		if ( count( $allowedvalues ) == 0 ) {
			return;
		}

		$hash = $this->m_dataitem->getHash();
		$testdv = DataValueFactory::getInstance()->newTypeIDValue( $this->getTypeID() );
		$accept = false;
		$valuestring = '';

		foreach ( $allowedvalues as $di ) {
			if ( $di instanceof SMWDIBlob ) {
				$testdv->setUserValue( $di->getString() );

				if ( $hash === $testdv->getDataItem()->getHash() ) {
					$accept = true;
					break;
				} else {
					if ( $valuestring !== '' ) {
						$valuestring .= ', ';
					}
					$valuestring .= $di->getString();
				}
			}
		}

		if ( !$accept ) {
			$this->addError( wfMessage(
					'smw_notinenum',
					$this->getWikiValue(), $valuestring
				)->inContentLanguage()->text()
			);
		}
	}

}
