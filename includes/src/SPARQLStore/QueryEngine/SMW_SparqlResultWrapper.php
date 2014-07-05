<?php
/**
 * Class for representing SPARQL query results.
 * 
 * @file
 * @ingroup SMWSparql
 * 
 * @author Markus Krötzsch
 */

/**
 * Class for accessing SPARQL query results in a unified form. The data is
 * structured in tabular form, with each cell containing some SMWExpElement.
 * Rows should always have the same number of columns, but the datatype of the
 * cells in each column may not be uniform throughout the result.
 *
 * @since 1.6
 *
 * @ingroup SMWSparql
 */
class SMWSparqlResultWrapper implements Iterator {

	/// Error code: no errors occurred.
	const ERROR_NOERROR     = 0;
	/// Error code: service unreachable; result will be empty
	const ERROR_UNREACHABLE = 1;
	/// Error code: results might be incomplete (e.g. due to some resource limit being reached)
	const ERROR_INCOMPLETE = 2;

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
	 * Error code.
	 * @var integer
	 */
	protected $m_errorCode;

	/**
	 * Initialise a result set from a result string in SPARQL XML format.
	 *
	 * @param $header array mapping SPARQL variable names to column indices
	 * @param $data array of array of (SMWExpElement or null)
	 * @param $comments array of string comments if the result contained any
	 * @param $errorCode integer an error code
	 */
	public function __construct( array $header, array $data, array $comments = array(), $errorCode = self::ERROR_NOERROR ) {
		$this->m_header    = $header;
		$this->m_data      = $data;
		$this->m_comments  = $comments;
		$this->m_errorCode = $errorCode;
		reset( $this->m_data );
	}

	/**
	 * Get the number of rows in the result object.
	 *
	 * @return interger number of result rows
	 */
	public function numRows() {
		return count( $this->m_data );
	}

	/**
	 * Return error code. SMWSparqlResultWrapper::ERROR_NOERROR (0)
	 * indicates that no error occurred.
	 *
	 * @return integer error code
	 */
	public function getErrorCode() {
		return $this->m_errorCode;
	}

	/**
	 * Set the error code of this result set. This is used for allowing
	 * callers to add additional errors discovered only later on. It does
	 * not allow removing existing errors, since it will not accept 
	 * SMWSparqlResultWrapper::ERROR_NOERROR as a parameter.
	 *
	 * @param $errorCode integer error code
	 */
	public function setErrorCode( $errorCode ) {
		if ( $errorCode != self::ERROR_NOERROR ) {
			$this->m_errorCode = $errorCode;
		}
	}

	/**
	 * Return a list of comment strings found in the SPARQL result. Comments
	 * are used by some RDF stores to provide additional information or
	 * warnings that can thus be accessed. 
	 *
	 * @return array of string
	 */
	public function getComments() {
		return $this->m_comments;
	}

	/**
	 * Check if the result is what one would get for a SPARQL ASK query
	 * that returned true. Returns false in all other cases (including
	 * the case that the results do not look at all like the result of
	 * an ASK query).
	 *
	 * @return boolean
	 */
	public function isBooleanTrue() {
		if ( count( $this->m_data ) == 1 ) {
			$row = reset( $this->m_data );
			$expElement = reset( $row );
			if ( ( count( $row ) == 1 ) && ( $expElement instanceof SMWExpLiteral ) &&
			     ( $expElement->getLexicalForm() == 'true' ) &&
			     ( $expElement->getDatatype() == 'http://www.w3.org/2001/XMLSchema#boolean' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the result is what one would get for a SPARQL SELECT COUNT
	 * query, and return the corresponding integer value. Returns 0 in all
	 * other cases (including the case that the results do not look at all
	 * like the result of a SELECT COUNT query).
	 *
	 * @return integer
	 */
	public function getNumericValue() {
		if ( count( $this->m_data ) == 1 ) {
			$row = reset( $this->m_data );
			$expElement = reset( $row );
			if ( ( count( $row ) == 1 ) && ( $expElement instanceof SMWExpLiteral ) &&
			     ( $expElement->getDatatype() == 'http://www.w3.org/2001/XMLSchema#integer' ) ) {
				return (int)$expElement->getLexicalForm();
			}
		}
		return 0;
	}

	/**
	 * Reset iterator to position 0. Standard method of Iterator.
	 */
	public function rewind() {
		reset( $this->m_data );
	}

	/**
	 * Return the current result row. Standard method of Iterator.
	 *
	 * @return array of (SMWExpElement or null), or false at end of data
	 */
	public function current() {
		return current( $this->m_data );
	}

	/**
	 * Return the next result row and advance the internal pointer.
	 * Standard method of Iterator.
	 *
	 * @return array of (SMWExpElement or null), or false at end of data
	 */
	public function next() {
		return next( $this->m_data );
	}

	/**
	 * Return the next result row and advance the internal pointer.
	 * Standard method of Iterator.
	 *
	 * @return array of (SMWExpElement or null), or false at end of data
	 */
	public function key() {
		return key( $this->m_data );
	}

	/**
	 * Return true if the internal pointer refers to a valid element.
	 * Standard method of Iterator.
	 *
	 * @return boolean
	 */
	public function valid() {
		return ( current( $this->m_data ) !== false );
	}

}