<?php

namespace SMW\Elastic\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
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
