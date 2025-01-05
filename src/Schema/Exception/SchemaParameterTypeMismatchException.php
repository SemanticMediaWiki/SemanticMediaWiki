<?php

namespace SMW\Schema\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaParameterTypeMismatchException extends RuntimeException {

	/**
	 * @since 3.1
	 *
	 * @param string $parameter
	 * @param string $type
	 */
	public function __construct( $parameter, $type ) {
		parent::__construct( "Expected $type for the `$parameter` parameter." );
	}

}
