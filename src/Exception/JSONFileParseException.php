<?php

namespace SMW\Exception;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class JSONFileParseException extends JSONParseException {

	/**
	 * @since 3.2
	 *
	 * @param string $file
	 * @param string $errMsg
	 */
	public function __construct( $file, $errMsg = '' ) {
		$this->message = $this->buildMessage( $file, $errMsg );
	}

	private function buildMessage( $file, $errMsg ) {

		if ( $errMsg === !'' ) {
			$message = "$errMsg in file $file caused by:";
		} else {
			$message = "JSON error in file $file caused by:";
		}

		if ( !is_readable( $file ) ) {
			return "$file is not readable!";
		}

		return "$message\n" . $this->getParseError( file_get_contents( $file ) );
	}

}
