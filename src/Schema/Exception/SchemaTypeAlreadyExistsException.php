<?php

namespace SMW\Schema\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class SchemaTypeAlreadyExistsException extends RuntimeException {

	/**
	 * @since 3.2
	 *
	 * @param string $type
	 */
	public function __construct( $type ) {
		parent::__construct( "$type is already registered as schema type." );
	}

}
