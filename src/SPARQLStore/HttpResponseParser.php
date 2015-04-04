<?php

namespace SMW\SPARQLStore;

/**
 * Provides an interface for which responses from a http client (repositor
 * connection) are parsed into a unified format
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
interface HttpResponseParser {

	/**
	 * @since 2.2
	 *
	 * @param string $response
	 *
	 * @return RepositoryResult
	 */
	public function parse( $response );

}
