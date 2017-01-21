<?php

namespace SMW\Parser;

use SMW\InTextAnnotationParser;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class Obfuscator {

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 * @param InTextAnnotationParser $parser
	 *
	 * @return text
	 */
	public static function obfuscateLinks( $text, InTextAnnotationParser $parser ) {

		// Use &#x005B; instead of &#91; to distinguish it from the MW's Sanitizer
		// who uses the same decode sequence and avoid issues when removing links
		// obfuscation

		// Filter simple [ ... ] from [[ ... ]] links
		// Ensure to find the correct start and end in case of
		// [[Foo::[[Bar]]]] or [[Foo::[http://example.org/foo]]]
		$text = str_replace(
			array( '[', ']', '&#x005B;&#x005B;', '&#93;&#93;&#93;&#93;', '&#93;&#93;&#93;', '&#93;&#93;' ),
			array( '&#x005B;', '&#93;', '[[', ']]]]', '&#93;]]', ']]' ),
			$text
		);

		return self::doObfuscate( $text, $parser );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function removeLinkObfuscation( $text ) {
		return str_replace(
			array( '&#x005B;', '&#93;', '&#124;' ),
			array( '[', ']', '|' ),
			$text
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function encodeLinks( $text ) {
		return str_replace(
			array( '[', ']', '|' ),
			array( '&#x005B;', '&#93;', '&#124;' ),
			$text
		);
	}

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
	public static function obfuscateAnnotation( $text ) {
		return preg_replace_callback(
			InTextAnnotationParser::getRegexpPattern( false ),
			function( array $matches ) {
				return str_replace( '[', '&#91;', $matches[0] );
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

	private static function doObfuscate( $text, $parser ) {

		// Find all [[ ... ]]
		preg_match_all('/\[{2}(.*?)\]{2}/is', $text, $matches );
		$off = false;

		foreach ( $matches[0] as $match ) {

			// Ignore transformed links ([[:Foo|Foo]])
			if ( strpos( $match, '[[:' ) !== false ) {
				continue;
			}

			// Remember whether the text contains OFF/ON marker (added by
			// recursive parser, template, embedded result printer) and restore
			// the marker after the text has been processed
			if ( $off === false ) {
				$off = $match === InTextAnnotationParser::OFF;
			}

			$openNum = substr_count( $match, '[[' );
			$closeNum = substr_count( $match, ']]' );
			$markerNum = substr_count( $match, '::' );

			if ( $markerNum == 0 ) {
				// Simple link [[ ... ]], no annotation therefore match and
				// obfuscate [[, |, ]] for a matching text elements
				$text = str_replace( $match, self::encodeLinks( $match ), $text );
			} elseif ( $openNum > $closeNum && $markerNum == 1 ) {
				// [[Text::Some [[abc]]
				// Forget about about the first position
				$replace = str_replace( $match, self::encodeLinks( $match ), $match );
				$replace = substr_replace( $replace, '[[', 0, 16 );
				$text = str_replace( $match, $replace, $text );
			} elseif ( $openNum === $closeNum && $markerNum == 1 ) {
				// [[Foo::Bar]] annotation therefore run a pattern match and
				// obfuscate the returning [[, |, ]] result
				$replace = self::encodeLinks( preg_replace_callback(
					$parser->getRegexpPattern( false ),
					array( $parser, 'preprocess' ),
					$match
				) );
				$text = str_replace( $match, $replace, $text );
			} elseif ( $openNum > $closeNum && $markerNum == 2 ) {
				// [[Text::Some [[Foo::Some]]
				// Remove the first [[ and added after results are returned
				$text = str_replace( $match, '[[' . self::doObfuscate( substr( $match, 2 ), $parser ), $text );
			}
		}

		// Restore OFF/ON
		if ( $off === true ) {
			$text = InTextAnnotationParser::OFF . $text . InTextAnnotationParser::ON;
		}

		return $text;
	}

}
