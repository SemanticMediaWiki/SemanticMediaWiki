<?php

namespace SMW\Utils;

/**
 * Serialize/encode a data element with a hmac hash to verify that the output are
 * in fact the same as the input data, minimizing an attack vector on injecting
 * malicious content when retrieving the data from en external systems (like
 * a cache).
 *
 * The shared secret key to generate the HMAC is by default MediaWiki's
 * $wgSecretKey.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HmacSerializer {

	/**
	 * @since 3.0
	 *
	 * @param mixed $data
	 * @param string $key
	 * @param string $algo = 'md5'
	 *
	 * @return string|boolean
	 */
	public static function encode( $data, $key = null, $algo = 'md5' ) {

		if ( $key === null ) {
			$key = $GLOBALS['wgSecretKey'];
		}

		$data = json_encode( $data );
		$hash = hash_hmac( $algo, $data, $key );

		if ( $hash !== false ) {
			return json_encode( [ 'hmac' => $hash, 'data' => $data ] );
		}

		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @param mixed $data
	 * @param string $key
	 * @param string $algo = 'md5'
	 *
	 * @return string|boolean
	 */
	public static function decode( $data, $key = null, $algo = 'md5' ) {

		if ( $key === null ) {
			$key = $GLOBALS['wgSecretKey'];
		}

		if ( !is_string( $data ) ) {
			return false;
		}

		$hash = '';
		$data = json_decode( $data, true );

		// Timing attack safe string comparison
		if ( isset( $data['hmac'] ) && hash_equals( hash_hmac( $algo, $data['data'], $key ), $data['hmac'] ) ) {
			return json_decode( $data['data'], true );
		}

		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @param mixed $data
	 * @param string $key
	 * @param string $algo = 'md5'
	 *
	 * @return string|boolean
	 */
	public static function serialize( $data, $key = null, $algo = 'md5' ) {

		if ( $key === null ) {
			$key = $GLOBALS['wgSecretKey'];
		}

		$data = serialize( $data );
		$hash = hash_hmac( $algo, $data, $key );

		if ( $hash !== false ) {
			return "$hash|$data";
		}

		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $data
	 * @param string $key
	 * @param string $algo = 'md5'
	 *
	 * @return mixed|boolean
	 */
	public static function unserialize( $data, $key = null, $algo = 'md5' ) {

		if ( $key === null ) {
			$key = $GLOBALS['wgSecretKey'];
		}

		if ( !is_string( $data ) ) {
			return false;
		}

		$hash = '';

		if ( strpos( $data, '|' ) !== false ) {
			list( $hash, $data ) = explode( '|', $data, 2 );
		}

		// Timing attack safe string comparison
		if ( hash_equals( hash_hmac( $algo, $data, $key ), $hash ) ) {
			return unserialize( $data );
		}

		return false;
	}

	/**
	 * @since 3.0
	 *
	 * @param mixed $data
	 * @param string $key
	 * @param string $algo = 'md5'
	 *
	 * @return string|boolean
	 */
	public static function compress( $data, $key = null, $algo = 'md5' ) {

		if ( $key === null ) {
			$key = $GLOBALS['wgSecretKey'];
		}

		$key = $key . 'compress';

		return gzcompress( self::serialize( $data, $key, $algo ), 9 );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $data
	 * @param string $key
	 * @param string $algo = 'md5'
	 *
	 * @return mixed|boolean
	 */
	public static function uncompress( $data, $key = null, $algo = 'md5' ) {

		if ( $key === null ) {
			$key = $GLOBALS['wgSecretKey'];
		}

		$key = $key . 'compress';

		return self::unserialize( @gzuncompress( $data ), $key, $algo );
	}

}
