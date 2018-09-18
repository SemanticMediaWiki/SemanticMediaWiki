<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CharArmor {

	/**
	 * Remove invisible control characters and unused code points (using a
	 * negated character class to avoid removing spaces)
	 *
	 * @see http://www.regular-expressions.info/unicode.html#category
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function removeControlChars( $text ) {
		return preg_replace('/[^\PC\s]/u', '', $text );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $text
	 *
	 * @return text
	 */
	public static function removeSpecialChars( $text ) {
		return str_replace(
			[ '&shy;', '&lrm;', " ", " ", " " ],
			[ '', '', ' ', ' ', ' ' ],
			$text
		);
	}

}
