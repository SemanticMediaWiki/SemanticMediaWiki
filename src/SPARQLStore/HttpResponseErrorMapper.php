<?php

namespace SMW\SPARQLStore;

use Exception;
use Onoi\HttpRequest\HttpRequest;
use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;
use SMW\SPARQLStore\Exception\HttpEndpointConnectionException;

/**
 * Post-processing for a bad inbound responses
 *
 * @ingroup Sparql
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class HttpResponseErrorMapper {

	private $httpRequest = null;

	/**
	 * @since  2.0
	 *
	 * @param HttpRequest $httpRequest
	 */
	public function __construct( HttpRequest $httpRequest ) {
		$this->httpRequest = $httpRequest;
	}

	/**
	 * Either throw a suitable exception or fall through if the error should be
	 * handled gracefully. It is attempted to throw exceptions for all errors that
	 * can generally be prevented by proper coding or configuration (e.g. query
	 * syntax errors), and to be silent on all errors that might be caused by
	 * network issues or temporary overloading of the server. In this case, calling
	 * methods rather return something that helps to make the best out of the situation.
	 *
	 * @since 2.0
	 *
	 * @param $endpoint string URL of endpoint that was used
	 * @param $sparql string query that caused the problem
	 *
	 * @throws Exception
	 * @throws SparqlDatabaseException
	 */
	public function mapErrorResponse( $endpoint, $sparql ) {
		$error = $this->httpRequest->getLastErrorCode();

		switch ( $error ) {
			case 22: //	equals CURLE_HTTP_RETURNED_ERROR but this constant is not defined in PHP
				$this->createResponseToHttpError( $this->httpRequest->getLastTransferInfo( CURLINFO_HTTP_CODE ), $endpoint, $sparql );
				break;
			case 52:
			case CURLE_GOT_NOTHING:
				break; // happens when 4Store crashes, do not bother the wiki
			case CURLE_COULDNT_CONNECT:
				break; // fail gracefully if backend is down
			default:
				throw new HttpEndpointConnectionException(
					$endpoint,
					$error,
					$this->httpRequest->getLastError()
				);
		}
	}

	private function createResponseToHttpError( $httpCode, $endpoint, $sparql ) {

		/// TODO We are guessing the meaning of HTTP codes here -- the SPARQL 1.1 spec does not yet provide this information for updates (April 15 2011)

		if ( $httpCode == 400 ) { // malformed query
			throw new BadHttpEndpointResponseException( BadHttpEndpointResponseException::ERROR_MALFORMED, $sparql, $endpoint, $httpCode );
		} elseif ( $httpCode == 500 ) { // query refused; maybe fail gracefully here (depending on how stores use this)
			throw new BadHttpEndpointResponseException( BadHttpEndpointResponseException::ERROR_REFUSED, $sparql, $endpoint, $httpCode );
		} elseif ( $httpCode == 404 ) {
			return; // endpoint not found, maybe down; fail gracefully
		}

		throw new BadHttpEndpointResponseException( BadHttpEndpointResponseException::ERROR_OTHER, $sparql, $endpoint, $httpCode );
	}

}

