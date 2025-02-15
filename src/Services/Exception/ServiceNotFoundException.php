<?php

namespace SMW\Services\Exception;

use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
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
