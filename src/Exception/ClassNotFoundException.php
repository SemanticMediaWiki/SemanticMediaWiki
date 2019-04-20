<?php

namespace SMW\Exception;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ClassNotFoundException extends RuntimeException {

	/**
	 * @since  3.1
	 *
	 * @param string $class
	 */
	public function __construct( $class ) {
		parent::__construct( "`$class` does not exist." );
	}

}
