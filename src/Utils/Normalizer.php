<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Normalizer {

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function toLowercase( $text ) {
		return mb_strtolower( $text, mb_detect_encoding( $text ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 * @param integer|null $length
	 *
	 * @return string
	 */
	public static function reduceLengthTo( $text, $length = null ) {

		if ( $length === null || mb_strlen( $text ) <= $length ) {
			return $text;
		}

		$encoding = mb_detect_encoding( $text );
		$lastWholeWordPosition = $length;

		if ( strpos( $text, ' ' ) !== false ) {
			$lastWholeWordPosition = strrpos( mb_substr( $text, 0, $length, $encoding ), ' ' ); // last whole word
		}

		if ( $lastWholeWordPosition > 0 ) {
			$length = $lastWholeWordPosition;
		}

		return mb_substr( $text, 0, $length, $encoding );
	}

}
