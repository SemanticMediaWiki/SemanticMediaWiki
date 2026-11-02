<?php

namespace SMW\ParserFunctions;

use MediaWiki\Parser\Sanitizer;
use SMW\Formatters\Infolink;
use SMW\Parser\LinksEncoder;

/**
 * Provides the {{#property_search_link}} parser function to link to
 * Special:SearchByProperty for any value, including those a hand-written
 * subpage link cannot carry (#5781).
 *
 * @license GPL-2.0-or-later
 * @since 7.4.0
 */
class PropertySearchLinkParserFunction {

	public function parse( string $property, string $value, string $caption ): string {
		if ( $property === '' ) {
			return '';
		}

		if ( $caption === '' ) {
			$caption = $value === '' ? $property : $value;
		}

		// Page name magic words such as {{PAGENAME}} return character references
		$wikiText = Infolink::newPropertySearchLink(
			LinksEncoder::neutralizeAnnotation( $caption ),
			Sanitizer::decodeCharReferences( $property ),
			Sanitizer::decodeCharReferences( $value ),
			false
		)->getWikiText();

		return $this->escapeDoubleColonInLinkTarget( $wikiText );
	}

	/**
	 * InTextAnnotationParser would read a `::` in the target of a link as an annotation.
	 */
	private function escapeDoubleColonInLinkTarget( string $wikiText ): string {
		return preg_replace_callback(
			'/^\[\[[^|]*/',
			static fn ( array $target ): string => str_replace( '::', '-3A-3A', $target[0] ),
			$wikiText
		);
	}

}
