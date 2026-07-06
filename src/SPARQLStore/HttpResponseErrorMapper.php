<?php

namespace SMW\SPARQLStore;

use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;

/**
 * Post-processing for a bad inbound responses
 *
 * @ingroup Sparql
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class HttpResponseErrorMapper {

	/**
	 * Map an HTTP error response to an appropriate exception, or return
	 * silently for errors that should be handled gracefully.
	 *
	 * @since 2.0
	 *
	 * @param int $httpStatusCode HTTP status code (0 for connection failures)
	 *
	 * @return void
	 * @throws BadHttpEndpointResponseException
	 */
	public function mapErrorResponse(
		int $httpStatusCode,
		string $endpoint,
		string $sparql
	): void {
		if ( $httpStatusCode === 0 ) {
			// Connection-level failure (refused, timeout, DNS, etc.)
			// Fail gracefully — caller returns error result
			return;
		}

		if ( $httpStatusCode === 400 ) {
			throw new BadHttpEndpointResponseException(
				BadHttpEndpointResponseException::ERROR_MALFORMED,
				$sparql,
				$endpoint,
				$httpStatusCode
			);
		}

		if ( $httpStatusCode === 500 ) {
			throw new BadHttpEndpointResponseException(
				BadHttpEndpointResponseException::ERROR_REFUSED,
				$sparql,
				$endpoint,
				$httpStatusCode
			);
		}

		if ( $httpStatusCode === 404 ) {
			// Endpoint not found, maybe down; fail gracefully
			return;
		}

		throw new BadHttpEndpointResponseException(
			BadHttpEndpointResponseException::ERROR_OTHER,
			$sparql,
			$endpoint,
			$httpStatusCode
		);
	}
}
