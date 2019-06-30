<?php

namespace SMW\Elastic\Exception;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MissingEndpointConfigException extends RuntimeException {

	/**
	 * @since 3.1
	 */
	public function __construct() {
		parent::__construct( 'Missing the `$smwgElasticsearchEndpoints` setting!' );
	}

}
