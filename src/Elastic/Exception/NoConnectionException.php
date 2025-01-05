<?php

namespace SMW\Elastic\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class NoConnectionException extends RuntimeException {

	/**
	 * @since 3.0
	 */
	public function __construct() {
		parent::__construct(
			"Could not establish a connection to Elasticsearch using " . json_encode( $GLOBALS['smwgElasticsearchEndpoints'] )
		);
	}

}
