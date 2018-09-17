<?php

namespace SMW\Utils;

/**
 * Convenience method to retrieved stringified error codes.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ErrorCodeFormatter {

	/**
	 * @var array
	 */
	private static $constants = [];

	/**
	 * @var array
	 */
	private static $jsonErrors = [];

	/**
	 * @see http://php.net/manual/en/function.json-decode.php
	 * @since 2.5
	 *
	 * @param integer $errorCode
	 *
	 * @return string
	 */
	public static function getStringFromJsonErrorCode( $errorCode ) {

		if ( self::$constants === [] ) {
			self::$constants = get_defined_constants( true );
		}

		if ( isset( self::$constants["json"] ) && self::$jsonErrors === [] ) {
			foreach ( self::$constants["json"] as $name => $value ) {
				if ( !strncmp( $name, "JSON_ERROR_", 11 ) ) {
					self::$jsonErrors[$value] = $name;
				}
			}
		}

		return isset( self::$jsonErrors[$errorCode] ) ? self::$jsonErrors[$errorCode] : 'UNKNOWN';
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $errorCode
	 *
	 * @return string
	 */
	public static function getMessageFromJsonErrorCode( $errorCode ) {

		$errorMessages = [
			JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch, malformed JSON',
			JSON_ERROR_CTRL_CHAR => 'Unexpected control character found, possibly incorrectly encoded',
			JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
			JSON_ERROR_UTF8   => 'Malformed UTF-8 characters, possibly incorrectly encoded',
			JSON_ERROR_DEPTH  => 'The maximum stack depth has been exceeded'
		];

		if ( !isset( $errorMessages[$errorCode] ) ) {
			return self::getStringFromJsonErrorCode( $errorCode );
		}

		return sprintf(
			"Expected a JSON compatible format but failed with '%s'",
			$errorMessages[$errorCode]
		);
	}

}
