<?php

namespace SMW\Query;

use InvalidArgumentException;
use SMW\DataValues\PropertyChainValue;
use SMW\Localizer;
use SMW\Query\PrintRequest\Deserializer;
use SMW\Query\PrintRequest\Formatter;
use SMW\Query\PrintRequest\Serializer;
use SMWDataValue;
use SMWPropertyValue as PropertyValue;
use Title;

/**
 * Container class for request for printout, as used in queries to
 * obtain additional information for the retrieved results.
 *
 * @ingroup SMWQuery
 * @author Markus Krötzsch
 */
class PrintRequest {

	/**
	 * Query mode to print all direct categories of the current element.
	 */
	const PRINT_CATS = 0;

	/**
	 * Query mode to print all property values of a certain attribute of the
	 * current element.
	 */
	const PRINT_PROP = 1;

	/**
	 * Query mode to print the current element (page in result set).
	 */
	const PRINT_THIS = 2;

	/**
	 * Query mode to print whether current element is in given category
	 * (Boolean printout).
	 */
	const PRINT_CCAT = 3;

	/**
	 * Query mode indicating a chainable property value entity, with the last
	 * element to represent the printable output
	 */
	const PRINT_CHAIN = 4;

	protected $m_mode; // type of print request

	protected $m_label; // string for labelling results, contains no markup

	protected $m_data; // data entries specifyin gwhat was requested (mixed type)

	protected $m_typeid = false; // id of the datatype of the printed objects, if applicable

	protected $m_outputformat; // output format string for formatting results, if applicable

	protected $m_hash = false; // cache your hash (currently useful since SMWQueryResult accesses the hash many times, might be dropped at some point)

	protected $m_params = [];

	/**
	 * Identifies whether this instance was used/added and is diconnected to
	 * the original query where it was added.
	 *
	 * Mostly used in cases where QueryProcessor::addThisPrintout was executed.
	 */
	private $isDisconnected = false;

	/**
	 * Whether the label was marked with an extra `#` identifier.
	 */
	private $labelMarker = false;

	/**
	 * Create a print request.
	 *
	 * @param integer $mode a constant defining what to printout
	 * @param string $label the string label to describe this printout
	 * @param mixed $data optional data for specifying some request, might be a property object, title, or something else; interpretation depends on $mode
	 * @param mixed $outputformat optional string for specifying an output format, e.g. an output unit
	 * @param array|null $params optional array of further, named parameters for the print request
	 */
	public function __construct( $mode, $label, $data = null, $outputformat = false, array $params = null ) {
		if ( ( ( $mode == self::PRINT_CATS || $mode == self::PRINT_THIS ) &&
				!is_null( $data ) ) ||
			( $mode == self::PRINT_PROP &&
				( !( $data instanceof PropertyValue ) || !$data->isValid() ) ) ||
			( $mode == self::PRINT_CHAIN &&
				( !( $data instanceof PropertyChainValue ) || !$data->isValid() ) ) ||
			( $mode == self::PRINT_CCAT &&
				!( $data instanceof Title ) )
		) {
			throw new InvalidArgumentException( 'Data provided for print request does not fit the type of printout.' );
		}

		$this->m_mode = $mode;
		$this->m_data = $data;
		$this->m_outputformat = $outputformat;

		if ( $mode == self::PRINT_CCAT && !$outputformat ) {
			$this->m_outputformat = 'x'; // changed default for Boolean case
		}

		$this->setLabel( $label );

		if ( $params !== null ) {
			$this->m_params = $params;
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isDisconnected
	 */
	public function isDisconnected( $isDisconnected ) {
		$this->isDisconnected = (bool)$isDisconnected;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 */
	public function markThisLabel( $text ) {

		if ( $this->m_mode !== self::PRINT_THIS ) {
			return;
		}

		$this->labelMarker = $text !== '' && $text[0] === '#';
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function hasLabelMarker() {
		return $this->labelMarker;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $mode
	 *
	 * @return boolean
	 */
	public function isMode( $mode ) {
		return $this->m_mode === $mode;
	}

	public function getMode() {
		return $this->m_mode;
	}

	public function getLabel() {
		return $this->m_label;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getCanonicalLabel() {

		if ( $this->m_mode === self::PRINT_PROP ) {
			return $this->m_data->getDataItem()->getCanonicalLabel();
		} elseif ( $this->m_mode === self::PRINT_CHAIN ) {
			return $this->m_data->getDataItem()->getString();
		} elseif ( $this->m_mode === self::PRINT_CATS ) {
			return Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );
		} elseif ( $this->m_mode === self::PRINT_CCAT ) {
			return $this->m_data->getPrefixedText();
		}

		return $this->m_label;
	}

	/**
	 * Obtain an HTML-formatted representation of the label.
	 * The $linker is a Linker object used for generating hyperlinks.
	 * If it is NULL, no links will be created.
	 */
	public function getHTMLText( $linker = null ) {
		return Formatter::format( $this, $linker, Formatter::FORMAT_HTML );
	}

	/**
	 * Obtain a Wiki-formatted representation of the label.
	 */
	public function getWikiText( $linker = false ) {
		return Formatter::format( $this, $linker, Formatter::FORMAT_WIKI );
	}

	/**
	 * Convenience method for accessing the text in either HTML or Wiki format.
	 */
	public function getText( $outputMode, $linker = null ) {
		return Formatter::format( $this, $linker, $outputMode );
	}

	/**
	 * Return additional data related to the print request. The result might be
	 * an object of class PropertyValue or Title, or simply NULL if no data
	 * is required for the given type of printout.
	 */
	public function getData() {
		return $this->m_data;
	}

	public function getOutputFormat() {
		return $this->m_outputformat;
	}

	/**
	 * If this print request refers to some property, return the type id of this property.
	 * Otherwise return '_wpg' since all other types of print request return wiki pages.
	 *
	 * @return string
	 */
	public function getTypeID() {

		if ( $this->m_typeid !== false ) {
			return $this->m_typeid;
		}

		if ( $this->m_mode == self::PRINT_PROP ) {
			$this->m_typeid = $this->m_data->getDataItem()->findPropertyTypeID();
		} elseif ( $this->m_mode == self::PRINT_CHAIN ) {
			$this->m_typeid = $this->m_data->getLastPropertyChainValue()->getDataItem()->findPropertyTypeID();
		} else {
			$this->m_typeid = '_wpg';
		}

		return $this->m_typeid;
	}

	/**
	 * Return a hash string that is used to eliminate duplicate
	 * print requests. The hash also includes the chosen label,
	 * so it is possible to print the same date with different
	 * labels.
	 *
	 * @return string
	 */
	public function getHash() {

		if ( $this->m_hash !== false ) {
			return $this->m_hash;
		}

		$this->m_hash = $this->m_mode . ':' . $this->m_label . ':';

		if ( $this->m_data instanceof Title ) {
			$this->m_hash .= $this->m_data->getPrefixedText() . ':';
		}
		elseif ( $this->m_data instanceof SMWDataValue ) {
			$this->m_hash .= $this->m_data->getHash() . ':';
		}

		$this->m_hash .= $this->m_outputformat . ':' . implode( '|', $this->m_params );

		return $this->m_hash;
	}

	/**
	 * Serialise this object like print requests given in \#ask.
	 *
	 * @param $params boolean that sets if the serialization should
	 *                include the extra print request parameters
	 */
	public function getSerialisation( $showparams = false ) {

		// In case of  disconnected instance (QueryProcessor::addThisPrintout as
		// part of a post-processing) return an empty serialization when the
		// mainLabel is available to avoid an extra `?...`
		if ( $this->isMode( self::PRINT_THIS ) && $this->isDisconnected ) {
			return '';
		}

		return Serializer::serialize( $this, $showparams );
	}

	/**
	 * Returns the value of a named parameter.
	 *
	 * @param $key string the name of the parameter key
	 *
	 * @return string Value of the paramer, if set (else FALSE)
	 */
	public function getParameter( $key ) {
		return array_key_exists( $key, $this->m_params ) ? $this->m_params[$key] : false;
	}

	/**
	 * Returns the array of parameters, where a string is mapped to a string.
	 *
	 * @return array Map of parameter names to values.
	 */
	public function getParameters() {
		return $this->m_params;
	}

	/**
	 * Sets a print request parameter.
	 *
	 * @param $key string Name of the parameter
	 * @param $value string Value for the parameter
	 */
	public function setParameter( $key, $value ) {
		$this->m_params[$key] = $value;
	}

	/**
	 * Removes a request parameter
	 *
	 * @since 3.0
	 *
	 * @param string $key
	 */
	public function removeParameter( $key ) {
		unset( $this->m_params[$key] );
	}

	/**
	 * @since  2.1
	 *
	 * @note $this->m_data = clone $data; // we assume that the caller denotes
	 * the object ot us; else he needs provide us with a clone
	 *
	 * @param string $label
	 */
	public function setLabel( $label ) {
		$this->m_label = $label;

		if ( $this->m_data instanceof SMWDataValue ) {
			$this->m_data->setCaption( $label );
		}
	}

	/**
	 * @see Deserializer::deserialize
	 * @since 2.4
	 *
	 * @param string $text
	 * @param boalean $showMode = false
	 * @param boolean $useCanonicalLabel = false
	 *
	 * @return PrintRequest|null
	 */
	public static function newFromText( $text, $showMode = false, $useCanonicalLabel = false ) {

		$options = [
			'show_mode' => $showMode,
			'canonical_label' => $useCanonicalLabel
		];

		return Deserializer::deserialize( $text, $options );
	}

}
