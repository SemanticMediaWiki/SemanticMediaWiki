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
		// after obfuscation

		// Filter simple [ ... ] from [[ ... ]] links and ensure to find the correct
		// start and end in case of [[Foo::[[Bar]]]] or [[Foo::[http://example.org/foo]]]
		$text = str_replace(
			array( '[', ']', '&#x005B;&#x005B;', '&#93;&#93;&#93;&#93;', '&#93;&#93;&#93;', '&#93;&#93;' ),
			array( '&#x005B;', '&#93;', '[[', ']]]]', '&#93;]]', ']]' ),
			$text
		);

		// Deep nesting is NOT supported as in [[Foo::[[abc]] [[Bar::123[[abc]] ]] ]]
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
			LinksProcessor::getRegexpPattern( false ),
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

		if ( strpos( $text, '::' ) === false && strpos( $text, ':=' ) === false ) {
			return $text;
		}

		return preg_replace_callback(
			LinksProcessor::getRegexpPattern( false ),
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

		// #1855
		if ( $value === '@@@' ) {
			$value = '';
		}

		return $caption !== false ? $caption : $value;
	}

	private static function doObfuscate( $text, $parser ) {

		/**
		 * @see http://blog.angeloff.name/post/2012/08/05/php-recursive-patterns/
		 *
		 * \[{2}         # find the first opening '[['.
		 *   (?:         # start a new group, this is so '|' below does not apply/affect the opening '['.
		 *     [^\[\]]+  # skip ahead happily if no '[' or ']'.
		 *     |         #   ...otherwise...
		 *     (?R)      # we may be at the start of a new group, repeat whole pattern.
		 *     )
		 *   *           # nesting can be many levels deep.
		 * \]{2}         # finally, expect a balanced closing ']]'
		 */
		preg_match_all("/\[{2}(?:[^\[\]]+|(?R))*\]{2}/is", $text, $matches );
		$isOffAnnotation = false;

		// At this point we distinguish between a normal [[Foo::bar]] annotation
		// and a compound construct such as [[Foo::[[Foobar::Bar]] ]] and
		// [[Foo::[http://example.org/foo foo] [[Foo::123|Bar]] ]].
		//
		// Only the compound is being processed and matched as we require to
		// identify the boundaries of the enclosing annotation
		foreach ( $matches[0] as $match ) {

			// Normal link
			if ( strpos( $match, '[[:' ) !== false ) {
				continue;
			}

			// Remember whether the text contains OFF/ON marker (added by
			// recursive parser, template, embedded result printer)
			if ( $isOffAnnotation === false ) {
				$isOffAnnotation = $match === InTextAnnotationParser::OFF;
			}

			$annotationOpenNum = substr_count( $match, '[[' );

			// Only engage if the match contains more than one [[ :: ]] pair
			if ( $annotationOpenNum > 1 ) {
				$replace = self::doMatchAndReplace( $match, $parser, $isOffAnnotation );
				$text = str_replace( $match, $replace, $text );
			}
		}

		return $text;
	}

	private static function doMatchAndReplace( $match, $parser, $isOffAnnotation = false ) {

		// Remove the Leading and last square bracket to avoid distortion
		// during the annotation parsing
		$match = substr( substr( $match, 2 ), 0, -2 );

		// Restore OFF/ON for the recursive processing
		if ( $isOffAnnotation === true ) {
			$match = InTextAnnotationParser::OFF . $match . InTextAnnotationParser::ON;
		}

		// Only match annotations of style [[...::...]] during a recursive
		// obfuscation process, any other processing is being done by the
		// InTextAnnotation parser hereafter
		//
		// [[Foo::Bar]] annotation therefore run a pattern match and
		// obfuscate the returning [[, |, ]] result
		$replace = self::encodeLinks( preg_replace_callback(
			LinksProcessor::getRegexpPattern( false ),
			array( $parser, 'preprocess' ),
			$match
		) );

		// Restore the square brackets
		return '[[' . $replace . ']]';
	}

}
