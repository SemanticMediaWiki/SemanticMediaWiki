<?php

namespace SMW\Query\ResultPrinters;

/**
 * Escapes a user-supplied result separator for the output mode it is emitted in.
 *
 * Only wiki output (inline #ask) is sanitised downstream by the parser, so there
 * the raw separator is returned unchanged and legitimate wikitext separators keep
 * working. Every other output mode is emitted straight into the response and
 * bypasses the parser's tag sanitisation: HTML output (Special:Ask), RAW output
 * (Special:Ask with request_type=raw, used by remote requests) and FILE output all
 * land in an HTML context where a user-supplied separator would allow HTML/script
 * injection. In those modes the separator is escaped, allowlisting only the <br>
 * line-break variants that are the intended separator markup.
 *
 * @license GPL-2.0-or-later
 * @since 7.3.1
 */
class SeparatorEscaper {

	/**
	 * @since 7.3.1
	 */
	public static function escape( string $separator, int $outputMode ): string {
		if ( $outputMode === SMW_OUTPUT_WIKI ) {
			return $separator;
		}

		if ( preg_match( '#^\s*<br\s*/?>\s*$#i', $separator ) ) {
			return $separator;
		}

		return htmlspecialchars( $separator, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}

}
