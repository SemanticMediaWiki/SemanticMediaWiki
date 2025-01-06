<?php

namespace SMW\Elastic\Exception;

use RuntimeException;
use SMW\Utils\ErrorCodeFormatter;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class InvalidJSONException extends RuntimeException {

	/**
	 * @since 3.0
	 */
	public function __construct( $error, $content = '' ) {
		parent::__construct( ErrorCodeFormatter::getMessageFromJsonErrorCode( $error ) . " caused by: $content" );
	}

}
