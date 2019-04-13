<?php

namespace SMW;

use Closure;
use Language;

/**
 * @private
 *
 * Object agnostic handler class that encapsulates a foreign Message object
 * (e.g MW's Message class). It is expected that a registered handler returns a
 * simple string representation for the parameters, type, and language given.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class Message {

	/**
	 * @var array
	 */
	private static $messageCache = null;

	/**
	 * PoolCache ID
	 */
	const POOLCACHE_ID = 'message.cache';

	/**
	 * MW processing mode
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
	private static $messageHandler = [];

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
	 */
	public static function clear() {
		self::$messageCache = null;
	}

	/**
	 * @since 2.4
	 *
	 * @return FixedInMemoryLruCache
	 */
	public static function getCache() {

		if ( self::$messageCache === null ) {
			self::$messageCache = InMemoryPoolCache::getInstance()->getPoolCacheById( self::POOLCACHE_ID, 1000 );
		}

		return self::$messageCache;
	}

	/**
	 * Encodes a message into a JSON representation that can transferred,
	 * transformed, and stored while allowing to add an infinite amount of
	 * arguments.
	 *
	 * '[2,"Foo", "Bar"]' => Preferred output type, Message ID, Argument $1 ... $
	 *
	 * @since 2.5
	 *
	 * @param string|array $parameters
	 * @param integer|null $type
	 *
	 * @return string
	 */
	public static function encode( $message, $type = null ) {

		if ( is_string( $message ) && json_decode( $message ) && json_last_error() === JSON_ERROR_NONE ) {
			return $message;
		}

		if ( $type === null ) {
			$type = self::TEXT;
		}

		if ( $message === [] ) {
			return '';
		}

		$message = (array)$message;
		$encode = [];
		$encode[] = $type;

		foreach ( $message as $value ) {
			// Check if the value is already encoded, and if decode to keep the
			// structure intact
			if ( substr( $value, 0, 1 ) === '[' && ( $dc = json_decode( $value, true ) ) && json_last_error() === JSON_ERROR_NONE ) {
				$encode += $dc;
			} else {
				// Normalize arguments like "<strong>Expression error:
				// Unrecognized word "yyyy".</strong>"
				$value = strip_tags( htmlspecialchars_decode( $value, ENT_QUOTES ) );

				// - Internally encoded to circumvent the strip_tags which would
				//   remove <, > from values that represent a range
				// - Encode `::` to prevent the annotation parser to pick the
				//   message value
				$value = str_replace( [ '%3C', '%3E', "::" ], [ '>', '<', "&#58;&#58;" ], $value );

				$encode[] = $value;
			}
		}

		return json_encode( $encode );
	}

	/**
	 * @FIXME Needs to be MW agnostic !
	 *
	 * @since 2.5
	 *
	 * @param string $messageId
	 *
	 * @return boolean
	 */
	public static function exists( $message ) {
		return wfMessage( $message )->exists();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $json
	 * @param integer|null $type
	 * @param integer|null $language
	 *
	 * @return string|boolean
	 */
	public static function decode( $message, $type = null, $language = null ) {

		$message = json_decode( $message );
		$asType = null;

		if ( json_last_error() !== JSON_ERROR_NONE || $message === '' || $message === null ) {
			return false;
		}

		// If the first element is numeric then its signals the expected message
		// formatter type
		if ( isset( $message[0] ) && is_numeric( $message[0] ) ) {
			$asType = array_shift( $message );
		}

		// Is it a msgKey or a simple text?
		if ( isset( $message[0] ) && !self::exists( $message[0] ) ) {
			return $message[0];
		}

		return self::get( $message, ( $type === null ? $asType : $type ), $language );
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
			$type = self::TEXT;
		}

		if ( $language === null || !$language ) {
			$language = self::CONTENT_LANGUAGE;
		}

		$hash = self::getHash( $parameters, $type, $language );

		if ( $content = self::getCache()->fetch( $hash ) ) {
			return $content;
		}

		if ( isset( self::$messageHandler[$type] ) && is_callable( self::$messageHandler[$type] ) ) {
			$handler = self::$messageHandler[$type];
		}

		if ( $handler === null ) {
			return '';
		}

		$message = call_user_func_array(
			$handler,
			[ $parameters, $language ]
		);

		self::getCache()->save( $hash, $message );

		return $message;
	}

	/**
	 * @since 2.4
	 *
	 * @param array $parameters
	 * @param integer $type
	 * @param integer|string|Language $language
	 *
	 * @return string
	 */
	public static function getHash( $parameters, $type = null, $language = null ) {

		if ( $language instanceof Language ) {
			$language = $language->getCode();
		}

		return md5( json_encode( $parameters ) . '#' . $type . '#' . $language );
	}

}
