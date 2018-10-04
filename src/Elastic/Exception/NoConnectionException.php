<?php

namespace SMW\Elastic\Exception;

use RuntimeException;

/**
 * @license GNU GPL v2+
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
