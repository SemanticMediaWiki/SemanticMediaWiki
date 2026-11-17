<?php

namespace SMW\Parser;

/**
 * Recognises wikitext that an interface-message parse, such as `smw-parse`,
 * renders as the text itself with `&` escaped. It errs on the side of rejecting
 * text the parser would leave unchanged. A content parse may still convert the
 * text to a language variant.
 *
 * @license GPL-2.0-or-later
 * @since 7.4.2
 */
class PlainWikitext {

	/**
	 * Letters, digits, spaces and prose punctuation, after a first character
	 * that cannot start line-level markup such as a list, heading or
	 * preformatted text
	 */
	private const PLAIN_CHARACTERS = '/^[\p{L}\p{N}][\p{L}\p{M}\p{N} .,()\/\'"‘’“”?!:;%&°…–—-]*\z/u';

	/**
	 * Formatting quotes, character references, the non-breaking space the
	 * parser inserts before French punctuation, URL schemes (`tel:`, `https:`),
	 * magic links, and the combining overlay U+0338 the parser writes as a
	 * character reference
	 */
	private const MARKUP = '/\'\'|&[^ ;]+;| [?!:;%]|:[^ ]|\b(?:ISBN|RFC|PMID)\b|\x{338}/u';

	public static function isPlain( string $text ): bool {
		return preg_match( self::PLAIN_CHARACTERS, $text ) === 1 && preg_match( self::MARKUP, $text ) === 0;
	}

}
