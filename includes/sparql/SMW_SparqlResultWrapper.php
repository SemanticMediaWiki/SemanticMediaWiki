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
 * @ingroup SMWSparql
 */
class SMWSparqlResultWrapper implements Iterator {

	/// Error code: no errors occurred.
	const ERROR_NOERROR     = 0;
	/// Error code: service unreachable; result will be empty
	const ERROR_UNREACHABLE = 1;

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
	 * Error code.
	 * @var integer
	 */
	protected $m_errorCode;

	/**
	 * Initialise a result set from a result string in SPARQL XML format.
	 *
	 * @param $header array mapping SPARQL variable names to column indices
	 * @param $data array of array of (SMWExpElement or null)
	 */
	public function __construct( array $header, array $data, $errorCode = self::ERROR_NOERROR ) {
		$this->m_header    = $header;
		$this->m_data      = $data;
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
	 * @return interger error code
	 */
	public function getErrorCode() {
		return $this->m_errorCode;
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