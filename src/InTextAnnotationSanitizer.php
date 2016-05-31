<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class InTextAnnotationSanitizer {

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function decodeSquareBracket( $text ) {
		return str_replace( array( '%5B', '%5D' ), array( '[', ']' ), $text );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function obscureAnnotation( $text ) {
		return preg_replace_callback(
			InTextAnnotationParser::getRegexpPattern( false ),
			function( array $matches ) {
				return str_replace( '[', '&#x005B;', $matches[0] );
			},
			self::decodeSquareBracket( $text )
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function removeAnnotation( $text ) {
		return preg_replace_callback(
			InTextAnnotationParser::getRegexpPattern( false ),
			'self::doRemoveAnnotation',
			self::decodeSquareBracket( $text )
		);
	}

	private static function doRemoveAnnotation( array $matches ) {

		$caption = false;
		$value = '';

		// #1453
		if ( $matches[0] === InTextAnnotationParser::OFF || $matches[0] === InTextAnnotationParser::ON ) {
			return false;
		}

		// Strict mode matching
		if ( array_key_exists( 1, $matches ) ) {
			if ( strpos( $matches[1], ':' ) !== false && isset( $matches[2] ) ) {
				list( $matches[1], $matches[2] ) = explode( '::', $matches[1] . '::' . $matches[2], 2 );
			}
		}

		if ( array_key_exists( 2, $matches ) ) {

			// #1747
			if ( strpos( $matches[1], '|' ) !== false ) {
				return $matches[0];
			}

			$parts = explode( '|', $matches[2] );
			$value = array_key_exists( 0, $parts ) ? $parts[0] : '';
			$caption = array_key_exists( 1, $parts ) ? $parts[1] : false;
		}

		// #...
		if ( $value === '@@@' ) {
			$value = '';
		}

		return $caption !== false ? $caption : $value;
	}

}
