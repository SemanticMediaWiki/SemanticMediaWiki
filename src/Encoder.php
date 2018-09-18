<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class Encoder {

	/**
	 * @see SMWInfolink::encodeParameters
	 *
	 * Escape certain problematic values. Use SMW-escape
	 * (like URLencode but - instead of % to prevent double encoding by later MW actions)
	 * : SMW's parameter separator, must not occur within params
	 * // - : used in SMW-encoding strings, needs escaping too
	 * [ ] < > &lt; &gt; '' |: problematic in MW titles
	 * & : sometimes problematic in MW titles ([[&amp;]] is OK, [[&test]] is OK, [[&test;]] is not OK)
	 *     (Note: '&' in strings obtained during parsing already has &entities; replaced by
	 *     UTF8 anyway)
	 * ' ': are equivalent with '_' in MW titles, but are not equivalent in certain parameter values
	 * "\n": real breaks not possible in [[...]]
	 * "#": has special meaning in URLs, triggers additional MW escapes (using . for %)
	 * '%': must be escaped to prevent any impact of double decoding when replacing -
	 *      by % before urldecode
	 * '?': if not escaped, strange effects were observed on some sites (printout and other
	 *      parameters ignored without obvious cause); SMW-escaping is always save to do -- it just
	 *      make URLs less readable
	 *
	 * @since 2.2
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function escape( $string ) {

		$value = str_replace(
			[ '-', '#', "\n", ' ', '/', '[', ']', '<', '>', '&lt;', '&gt;', '&amp;', '\'\'', '|', '&', '%', '?', '$', "\\", ";", '_' ],
			[ '-2D', '-23', '-0A', '-20', '-2F', '-5B', '-5D', '-3C', '-3E', '-3C', '-3E', '-26', '-27-27', '-7C', '-26', '-25', '-3F', '-24', '-5C', "-3B", '-5F' ],
			$string
		);

		return $value;
	}

	/**
	 * Reverse of self::escape
	 *
	 * @since 2.5
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function unescape( $string ) {

		$value = str_replace(
			[ '-20', '-23', '-0A', '-2F', '-5B', '-5D', '-3C', '-3E', '-3C', '-3E', '-26', '-27-27', '-7C', '-26', '-25', '-3F', '-24', '-5C', "-3B", "-3A", '-5F', '-2D' ],
			[ ' ',    '#',   "\n", '/',   '[',    ']',   '<',   '>',  '&lt;', '&gt;', '&', '\'\'',    '|',   '&',  '%',   '?',   '$',    "\\", ";",   ":",   "_",   '-' ],
			$string
		);

		return $value;
	}

	/**
	 * @see SMWInfolink::encodeParameters
	 *
	 * @since 2.2
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function encode( $string ) {
		return rawurlencode( $string );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function decode( $string ) {

		// Apply decoding for SMW's own url encoding strategy (see SMWInfolink)
		$string = str_replace( '%', '-', rawurldecode( str_replace( '-', '%', $string ) ) );

		$string = str_replace( [ '-2D', '-3A' ], [ '-', ':' ], $string );

		// Sanitize remaining string content
		$string = trim( htmlspecialchars( $string, ENT_NOQUOTES ) );
		$string = str_replace( '&nbsp;', ' ', str_replace( [ '&#160;', '&amp;' ], [ ' ', '&' ], $string ) );

		return $string;
	}

}
