<?php

namespace SMW\Services\Exception;

use InvalidArgumentException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ServiceNotFoundException extends InvalidArgumentException {

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function __construct( $service ) {
		parent::__construct( "`$service` is not registered as service!" );
	}

}
