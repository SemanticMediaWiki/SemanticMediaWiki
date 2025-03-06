<?php

namespace SMW\Exception;

use RuntimeException;

/**
 * @license GPL-2.0-or-later
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
