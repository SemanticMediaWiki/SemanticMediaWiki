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
	public function __construct( $endpoint, $errorCode, $errorText ) {
		parent::__construct( "Failed to communicate to Endpoint: $endpoint\n" . "due to curl error: $errorCode ($errorText).\n" );
	}

}
