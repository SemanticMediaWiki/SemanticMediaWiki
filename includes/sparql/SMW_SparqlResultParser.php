<?php
/**
 * Parser class for the SPARQL XML result format.
 * 
 * @file
 * @ingroup SMWSparql
 * 
 * @author Markus Krötzsch
 */

/**
 * Class for parsing SPARQL results in XML format.
 *
 * @since 1.6
 *
 * @ingroup SMWSparql
 */
class SMWSparqlResultParser {

	/**
	 * Associative array mapping SPARQL variable names to column indices.
	 * @var array of integer
	 */
	protected $m_header;

	/**
	 * List of result rows. Individual entries can be null if a cell in the
	 * SPARQL result table is empty (this is different from finding a blank
	 * node).
	 * @var array of array of (SMWExpElement or null)
	 */
	protected $m_data;

	/**
	 * List of comment strings found in the XML file (without surrounding
	 * markup, i.e. the actual string only).
	 * @var array of string
	 */
	protected $m_comments;

	/**
	 * Stack of open XML tags during parsing.
	 * @var array of string
	 */
	protected $m_xml_opentags;
	/**
	 * Integer index of the column that the current result binding fills.
	 * @var integer
	 */
	protected $m_xml_bindidx;
	/**
	 * Datatype URI for the current literal, or empty if none.
	 * @var string
	 */
	protected $m_xml_datatype;

	/**
	 * Parse the given XML result and return an SMWSparqlResultWrapper for
	 * the contained data.
	 *
	 * @param $xmlQueryResult string
	 */
	public function makeResultFromXml( $xmlQueryResult ) {
		$parser = xml_parser_create();
		xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 0 );
		xml_parser_set_option( $parser, XML_OPTION_TARGET_ENCODING, 'UTF-8' );
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_object( $parser, $this );
		xml_set_element_handler( $parser, 'xmlHandleOpen', 'xmlHandleClose' );
		xml_set_character_data_handler($parser, 'xmlHandleCData' );
		xml_set_default_handler( $parser, 'xmlHandleDefault' );
		//xml_set_start_namespace_decl_handler($parser, 'xmlHandleNsDeclaration' );

		$this->m_xml_opentags = array();
		$this->m_header = array();
		$this->m_data = array();
		$this->m_comments = array();

		xml_parse( $parser, $xmlQueryResult, true );

		xml_parser_free( $parser );

		return new SMWSparqlResultWrapper( $this->m_header, $this->m_data, $this->m_comments );
	}

	protected function xmlHandleDefault( $parser, $data ) {
		if ( substr( $data, 0, 4 ) == '<!--' ) {
			$comment = substr( $data, 4, strlen( $data ) - 7 );
			$this->m_comments[] = trim( $comment );
		}
	}

	/**
	 * Handle an opening XML tag during parsing.
	 * @see xml_set_element_handler
	 */
	protected function xmlHandleOpen( $parser, $tagName, $attributes ) {
		$prevTag = end( $this->m_xml_opentags );
		$this->m_xml_opentags[] = $tagName;
		if ( ( $tagName == 'binding' ) && ( $prevTag == 'result' ) ) {
			if ( ( array_key_exists( 'name', $attributes ) ) &&
			     ( array_key_exists( $attributes['name'], $this->m_header ) ) ) {
				 $this->m_xml_bindidx = $this->m_header[$attributes['name']];
			}
		} elseif ( ( $tagName == 'result' ) && ( $prevTag == 'results' ) ) {
			$row = array();
			for ( $i = 0; $i < count( $this->m_header ); ++$i ) {
				$row[$i] = null;
			}
			$this->m_data[] = $row;
		} elseif ( ( $tagName == 'literal' ) && ( $prevTag == 'binding' ) ) {
			if ( array_key_exists( 'datatype', $attributes ) ) {
				$this->m_xml_datatype = $attributes['datatype'];
			} else {
				$this->m_xml_datatype = false;
			}
			/// TODO handle xml:lang attributes here as well?
		} elseif ( ( $tagName == 'variable' ) && ( $prevTag == 'head' ) ) {
			if ( array_key_exists( 'name', $attributes ) ) {
				$this->m_header[$attributes['name']] = count( $this->m_header );
			}
		}
	}

	/**
	 * Handle a closing XML tag during parsing.
	 * @see xml_set_element_handler
	 */
	protected function xmlHandleClose( $parser, $tagName ) {
		array_pop( $this->m_xml_opentags );
	}

	/**
	 * Handle XML character data during parsing.
	 * @see xml_set_character_data_handler
	 */
	protected function xmlHandleCData( $parser, $dataString ) {
		$prevTag = end( $this->m_xml_opentags );
		$rowcount = count( $this->m_data );
		if ( $prevTag == 'uri' ) {
			$this->m_data[$rowcount-1][$this->m_xml_bindidx] = new SMWExpResource( $dataString );
		} elseif ( $prevTag == 'literal' ) {
			$this->m_data[$rowcount-1][$this->m_xml_bindidx] = new SMWExpLiteral( $dataString, $this->m_xml_datatype );
		} elseif ( $prevTag == 'bnode' ) {
			$this->m_data[$rowcount-1][$this->m_xml_bindidx] = new SMWExpResource( '_' . $dataString );
		} elseif ( $prevTag == 'boolean' ) { // no "results" in this case
			$literal = new SMWExpLiteral( $dataString, 'http://www.w3.org/2001/XMLSchema#boolean' );
			$this->m_data = array( array( $literal ) );
			$this->m_header = array( '' => 0 );
		}
	}

}