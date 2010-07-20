<?php
/**
 * This file contains the class for defining "print requests", i.e. requests for output
 * informatoin to be included in query results.
 * @file
 * @ingroup SMWQuery
 * @author Markus Krötzsch
 */

/**
 * Container class for request for printout, as used in queries to
 * obtain additional information for the retrieved results.
 * @ingroup SMWQuery
 */
class SMWPrintRequest {
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
	 * @param $mode a constant defining what to printout
	 * @param $label the string label to describe this printout
	 * @param $data optional data for specifying some request, might be a property object, title, or something else; interpretation depends on $mode
	 * @param $outputformat optional string for specifying an output format, e.g. an output unit
	 * @param $params optional array of further, named parameters for the print request
	 */
	public function __construct( $mode, $label, $data = null, $outputformat = false, $params = null ) {
		$this->m_mode = $mode;
		$this->m_label = $label;
		$this->m_data = $data;
		$this->m_outputformat = $outputformat;
		if ( ( $mode == SMWPrintRequest::PRINT_CCAT ) && ( $outputformat == false ) ) {
			$this->m_outputformat = 'x'; // changed default for Boolean case
		}
		if ( $this->m_data instanceof SMWDataValue ) {
			// $this->m_data = clone $data; // we assume that the caller denotes the object ot us; else he needs provide us with a clone
			$this->m_data->setCaption( $label );
		}
		if ( null != $params ) $m_params = $params;
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
		if ( ( $linker === null ) || ( $this->m_label == '' ) ) {
			return htmlspecialchars( $this->m_label );
		}
		switch ( $this->m_mode ) {
			case SMWPrintRequest::PRINT_CATS:
				return htmlspecialchars( $this->m_label ); // TODO: link to Special:Categories
			case SMWPrintRequest::PRINT_CCAT:
				return $linker->makeLinkObj( $this->m_data, htmlspecialchars( $this->m_label ) );
			case SMWPrintRequest::PRINT_PROP:
				return $this->m_data->getShortHTMLText( $linker );
			case SMWPrintRequest::PRINT_THIS: default: return htmlspecialchars( $this->m_label );
		}
	}

	/**
	 * Obtain a Wiki-formatted representation of the label.
	 */
	public function getWikiText( $linked = false ) {
		if ( ( $linked === null ) || ( $linked === false ) || ( $this->m_label == '' ) ) {
			return $this->m_label;
		} else {
			switch ( $this->m_mode ) {
				case SMWPrintRequest::PRINT_CATS:
					return $this->m_label; // TODO: link to Special:Categories
				case SMWPrintRequest::PRINT_PROP:
					return $this->m_data->getShortWikiText( $linked );
				case SMWPrintRequest::PRINT_CCAT:
				return '[[:' . $this->m_data->getPrefixedText() . '|' . $this->m_label . ']]';
				case SMWPrintRequest::PRINT_THIS: default: return $this->m_label;
			}
		}
	}

	/**
	 * Convenience method for accessing the text in either HTML or Wiki format.
	 */
	public function getText( $outputmode, $linker = null ) {
		switch ( $outputmode ) {
			case SMW_OUTPUT_WIKI: return $this->getWikiText( $linker );
			case SMW_OUTPUT_HTML: case SMW_OUTPUT_FILE: default: return $this->getHTMLText( $linker );
		}
	}

	/**
	 * Return additional data related to the print request. The result might be
	 * an object of class SMWPropertyValue or Title, or simply NULL if no data
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
	 * Otherwise return FALSE.
	 */
	public function getTypeID() {
		if ( $this->m_typeid === false ) {
			if ( $this->m_mode == SMWPrintRequest::PRINT_PROP ) {
				$this->m_typeid = $this->m_data->getPropertyTypeID();
			} else {
				$this->m_typeid = '_wpg'; // return objects might be titles, but anyway
			}
		}
		return $this->m_typeid;
	}

	/**
	 * Return a hash string that is used to eliminate duplicate
	 * print requests. The hash also includes the chosen label,
	 * so it is possible to print the same date with different
	 * labels.
	 */
	public function getHash() {
		if ( $this->m_hash === false ) {
			$this->m_hash = $this->m_mode . ':' . $this->m_label . ':';
			
			if ( $this->m_data instanceof Title ) {
				$this->m_hash .= $this->m_data->getPrefixedText() . ':';
			} elseif ( $this->m_data instanceof SMWDataValue ) {
				$this->m_hash .= $this->m_data->getHash() . ':';
			}
			
			$this->m_hash .= $this->m_outputformat . ':' . implode( '|', $this->m_params );
		}
		
		return $this->m_hash;
	}

	/**
	 * Serialise this object like print requests given in \#ask.
	 * @param $params boolean that sets if the serialization should
	 *                include the extra print request parameters
	 */
	public function getSerialisation( $showparams = false ) {
		$parameters = '';
		if ( $showparams ) foreach ( $this->m_params as $key => $value ) {
			$parameters .= "|+" . $key . "=" . $value;
		}
		switch ( $this->m_mode ) {
			case SMWPrintRequest::PRINT_CATS:
				global $wgContLang;
				$catlabel = $wgContLang->getNSText( NS_CATEGORY );
				$result = '?' . $catlabel;
				if ( $this->m_label != $catlabel ) {
					$result .= '=' . $this->m_label;
				}
				return $result . $parameters;
			case SMWPrintRequest::PRINT_PROP: case SMWPrintRequest::PRINT_CCAT:
				if ( $this->m_mode == SMWPrintRequest::PRINT_CCAT ) {
					$printname = $this->m_data->getPrefixedText();
					$result = '?' . $printname;
					if ( $this->m_outputformat != 'x' ) {
						$result .= '#' . $this->m_outputformat;
					}
				} else {
					$printname = $this->m_data->getWikiValue();
					$result = '?' . $printname;
					if ( $this->m_outputformat != '' ) {
						$result .= '#' . $this->m_outputformat;
					}
				}
				if ( $printname != $this->m_label ) {
					$result .= '=' . $this->m_label;
				}
				return $result . $parameters;
			case SMWPrintRequest::PRINT_THIS: default: return ''; // no current serialisation
		}
	}

	/**
	 * Returns the value of a named parameter.
	 * @param $key string the name of the parameter key
	 * @return string Value of the paramer, if set (else FALSE)
	 */
	public function getParameter( $key ) {
		return ( array_key_exists( $key, $this->m_params ) ) ? $this->m_params[$key]:false;
	}

	/**
	 * Returns the array of parameters, where a string is mapped to a string.
	 * @return array Map of parameter names to values.
	 */
	public function getParameters() {
		return $this->m_params;
	}

	/**
	 * Sets a print request parameter.
	 * @param $key string Name of the parameter
	 * @param $value string Value for the parameter
	 */
	public function setParameter( $key, $value ) {
		$this->m_params[$key] = $value;
	}
}