<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\Exception\XmlParserException;

use SMWExpResource as ExpResource;
use SMWExpLiteral as ExpLiteral;

/**
 * Class for parsing SPARQL results in XML format
 *
 * @ingroup Sparql
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class RawResultParser {

	/**
	 * @var resource
	 */
	private $parser;

	/**
	 * Associative array mapping SPARQL variable names to column indices.
	 * @var array of integer
	 */
	private $header;

	/**
	 * List of result rows. Individual entries can be null if a cell in the
	 * SPARQL result table is empty (this is different from finding a blank
	 * node).
	 * @var array of array of (SMWExpElement or null)
	 */
	private $data;

	/**
	 * List of comment strings found in the XML file (without surrounding
	 * markup, i.e. the actual string only).
	 * @var array of string
	 */
	private $comments;

	/**
	 * Stack of open XML tags during parsing.
	 * @var array of string
	 */
	private $xmlOpenTags;

	/**
	 * Integer index of the column that the current result binding fills.
	 * @var integer
	 */
	private $xmlBindIndex;

	/**
	 * Datatype URI for the current literal, or empty if none.
	 * @var string
	 */
	private $currentDataType;

	/**
	 * @since 2.0
	 */
	public function __construct() {
		$this->parser = xml_parser_create();

		xml_parser_set_option( $this->parser, XML_OPTION_SKIP_WHITE, 0 );
		xml_parser_set_option( $this->parser, XML_OPTION_TARGET_ENCODING, 'UTF-8' );
		xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_object( $this->parser, $this );
		xml_set_element_handler( $this->parser, 'handleOpenElement', 'handleCloseElement' );
		xml_set_character_data_handler( $this->parser, 'handleCharacterData' );
		xml_set_default_handler( $this->parser, 'handleDefault' );
		//xml_set_start_namespace_decl_handler($parser, 'handleNsDeclaration' );
	}

	/**
	 * @since 2.0
	 */
	public function __destruct() {
		xml_parser_free( $this->parser );
	}

	/**
	 * Parse the given XML result and return an FederateResultSet for
	 * the contained data.
	 *
	 * @param string $rawData
	 *
	 * @return FederateResultSet
	 * @throws XmlParserException
	 */
	public function parse( $rawResult ) {

		$this->xmlOpenTags = array();
		$this->header = array();
		$this->data = array();
		$this->comments = array();

		// #626 Virtuoso
		if ( $rawResult == 'true' ) {
			$this->data = array( array( new ExpLiteral( 'true', 'http://www.w3.org/2001/XMLSchema#boolean' ) ) );
		}

		// #474 Virtuoso allows `false` to be a valid raw result
		if ( $rawResult == 'false' || $rawResult == 'true' || is_bool( $rawResult ) || $this->parseXml( $rawResult ) ) {
			return new FederateResultSet(
				$this->header,
				$this->data,
				$this->comments
			);
		}

		throw new XmlParserException(
			$this->getLastError(),
			$this->getLastLineNumber(),
			$this->getLastColumnNumber()
		);
	}

	private function parseXml( $xmlResultData ) {
		return xml_parse( $this->parser, $xmlResultData , true );
	}

	private function getLastError() {
		return xml_error_string( xml_get_error_code( $this->parser ) );
	}

	private function getLastLineNumber() {
		return xml_get_current_line_number( $this->parser );
	}

	private function getLastColumnNumber() {
		return xml_get_current_column_number ( $this->parser );
	}

	private function handleDefault( $parser, $data ) {
		if ( substr( $data, 0, 4 ) == '<!--' ) {
			$comment = substr( $data, 4, strlen( $data ) - 7 );
			$this->comments[] = trim( $comment );
		}
	}

	/**
	 * @see xml_set_element_handler
	 */
	private function handleOpenElement( $parser, $elementTag, $attributes ) {

		$this->currentDataType = '';

		$prevTag = end( $this->xmlOpenTags );
		$this->xmlOpenTags[] = $elementTag;

		switch ( $elementTag ) {
			case 'binding' && ( $prevTag == 'result' ):
					if ( ( array_key_exists( 'name', $attributes ) ) &&
					     ( array_key_exists( $attributes['name'], $this->header ) ) ) {
						 $this->xmlBindIndex = $this->header[$attributes['name']];
					}
				break;
			case 'result' && ( $prevTag == 'results' ):
				$row = array();

				for ( $i = 0; $i < count( $this->header ); ++$i ) {
					$row[$i] = null;
				}

				$this->data[] = $row;
				break;
			case  'literal' && ( $prevTag == 'binding' ):
				if ( array_key_exists( 'datatype', $attributes ) ) {
					$this->currentDataType = $attributes['datatype'];
				}
				/// TODO handle xml:lang attributes here as well?
				break;
			case  'variable' && ( $prevTag == 'head' ):
				if ( array_key_exists( 'name', $attributes ) ) {
					$this->header[$attributes['name']] = count( $this->header );
				}
				break;
		}
	}

	/**
	 * @see xml_set_element_handler
	 */
	private function handleCloseElement( $parser, $elementTag ) {
		array_pop( $this->xmlOpenTags );
	}

	/**
	 * @see xml_set_character_data_handler
	 */
	private function handleCharacterData( $parser, $characterData ) {

		$prevTag = end( $this->xmlOpenTags );
		$rowcount = count( $this->data );

		switch ( $prevTag ) {
			case 'uri':
				$this->data[$rowcount-1][$this->xmlBindIndex] = new ExpResource( $characterData );
				break;
			case 'literal':
				$this->data[$rowcount-1][$this->xmlBindIndex] = new ExpLiteral( $characterData, $this->currentDataType );
				break;
			case 'bnode':
				$this->data[$rowcount-1][$this->xmlBindIndex] = new ExpResource( '_' . $characterData );
				break;
			case 'boolean':
				// no "results" in this case
				$literal = new ExpLiteral( $characterData, 'http://www.w3.org/2001/XMLSchema#boolean' );

				// ?? Really !!
				$this->data = array( array( $literal ) );
				$this->header = array( '' => 0 );
				break;
		}
	}

}
