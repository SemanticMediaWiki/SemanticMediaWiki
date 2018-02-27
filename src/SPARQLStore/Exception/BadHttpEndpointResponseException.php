<?php

namespace SMW\SPARQLStore\Exception;

/**
 * Class to escalate SPARQL query errors to the interface. We only do this for
 * malformed queries, permission issues, etc. Connection problems are usually
 * ignored so as to keep the wiki running even if the SPARQL backend is down.
 *
 * @ingroup Sparql
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class BadHttpEndpointResponseException extends \Exception {

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
	/// Error code: required service not known
	const ERROR_NOSERVICE      = 6;

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
			case self::ERROR_NOSERVICE:
				$errorName = 'Required service has not been defined';
				break;
			default:
				$errorCode = self::ERROR_OTHER;
				$errorName = 'Unkown error';
		}

		$message = "A SPARQL query error has occurred\n" .
		  "Query: $queryText\n" .
		  "Error: $errorName\n" .
		  "Endpoint: $endpoint\n" .
		  "HTTP response code: $httpCode\n";

		parent::__construct( $message, $errorCode );
		$this->errorCode = $errorCode;
		$this->queryText = $queryText;
	}

}
