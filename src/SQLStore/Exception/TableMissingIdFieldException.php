<?php

namespace SMW\SQLStore\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TableMissingIdFieldException extends RuntimeException {

	/**
	 * @since 3.0
	 */
	public function __construct( $name ) {
		parent::__construct( "Operation is not supported for a table ({$name}) without subject IDs." );
	}

}
