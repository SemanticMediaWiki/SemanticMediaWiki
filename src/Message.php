<?php

namespace SMW;

use Closure;

/**
 * @private
 *
 * Object agnostic handler class that encapsulates a foreign Message object
 * (e.g MW's Message class). It is expected that a registered handler returns a
 * simple string respresentation for the parameters, type, and language given.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class Message {

	/**
	 * Processing mode
	 */
	const TEXT = 0x2;
	const ESCAPED = 0x4;
	const PARSE = 0x8;

	/**
	 * Predefined language mode
	 */
	const CONTENT_LANGUAGE = 0x32;
	const USER_LANGUAGE = 0x64;

	/**
	 * @var array
	 */
	private static $messageHandler = array();

	/**
	 * @since 2.4
	 *
	 * @param $type
	 * @param Closure $handler
	 */
	public static function registerCallbackHandler( $type, Closure $handler ) {
		self::$messageHandler[$type] = $handler;
	}

	/**
	 * @since 2.4
	 *
	 * @param $type
	 */
	public static function deregisterHandlerFor( $type ) {
		unset( self::$messageHandler[$type] );
	}

	/**
	 * @since 2.4
	 *
	 * @param string|array $parameters
	 * @param integer|null $type
	 * @param integer|null $language
	 *
	 * @return string
	 */
	public static function get( $parameters, $type = null, $language = null ) {

		$handler = null;
		$parameters = (array)$parameters;

		if ( $type === null ) {
			$type = Message::TEXT;
		}

		if ( $language === null ) {
			$language = Message::CONTENT_LANGUAGE;
		}

		if ( isset( self::$messageHandler[$type] ) && is_callable( self::$messageHandler[$type] ) ) {
			$handler = self::$messageHandler[$type];
		}

		if ( $handler === null ) {
			return '';
		}

		return call_user_func_array( $handler, array( $parameters, $language ) );
	}

}
