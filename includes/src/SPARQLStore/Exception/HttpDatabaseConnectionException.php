<?php

namespace SMW\SPARQLStore\Exception;

/**
 * @ingroup Sparql
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class HttpDatabaseConnectionException extends \Exception {

	/**
	 * @since  2.1
	 *
	 * @param string $endpoint
	 * @param integer $errorCode
	 * @param string $errorText
	 */
	function __construct( $endpoint, $errorCode, $errorText ) {

		$message = "Failed to communicate to Endpoint: $endpoint\n" . "Curl error: $errorCode with $errorText\n";

		parent::__construct( $message, $errorCode );
	}

}
