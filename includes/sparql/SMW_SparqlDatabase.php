<?php
/**
 * Base classes for SMW's binding to SPARQL stores.
 * 
 * @file
 * @ingroup SMWSparql
 * 
 * @author Markus Krötzsch
 */

/**
 * This group contains all parts of SMW that relate to communication with
 * storage backends and clients via SPARQL.
 * 
 * @defgroup SMWSparql SWMSparql
 * @ingroup SMW
 */

/**
 * Class to escalate SPARQL query errors to the interface. We only do this for
 * malformed queries, permission issues, etc. Connection problems are usually
 * ignored so as to keep the wiki running even if the SPARQL backend is down.
 *
 * @ingroup SMWSparql
 */
class SMWSparqlDatabaseError extends Exception {

	/// Error code: malformed query
	const ERROR_MALFORMED      = 1;
	/// Error code: service refused to handle the request
	const ERROR_REFUSED        = 2;
	/// Error code: the query required a graph that does not exist
	const ERROR_GRAPH_NOEXISTS = 3;
	/// Error code: some existing graph should not exist to run this query
	const ERROR_GRAPH_EXISTS   = 4;
	/// Error code: unknown error
	const ERROR_OTHER          = 5;

	/**
	 * SPARQL query that caused the problem.
	 * @var string
	 */
	public $queryText;
	/**
	 * Error code
	 * @var integer
	 */
	public $errorCode;
	
	/**
	 * Constructor that creates an error message based on the given data.
	 *
	 * @param $errorCode integer error code as defined in this class
	 * @param $queryText string with the original SPARQL query/update
	 * @param $endpoint string URL of the endpoint
	 * @param $httpCode mixed integer HTTP error code or some string to print there
	 */
	function __construct( $errorCode, $queryText, $endpoint, $httpCode = '<not given>' ) {
		switch ( $errorCode ) {
			case self::ERROR_MALFORMED:
				$errorName = 'Malformed query';
			break;
			case self::ERROR_REFUSED:
				$errorName = 'Query refused';
			break;
			case self::ERROR_GRAPH_NOEXISTS:
				$errorName = 'Graph not existing';
			break;
			case self::ERROR_GRAPH_EXISTS:
				$errorName = 'Graph already exists';
			break;
			case self::ERROR_OTHER: default:
				$errorName = 'Unkown error';
			break;
		}
		$message = "A SPARQL query error has occurred\n" .
		  "Query: $queryText\n" .
		  "Error: $errorName\n" .
		  "Endpoint: $endpoint\n" . 
		  "HTTP response code: $httpCode\n";

		parent::__construct( $message );
		$this->errorCode = $errorCode;
		$this->queryText = $queryText;
	}

}

/**
 * Basic database connector for exchanging data via SPARQL.
 * 
 * @ingroup SMWSparql
 * 
 * @author Markus Krötzsch
 */
class SMWSparqlDatabase {

	/**
	 * The URL of the endpoint for executing read queries.
	 * @var string
	 */
	protected $m_queryEndpoint;

	/**
	 * The URL of the endpoint for executing update queries, or empty if
	 * update is not allowed/supported.
	 * @var string
	 */
	protected $m_updateEndpoint;

	/**
	 * The curl handle we use for communicating. We reuse the same handle
	 * throughout as this safes some initialization effort.
	 * @var resource
	 */
	protected $m_curlhandle;

	/**
	 * Constructor
	 *
	 * @param $queryEndpoint string of URL of query service (reading)
	 * @param $updateEndpoint string of URL of update service (writing)
	 */
	public function __construct( $queryEndpoint, $updateEndpoint = '' ) {
		$this->m_queryEndpoint = $queryEndpoint;
		$this->m_updateEndpoint = $updateEndpoint;
		$this->m_curlhandle = curl_init();
		curl_setopt( $this->m_curlhandle, CURLOPT_FORBID_REUSE, false );
		curl_setopt( $this->m_curlhandle, CURLOPT_FRESH_CONNECT, false );
		curl_setopt( $this->m_curlhandle, CURLOPT_RETURNTRANSFER, true ); // put result into variable
		curl_setopt( $this->m_curlhandle, CURLOPT_CONNECTTIMEOUT, 10 ); // timeout in seconds
		curl_setopt( $this->m_curlhandle, CURLOPT_FAILONERROR, true );
	}

	/**
	 * Check if the database can be contacted.
	 *
	 * @param $pingQueryEndpoint boolean true if the query endpoint should
	 * be pinged, false if the update enpoint should be pinged
	 * @return boolean to indicate success
	 * @todo SPARQL endpoints sometimes return errors if no (valid) query
	 * is posted. The current implementation tries to catch this, but this
	 * might not be entirely correct. Especially, the SPARQL 1.1 HTTP error
	 * codes for Update are not defined yet (April 15 2011).
	 */
	public function ping( $pingQueryEndpoint = true ){
		if ( $pingQueryEndpoint ) {
			curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_queryEndpoint );
			curl_setopt( $this->m_curlhandle, CURLOPT_NOBODY, true );
		} else {
			if ( $this->m_updateEndpoint == '' ) {
				return false;
			}
			curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_updateEndpoint );
			curl_setopt( $this->m_curlhandle, CURLOPT_NOBODY, false ); // 4Store gives 404 instead of 500 with CURLOPT_NOBODY
		}
		
		curl_exec( $this->m_curlhandle );

		if ( curl_errno( $this->m_curlhandle ) == 0 ) {
			return true;
		} else {
			$httpCode = curl_getinfo( $this->m_curlhandle, CURLINFO_HTTP_CODE );
			return ( ( $httpCode == 500 ) || ( $httpCode == 400 ) ); // valid HTTP responses from a complaining SPARQL endpoint that is alive and kicking
		}
	}

	/**
	 * Execute a SPARQL query and return an SMWSparqlResultWrapper object
	 * that contains the results. The method throws exceptions based on
	 * SMWSparqlDatabase::throwSparqlErrors(). If errors occur and this
	 * method does not throw anything, then an empty result with an error
	 * code is returned.
	 *
	 * @param $sparql string with the complete SPARQL query (SELECT or ASK)
	 * @return SMWSparqlResultWrapper
	 */
	public function doQuery( $sparql ) {
		curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_queryEndpoint );
		curl_setopt( $this->m_curlhandle, CURLOPT_POST, true );
		$parameterString = "query=" . urlencode( $sparql );
		curl_setopt( $this->m_curlhandle, CURLOPT_POSTFIELDS, $parameterString );

		$xmlResult = curl_exec( $this->m_curlhandle );

		if ( curl_errno( $this->m_curlhandle ) == 0 ) {
			$xmlParser = new SMWSparqlResultParser();
			return $xmlParser->makeResultFromXml( $xmlResult );
		} else {
			$this->throwSparqlErrors( $this->m_updateEndpoint, $sparql );
			return new SMWSparqlResultWrapper( array(), array(), SMWSparqlResultWrapper::ERROR_UNREACHABLE );
		}
	}

	/**
	 * Execute a SPARQL update and return a boolean to indicate if the
	 * operations was sucessfull. The method throws exceptions based on
	 * SMWSparqlDatabase::throwSparqlErrors(). If errors occur and this
	 * method does not throw anything, then false is returned.
	 *
	 * @param $sparql string with the complete SPARQL update query (INSERT or DELETE)
	 * @return boolean
	 */
	public function doUpdate( $sparql ) {
		if ( $this->m_updateEndpoint == '' ) {
			throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_READONLY, $sparql, $this->m_queryEndpoint, $error );
		}
		curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_updateEndpoint );
		curl_setopt( $this->m_curlhandle, CURLOPT_POST, true );
		$parameterString = "update=" . urlencode( $sparql );
		curl_setopt( $this->m_curlhandle, CURLOPT_POSTFIELDS, $parameterString );

		$xmlResult = curl_exec( $this->m_curlhandle );

		if ( curl_errno( $this->m_curlhandle ) == 0 ) {
			$xmlParser = new SMWSparqlResultParser();
			return true;
		} else {
			$this->throwSparqlErrors( $this->m_updateEndpoint, $sparql );
			return false;
		}
	}

	/**
	 * Decide what to make of the errors reported by the Curl handler.
	 * Either throw a suitable exception or fall through if the error
	 * should be handled gracefully. It is attempted to throw exceptions
	 * for all errors that can generally be prevented by proper coding or
	 * configuration (e.g. query syntax errors), and to be silent on all
	 * errors that might be caused by network issues or temporary
	 * overloading of the server. In this case, calling methods rather
	 * return something that helps to make the best out of the situation.
	 *
	 * @param $endpoint string URL of endpoint that was used
	 * @param $sparql string query that caused the problem
	 */
	protected function throwSparqlErrors( $endpoint, $sparql ) {
		$error = curl_errno( $this->m_curlhandle );
		if ( $error == 22 ) { // 22 == CURLE_HTTP_RETURNED_ERROR, but this constant is not defined in PHP, it seems
			$httpCode = curl_getinfo( $this->m_curlhandle, CURLINFO_HTTP_CODE );
			/// TODO We are guessing the meaning of HTTP codes here -- the SPARQL 1.1 spec does not yet provide this information for updates (April 15 2011)
			if ( $httpCode == 400 ) { // malformed query
				throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_MALFORMED, $sparql, $endpoint, $error );
			} elseif ( $httpCode == 500 ) { // query refused; maybe fail gracefully here (depending on how stores use this)
				throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_REFUSED, $sparql, $endpoint, $error );
			} elseif ( $httpCode == 404 ) {
				return; // endpoint not found, maybe down; fail gracefully
			} else {
				throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_OTHER, $sparql, $endpoint, $error );
			}
		} elseif ( $error == CURLE_COULDNT_CONNECT ) {
			retur; // fail gracefully if backend is down
		} else {
			throw new Exception( "Failed to communicate with SPARQL store.\n Endpoint: " . $endpoint . "\n Curl error: '" . curl_error( $this->m_curlhandle ) . "' ($error)" );
		}
	}

}

