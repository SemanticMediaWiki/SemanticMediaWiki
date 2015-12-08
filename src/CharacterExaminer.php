<?php

namespace Onoi\Tesa;

/**
 * @since 0.1
 *
 * @{
 */
// @codeCoverageIgnoreStart
define( 'ONOI_TESA_CHAR_EXAMINER_HIRAGANA_KATAKANA', 'ONOI_TESA_CHAR_EXAMINER_HIRAGANA_KATAKANA' );
define( 'ONOI_TESA_CHAR_EXAMINER_HANGUL', 'ONOI_TESA_CHAR_EXAMINER_HANGUL' );
define( 'ONOI_TESA_CHAR_EXAMINER_CJK_UNIFIED', 'ONOI_TESA_CHAR_EXAMINER_CJK_UNIFIED' );
// @codeCoverageIgnoreEnd
/**@}
 */

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class CharacterExaminer {

	/**
	 * @see http://jrgraphix.net/research/unicode_blocks.php
	 * @since 0.1
	 *
	 * @param string $type
	 * @param string $text
	 *
	 * @return boolean
	 */
	public static function contains( $type, $text ) {

		if ( $type === ONOI_TESA_CHAR_EXAMINER_HIRAGANA_KATAKANA ) {
			return preg_match('/[\x{3040}-\x{309F}]/u', $text ) > 0 || preg_match('/[\x{30A0}-\x{30FF}]/u', $text ) > 0; // isHiragana || isKatakana
		}

		if ( $type === ONOI_TESA_CHAR_EXAMINER_HANGUL ) {
			return preg_match('/[\x{3130}-\x{318F}]/u', $text ) > 0 || preg_match('/[\x{AC00}-\x{D7AF}]/u', $text ) > 0;
		}

		// @see https://en.wikipedia.org/wiki/CJK_Unified_Ideographs
		// Chinese, Japanese and Korean (CJK) scripts share common characters
		// known as CJK characters

		if ( $type === ONOI_TESA_CHAR_EXAMINER_CJK_UNIFIED ) {
			return preg_match('/[\x{4e00}-\x{9fa5}]/u', $text ) > 0;
		}

		return false;
	}

}
