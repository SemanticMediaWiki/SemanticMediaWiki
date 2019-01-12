<?php

namespace SMW\Exception;

use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FileNotWritableException extends RuntimeException {

	/**
	 * @since 3.0
	 *
	 * @param string $file
	 */
	public function __construct( $file ) {
		parent::__construct( "$file is not writable." );
	}

}
