<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class UrlEncoder {

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

		// Sanitize remaining string content
		$string = trim( htmlspecialchars( $string, ENT_NOQUOTES ) );
		$string = str_replace( '&nbsp;', ' ', str_replace( array( '&#160;', '&amp;' ), array( ' ', '&' ), $string ) );

		return $string;
	}

}
