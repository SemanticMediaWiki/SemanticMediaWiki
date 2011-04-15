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
 * malformed queries or permission issues. Connection problems are usually
 * ignored so as to keep the wiki running even if the RDF backend is down.
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
	 * The URL of the endpoint to contact the database.
	 * @var string
	 */
	protected $m_endpoint;

	/**
	 * The curl handle we use for communicating.
	 * @var resource
	 */
	protected $m_curlhandle;

	/**
	 * Constructor.
	 * @param $endpoint string of URL to contact the database at
	 */
	public function __construct( $endpoint ) {
		$this->m_endpoint = $endpoint;
		$this->m_curlhandle = curl_init();
		curl_setopt( $this->m_curlhandle, CURLOPT_FORBID_REUSE, false );
		curl_setopt( $this->m_curlhandle, CURLOPT_FRESH_CONNECT, false );
		curl_setopt( $this->m_curlhandle, CURLOPT_RETURNTRANSFER, true ); // put result into variable
		curl_setopt( $this->m_curlhandle, CURLOPT_CONNECTTIMEOUT, 10 ); // timeout in seconds
		curl_setopt( $this->m_curlhandle, CURLOPT_FAILONERROR, true );
	}

	/**
	 * Check if the database can be contacted,
	 *
	 * @return boolean to indicate success
	 */
	public function ping(){
		curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_endpoint );
		curl_setopt( $this->m_curlhandle, CURLOPT_NOBODY, 1 );
		curl_exec( $this->m_curlhandle );
		return ( curl_errno( $this->m_curlhandle ) == 0 );
	}

	public function doQuery( $sparql ) {
		curl_setopt( $this->m_curlhandle, CURLOPT_URL, $this->m_endpoint );
		curl_setopt( $this->m_curlhandle, CURLOPT_POST, true );
		$parameterString = "query=" . urlencode( $sparql );
		curl_setopt( $this->m_curlhandle, CURLOPT_POSTFIELDS, $parameterString );
		$xmlResult = curl_exec( $this->m_curlhandle );
		$error = curl_errno( $this->m_curlhandle );
		if ( $error == 0 ) {
			$xmlParser = new SMWSparqlResultParser();
			$resultWrapper = $xmlParser->makeResultFromXml( $xmlResult );
		} elseif ( $error == CURLE_COULDNT_CONNECT ) { // fail gracefully if backend is down
			$resultWrapper = new SMWSparqlResultWrapper( array(), array(), SMWSparqlResultWrapper::ERROR_UNREACHABLE );
		} elseif ( $error == 22 ) { // 22 == CURLE_HTTP_RETURNED_ERROR, but this constant is not defined in PHP, it seems
			$httpCode = curl_getinfo( $this->m_curlhandle, CURLINFO_HTTP_CODE );
			if ( $httpCode == 400 ) { // malformed query
				throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_MALFORMED, $sparql, $this->m_endpoint, $error );
			} elseif ( $httpCode == 500 ) { // query refused; maybe fail gracefully here (depending on how stores use this)
				throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_REFUSED, $sparql, $this->m_endpoint, $error );
			} elseif ( $httpCode == 404 ) { // endpoint not found, maybe down; fail gracefully
				$resultWrapper = new SMWSparqlResultWrapper( array(), array(), SMWSparqlResultWrapper::ERROR_UNREACHABLE );
			} else {
				throw new SMWSparqlDatabaseError( SMWSparqlDatabaseError::ERROR_OTHER, $sparql, $this->m_endpoint, $error );
			}
		} else {
			throw new Exception( "Failed to communicate with SPARQL store.\n Endpoint: " . $this->m_endpoint . "\n Curl error: '" . curl_error( $this->m_curlhandle ) . "' ($error)" );
		}

		return $resultWrapper;
	}

}

