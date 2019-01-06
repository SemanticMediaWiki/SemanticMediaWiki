<?php

namespace SMW\Schema\Exception;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaConstructionFailedException extends RuntimeException {

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 */
	public function __construct( $type ) {
		parent::__construct( "$type couldn't construct a Schema instance!" );
	}

}
