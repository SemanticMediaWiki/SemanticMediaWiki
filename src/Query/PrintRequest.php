<?php

namespace SMW\Query;

use InvalidArgumentException;
use SMW\Localizer;
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

	/// Query mode to print all direct categories of the current element.
	const PRINT_CATS = 0;
	/// Query mode to print all property values of a certain attribute of the current element.
	const PRINT_PROP = 1;
	/// Query mode to print the current element (page in result set).
	const PRINT_THIS = 2;
	/// Query mode to print whether current element is in given category (Boolean printout).
	const PRINT_CCAT = 3;

	protected $m_mode; // type of print request

	protected $m_label; // string for labelling results, contains no markup

	protected $m_data; // data entries specifyin gwhat was requested (mixed type)

	protected $m_typeid = false; // id of the datatype of the printed objects, if applicable

	protected $m_outputformat; // output format string for formatting results, if applicable

	protected $m_hash = false; // cache your hash (currently useful since SMWQueryResult accesses the hash many times, might be dropped at some point)

	protected $m_params = array();

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

	public function getMode() {
		return $this->m_mode;
	}

	public function getLabel() {
		return $this->m_label;
	}

	/**
	 * Obtain an HTML-formatted representation of the label.
	 * The $linker is a Linker object used for generating hyperlinks.
	 * If it is NULL, no links will be created.
	 */
	public function getHTMLText( $linker = null ) {
		if ( is_null( $linker ) || ( $this->m_label === '' ) ) {
			return htmlspecialchars( $this->m_label );
		}

		switch ( $this->m_mode ) {
			case self::PRINT_CATS:
				return htmlspecialchars( $this->m_label ); // TODO: link to Special:Categories
			case self::PRINT_CCAT:
				return \Linker::link( $this->m_data, htmlspecialchars( $this->m_label ) );
			case self::PRINT_PROP:
				return $this->m_data->getShortHTMLText( $linker );
			case self::PRINT_THIS:
			default:
				return htmlspecialchars( $this->m_label );
		}
	}

	/**
	 * Obtain a Wiki-formatted representation of the label.
	 */
	public function getWikiText( $linked = false ) {
		if ( is_null( $linked ) || ( $linked === false ) || ( $this->m_label === '' ) ) {
			return $this->m_label;
		}
		else {
			switch ( $this->m_mode ) {
				case self::PRINT_CATS:
					return $this->m_label; // TODO: link to Special:Categories
				case self::PRINT_PROP:
					return $this->m_data->getShortWikiText( $linked );
				case self::PRINT_CCAT:
					return '[[:' . $this->m_data->getPrefixedText() . '|' . $this->m_label . ']]';
				case self::PRINT_THIS:
				default:
					return $this->m_label;
			}
		}
	}

	/**
	 * Convenience method for accessing the text in either HTML or Wiki format.
	 */
	public function getText( $outputmode, $linker = null ) {
		switch ( $outputmode ) {
			case SMW_OUTPUT_WIKI:
				return $this->getWikiText( $linker );
			case SMW_OUTPUT_HTML:
			case SMW_OUTPUT_FILE:
			default:
				return $this->getHTMLText( $linker );
		}
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
		if ( $this->m_typeid === false ) {
			if ( $this->m_mode == self::PRINT_PROP ) {
				$this->m_typeid = $this->m_data->getDataItem()->findPropertyTypeID();
			}
			else {
				$this->m_typeid = '_wpg';
			}
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
		if ( $this->m_hash === false ) {
			$this->m_hash = $this->m_mode . ':' . $this->m_label . ':';

			if ( $this->m_data instanceof Title ) {
				$this->m_hash .= $this->m_data->getPrefixedText() . ':';
			}
			elseif ( $this->m_data instanceof SMWDataValue ) {
				$this->m_hash .= $this->m_data->getHash() . ':';
			}

			$this->m_hash .= $this->m_outputformat . ':' . implode( '|', $this->m_params );
		}

		return $this->m_hash;
	}

	/**
	 * Serialise this object like print requests given in \#ask.
	 *
	 * @param $params boolean that sets if the serialization should
	 *                include the extra print request parameters
	 */
	public function getSerialisation( $showparams = false ) {
		$parameters = '';

		if ( $showparams ) {
			foreach ( $this->m_params as $key => $value ) {
				$parameters .= "|+" . $key . "=" . $value;
			}
		}

		switch ( $this->m_mode ) {
			case self::PRINT_CATS:
				global $wgContLang;
				$catlabel = $wgContLang->getNSText( NS_CATEGORY );
				$result = '?' . $catlabel;
				if ( $this->m_label != $catlabel ) {
					$result .= '=' . $this->m_label;
				}

				return $result . $parameters;
			case self::PRINT_PROP:
			case self::PRINT_CCAT:
				if ( $this->m_mode == self::PRINT_CCAT ) {
					$printname = $this->m_data->getPrefixedText();
					$result = '?' . $printname;

					if ( $this->m_outputformat != 'x' ) {
						$result .= '#' . $this->m_outputformat;
					}
				}
				else {

					$printname = '';

					if ( $this->m_data->isVisible() ) {
						// #1564
						// Use the canonical form for predefined properties to ensure
						// that local representations are for display but points to
						// the correct property
						$printname = $this->m_data->getDataItem()->getCanonicalLabel();
					}

					$result = '?' . $printname;

					if ( $this->m_outputformat !== '' ) {
						$result .= '#' . $this->m_outputformat;
					}
				}
				if ( $printname != $this->m_label ) {
					$result .= '=' . $this->m_label;
				}

				return $result . $parameters;
			case self::PRINT_THIS:
				$result = '?';

				if ( $this->m_label !== '' ) {
					$result .= '=' . $this->m_label;
				}

				if ( $this->m_outputformat !== '' ) {
					$result .= '#' . $this->m_outputformat;
				}

				return $result . $parameters;
			default:
				return ''; // no current serialisation
		}
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
	 * Create an PrintRequest object from a string description as one
	 * would normally use in #ask and related inputs. The string must start
	 * with a "?" and may contain label and formatting parameters after "="
	 * or "#", respectively. However, further parameters, given in #ask by
	 * "|+param=value" are not allowed here; they must be added
	 * individually.
	 *
	 * @since 2.4
	 *
	 * @param string $text
	 * @param $showMode = false
	 *
	 * @return PrintRequest|null
	 */
	public static function newFromText( $text, $showMode = false ) {

		list( $parts, $propparts, $printRequestLabel ) = self::doSplitText( $text );
		$data = null;

		if ( $printRequestLabel === '' ) { // print "this"
			$printmode = self::PRINT_THIS;
			$label = ''; // default
		} elseif ( self::isCategory( $printRequestLabel ) ) { // print categories
			$printmode = self::PRINT_CATS;
			$label = $showMode ? '' : Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY ); // default
		} else { // print property or check category
			$title = Title::newFromText( $printRequestLabel, SMW_NS_PROPERTY ); // trim needed for \n
			if ( is_null( $title ) ) { // not a legal property/category name; give up
				return null;
			}

			if ( $title->getNamespace() == NS_CATEGORY ) {
				$printmode = self::PRINT_CCAT;
				$data = $title;
				$label = $showMode ? '' : $title->getText();  // default
			} else { // enforce interpretation as property (even if it starts with something that looks like another namespace)
				$printmode = self::PRINT_PROP;
				$data = PropertyValue::makeUserProperty( $printRequestLabel );
				if ( !$data->isValid() ) { // not a property; give up
					return null;
				}
				$label = $showMode ? '' : $data->getWikiValue();  // default
			}
		}

		if ( count( $propparts ) == 1 ) { // no outputformat found, leave empty
			$propparts[] = false;
		} elseif ( trim( $propparts[1] ) === '' ) { // "plain printout", avoid empty string to avoid confusions with "false"
			$propparts[1] = '-';
		}

		if ( count( $parts ) > 1 ) { // label found, use this instead of default
			$label = trim( $parts[1] );
		}

		try {
			return new PrintRequest( $printmode, $label, $data, trim( $propparts[1] ) );
		} catch ( InvalidArgumentException $e ) { // something still went wrong; give up
			return null;
		}
	}

	private static function isCategory( $text ) {
		return Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY ) == mb_convert_case( $text, MB_CASE_TITLE ) ||
		$text == 'Category';
	}

	private static function doSplitText( $text ) {
		// 1464
		// Temporary encode "=" within a <> entity (<span>...</span>)
		$text = preg_replace_callback( "/(<(.*?)>(.*?)>)/u", function( $matches ) {
			foreach ( $matches as $match ) {
				return str_replace( array( '=' ), array( '-3D' ), $match );
			}
		}, $text );

		$parts = explode( '=', $text, 2 );

		// Restore temporary encoding
		$parts[0] = str_replace( array( '-3D' ), array( '=' ), $parts[0] );

		$propparts = explode( '#', $parts[0], 2 );
		$printRequestLabel = trim( $propparts[0] );

		return array( $parts, $propparts, $printRequestLabel );
	}

}
