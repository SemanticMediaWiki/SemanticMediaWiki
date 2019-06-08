<?php

/**
 * This group contains all parts of SMW that relate to the processing of datavalues
 * of various types.
 *
 * @defgroup SMWDataValues SMWDataValues
 * @ingroup SMW
 */

use SMW\DataValues\InfoLinksProvider;
use SMW\DIProperty;
use SMW\Localizer;
use SMW\Message;
use SMW\Options;
use SMW\Query\QueryComparator;
use SMW\Services\DataValueServiceFactory;
use SMW\Utils\CharArmor;
use SMW\ProcessingError;

/**
 * Objects of this type represent all that is known about a certain user-provided
 * data value, especially its various representations as strings, tooltips,
 * numbers, etc.  Objects can be created as "empty" containers of a certain type,
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
	 * Contains the user language a user operates in.
	 */
	const OPT_USER_LANGUAGE = 'user.language';

	/**
	 * Contains either the global "site" content language or a specified page
	 * content language invoked by the context page.
	 */
	const OPT_CONTENT_LANGUAGE = 'content.language';

	/**
	 * Describes a state where a DataValue is part of a query condition and may
	 * (or not) require a different treatment.
	 */
	const OPT_QUERY_CONTEXT = 'query.context';

	/**
	 * Describes a state where a DataValue is part of a query condition and
	 * contains a comparator.
	 */
	const OPT_QUERY_COMP_CONTEXT = 'query.comparator.context';

	/**
	 * Option to disable related infolinks
	 */
	const OPT_DISABLE_INFOLINKS = 'disable.infolinks';

	/**
	 * Option to disable service links
	 */
	const OPT_DISABLE_SERVICELINKS = 'disable.servicelinks';

	/**
	 * Option to use compact infolinks
	 */
	const OPT_COMPACT_INFOLINKS = 'compact.infolinks';

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
	 * @var DIProperty
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
	private $mErrors = [];

	/**
	 * @var array
	 */
	private $errorsByType = [];

	/**
	 * Boolean indicating if there where any errors.
	 * Should be modified accordingly when modifying $mErrors.
	 * @var boolean
	 */
	private $mHasErrors = false;

	/**
	 * @var false|array
	 */
	protected $restrictionError = false;

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var InfoLinksProvider
	 */
	private $infoLinksProvider = null;

	/**
	 * @var string
	 */
	private $userValue = '';

	/**
	 * @var DataValueServiceFactory
	 */
	protected $dataValueServiceFactory;

	/**
	 * @var DescriptionBuilderRegistry
	 */
	private $descriptionBuilderRegistry;

	/**
	 * @var []
	 */
	private $callables = [];

	/**
	 * Constructor.
	 *
	 * @param string $typeid
	 */
	public function __construct( $typeid ) {
		$this->m_typeid = $typeid;
	}

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
	 * Set the user value (and compute other representations if possible).
	 * The given value is a string as supplied by some user. An alternative
	 * label for printout might also be specified.
	 *
	 * @param string $value
	 * @param mixed $caption
	 */
	public function setUserValue( $value, $caption = false ) {

		$this->m_dataitem = null;
		$this->mErrors = []; // clear errors
		$this->mHasErrors = false;
		$this->m_caption = is_string( $caption ) ? trim( $caption ) : false;
		$this->userValue = $value;

		// #2435
		$value = CharArmor::removeControlChars(
			CharArmor::removeSpecialChars( $value )
		);

		// Process may set a caption if not set yet, depending on datavalue
		$this->parseUserValue( $value );

		// The following checks for Strip markers generated by MediaWiki to handle special content,
		// from parser and extension tags e.g. <pre>,<nowiki>,<math>,<source>.
		// See https://en.wikipedia.org/wiki/Help:Strip_markers
		// In general, we are not prepared to handle such content properly, and we
		// also have no means of obtaining the user input at this point. Hence the assignment
		// just fails, even if parseUserValue() above might not have noticed this issue.
		// Note: \x07 was used in MediaWiki 1.11.0, \x7f is used now (backwards compatibility, b/c)
		if ( is_string( $value ) && ( ( strpos( $value, "\x7f" ) !== false ) || ( strpos( $value, "\x07" ) !== false ) ) ) {
			$this->addErrorMsg( [ 'smw-datavalue-stripmarker-parse-error', $value ] );
		}

		if ( $this->isValid() && !$this->getOption( self::OPT_QUERY_CONTEXT ) ) {
			$this->checkConstraints();
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
		$this->mErrors = [];
		$this->mHasErrors = $this->m_caption = false;
		return $this->loadDataItem( $dataItem );
	}

	/**
	 * @since 2.5
	 *
	 * @param DataValueServiceFactory $dataValueServiceFactory
	 */
	public function setDataValueServiceFactory( DataValueServiceFactory $dataValueServiceFactory ) {
		$this->dataValueServiceFactory = $dataValueServiceFactory;
	}

	/**
	 * Specify the property to which this value refers. Property pages are
	 * used to make settings that affect parsing and display, hence it is
	 * sometimes needed to know them.
	 *
	 * @since 1.6
	 *
	 * @param DIProperty $property
	 */
	public function setProperty( DIProperty $property ) {
		$this->m_property = $property;
	}

	/**
	 * Returns the property to which this value refers.
	 *
	 * @since 1.8
	 *
	 * @return DIProperty|null
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
	 * @param array|string|ProcessingError $error
	 */
	public function addError( $error ) {

		if ( $error instanceof ProcessingError ) {
			$hash = $error->getHash();
			$type = $error->getType();

			if ( !isset( $this->errorsByType[$type] ) ) {
				$this->errorsByType[$type] = [];
			}

			$this->mErrors[$hash] = $error->encode();
			$this->errorsByType[$type][] = $hash;
			$this->mHasErrors = true;
		} elseif ( is_array( $error ) ) {
			$this->mErrors = array_merge( $this->mErrors, $error );
			$this->mHasErrors = $this->mHasErrors || ( count( $error ) > 0 );
		} else {
			$this->mErrors[] = $error;
			$this->mHasErrors = true;
		}
	}

	/**
	 * Messages are not resolved until the output and instead will be kept with the
	 * message and argument keys (e.g. `[2,"smw_baduri","~*0123*"]`). This allows to
	 * switch the a representation without requiring language context by the object
	 * that reports an error.
	 *
	 * @since 2.4
	 *
	 * @param array|string|ProcessingError $error
	 * @param integer|null $type
	 */
	public function addErrorMsg( $error, $type = Message::TEXT ) {

		if ( $error instanceof ProcessingError ) {
			$hash = $error->getHash();
			$type = $error->getType();

			if ( !isset( $this->errorsByType[$type] ) ) {
				$this->errorsByType[$type] = [];
			}

			$this->mErrors[$hash] = $error->encode();
			$this->errorsByType[$type][] = $hash;
		} else {
			$this->mErrors[Message::getHash( $error, $type )] = Message::encode( $error, $type );
		}

		$this->mHasErrors = true;
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
	 * @since 3.1
	 *
	 * @param string|null $type
	 *
	 * @return array
	 */
	public function getErrorsByType( $type = null ) {

		if ( $type === null ) {
			return $this->errorsByType;
		}

		if ( isset( $this->errorsByType[$type] ) ) {
			return $this->errorsByType[$type];
		}

		return [];
	}

	/**
	 * @since 3.0
	 *
	 * @return array|false
	 */
	public function getRestrictionError() {
		return $this->restrictionError;
	}

	/**
	 * @since 2.4
	 */
	public function clearErrors() {
		$this->mErrors = [];
		$this->mHasErrors = false;
	}

///// Query support /////

	/**
	 * FIXME 3.0, allow NULL as value
	 *
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

		$descriptionBuilderRegistry = $this->dataValueServiceFactory->getDescriptionBuilderRegistry();
		$descriptionBuilder = $descriptionBuilderRegistry->getDescriptionBuilder( $this );

		$descriptionBuilder->clearErrors();
		$description = $descriptionBuilder->newDescription( $this, $value );

		foreach ( $descriptionBuilder->getErrors() as $error ) {
			$this->addError( $error );
		}

		return $description;
	}

	/**
	 * @deprecated since 2.3
	 *
	 * @see DescriptionBuilder::prepareValue
	 *
	 * This method should no longer be used for direct public access, instead a
	 * DataValue is expected to register a DescriptionBuilder with
	 * DVDescriptionDeserializerRegistry.
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

		return new SMWDIError( $this->mErrors, $this->userValue );
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
	 *
	 * @param Linker|null|bool $linker
	 */
	abstract public function getShortWikiText( $linker = null );

	/**
	 * Returns a short textual representation for this data value. If the value
	 * was initialised from a user supplied string, then this original string
	 * should be reflected in this short version (i.e. no normalisation should
	 * normally happen). There might, however, be additional parts such as code
	 * for generating tooltips. The output is in HTML text.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (or NULL for no linking).
	 *
	 * @param Linker|null|bool $linker
	 */
	abstract public function getShortHTMLText( $linker = null );

	/**
	 * Return the long textual description of the value, as printed for
	 * example in the factbox. If errors occurred, return the error message
	 * The result always is a wiki-source string.
	 *
	 * The parameter $linked controls linking of values such as titles and should
	 * be non-NULL and non-false if this is desired.
	 *
	 * @param Linker|null|bool $linker
	 */
	abstract public function getLongWikiText( $linker = null );

	/**
	 * Return the long textual description of the value, as printed for
	 * example in the factbox. If errors occurred, return the error message
	 * The result always is an HTML string.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (or NULL for no linking).
	 *
	 * @param Linker|null|bool $linker
	 */
	abstract public function getLongHTMLText( $linker = null );

	/**
	 * Return the plain wiki version of the value, or
	 * FALSE if no such version is available. The returned
	 * string suffices to reobtain the same DataValue
	 * when passing it as an input string to setUserValue().
	 */
	abstract public function getWikiValue();

	/**
	 * Returns a short textual representation for this data value. If the value
	 * was initialised from a user supplied string, then this original string
	 * should be reflected in this short version (i.e. no normalisation should
	 * normally happen). There might, however, be additional parts such as code
	 * for generating tooltips. The output is in the specified format.
	 *
	 * The parameter $linker controls linking of values such as titles and should
	 * be some Linker object (for HTML output), or NULL for no linking.
	 *
	 * @param int $outputFormat
	 * @param Linker|null|bool $linker
	 */
	public function getShortText( $outputFormat, $linker = null ) {
		switch ( $outputFormat ) {
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
	 *
	 * @param int $outputFormat
	 * @param Linker|null|bool $linker
	 */
	public function getLongText( $outputFormat, $linker = null ) {
		switch ( $outputFormat ) {
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
	 * @param integer $outputFormat Element of the SMW_OUTPUT_ enum
	 * @param Linker|null|bool $linker
	 *
	 * @return string
	 */
	public function getInfolinkText( $outputFormat, $linker = null ) {

		if ( $this->getOption( self::OPT_DISABLE_INFOLINKS ) === true ) {
			return '';
		}

		if ( $this->infoLinksProvider === null ) {
			$this->infoLinksProvider = $this->dataValueServiceFactory->newInfoLinksProvider( $this );
		}

		if ( $this->getOption( self::OPT_DISABLE_SERVICELINKS ) === true ) {
			$this->infoLinksProvider->disableServiceLinks();
		}

		$this->infoLinksProvider->setCompactLink(
			$this->getOption( self::OPT_COMPACT_INFOLINKS, false )
		);

		return $this->infoLinksProvider->getInfolinkText( $outputFormat, $linker );
	}

	/**
	 * Return an array of SMWLink objects that provide additional resources
	 * for the given value. Captions can contain some HTML markup which is
	 * admissible for wiki text, but no more. Result might have no entries
	 * but is always an array.
	 */
	public function getInfolinks() {

		if ( $this->infoLinksProvider === null ) {
			$this->infoLinksProvider = $this->dataValueServiceFactory->newInfoLinksProvider( $this );
		}

		$this->infoLinksProvider->setServiceLinkParameters(
			$this->getServiceLinkParams()
		);

		return $this->infoLinksProvider->createInfoLinks();
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
		}

		return false;
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
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function canUse() {
		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function isRestricted() {
		return false;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param callable $callable
	 *
	 * @throws RuntimeException
	 */
	public function addCallable( $key, callable $callable ) {

		if ( isset( $this->callables[$key] ) ) {
			throw new RuntimeException( "`$key` is alread in use, please clear the callable first!" );
		}

		$this->callables[$key] = $callable;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return callable
	 * @throws RuntimeException
	 */
	public function getCallable( $key ) {

		if ( !isset( $this->callables[$key] ) ) {
			throw new RuntimeException( "`$key` as callable is unknown or not registered!" );
		}

		return $this->callables[$key];
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 */
	public function clearCallable( $key ) {
		unset( $this->callables[$key] );
	}

	/**
	 * @since 2.4
	 *
	 * @return Options|null $options
	 */
	public function copyOptions( Options $options = null ) {

		if ( $options === null ) {
			return;
		}

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
	public function getOption( $key, $default = false ) {

		if ( $this->options !== null && $this->options->has( $key ) ) {
			return $this->options->get( $key );
		}

		return $default;
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $feature
	 *
	 * @return boolean
	 */
	public function hasFeature( $feature ) {

		if ( $this->options !== null ) {
			return $this->options->isFlagSet( 'smwgDVFeatures', (int)$feature );
		}

		return false;
	}

	/**
	 * @deprecated since 3.0, use DataValue::hasFeature
	 * @since 2.4
	 */
	public function isEnabledFeature( $feature ) {
		return $this->hasFeature( $feature );
	}

	/**
	 * @since 2.5
	 *
	 * @return Options
	 */
	protected function getOptions() {
		return $this->options;
	}

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

	/**
	 * Overwritten by callers to supply an array of parameters that can be used for
	 * creating servicelinks. The number and content of values in the parameter array
	 * may vary, depending on the concrete datatype.
	 */
	protected function getServiceLinkParams() {
		return false;
	}

	/**
	 * @deprecated since 3.1, use DataValue::checkConstraints
	 */
	protected function checkAllowedValues() {
		$this->checkConstraints();
	}

	/**
	 * @since 3.1
	 */
	public function checkConstraints() {

		if ( $this->dataValueServiceFactory === null ) {
			return;
		}

		$this->dataValueServiceFactory->getConstraintValueValidator()->validate( $this );
	}

	function __destruct() {
		$this->callables = [];
	}

}
