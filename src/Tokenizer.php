<?php

namespace Onoi\Tesa;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class Tokenizer {

	/**
	 * Any change to the content of this file should be reflected in a version
	 * change (the version is not necessarily the same as the library).
	 */
	const VERSION = '0.1';

	/**
	 * Supported options
	 */
	const LAZY = 0x2;
	const STRICT = 0x4;

	/**
	 * @since 0.1
	 *
	 * @param string $string
	 * @param integer $flag
	 *
	 * @return array|false
	 */
	public static function tokenize( $string, $flag = self::STRICT ) {

		if ( $flag === ( $flag | self::STRICT ) ) {
			return preg_split('/([\s\-_,:;?!\/\(\)\[\]{}<>\r\n"]|(?<!\d)\.(?!\d))/', $string, null, PREG_SPLIT_NO_EMPTY );
		} elseif ( $flag === ( $flag | self::LAZY ) ) {
			return preg_split('/\s+/', $string );
		}

		return false;
	}

}
