<?php

namespace SMW\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
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
