<?php

namespace SMW\SPARQLStore\Exception;

use Exception;

/**
 * @ingroup Sparql
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class HttpEndpointConnectionException extends Exception {

	/**
	 * @since  2.1
	 */
	public function __construct( string $endpoint, int $errorCode, string $errorText = '' ) {
		parent::__construct(
			"Failed to communicate with $endpoint (endpoint), HTTP error: $errorCode" .
			( $errorText !== '' ? " ($errorText)" : '' ) . "\n"
		);
	}

}
