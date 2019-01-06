<?php

namespace SMW\Utils;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class Tokenizer {

	/**
	 * @since 2.5
	 *
	 * @param string $text
	 *
	 * @return array
	 */
	public static function tokenize( $text ) {

		if ( !class_exists( '\IntlRuleBasedBreakIterator' ) ) {
			return explode( ' ', $text );
		}

		// As for CJK, this returns better results as trying to split tokens
		// by a single character
		$intlRuleBasedBreakIterator = \IntlRuleBasedBreakIterator::createWordInstance( 'en' );
		$intlRuleBasedBreakIterator->setText( $text );

		$prev = 0;
		$tokens = [];

		foreach ( $intlRuleBasedBreakIterator as $token ) {

			if ( $token == 0 ) {
				continue;
			}

			$res = substr( $text, $prev, $token - $prev );

			if ( $res !== '' && $res !== ' ' ) {
				$tokens[] = $res;
			}

			$prev = $token;
		}

		return $tokens;
	}

}
