<?php

use MediaWiki\Linker\Linker;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\WikiMap\WikiMap;
use SMW\DataValues\Number\IntlNumberFormatter;
use SMW\Formatters\Highlighter;
use SMW\Localizer\Localizer;
use SMW\Localizer\LocalLanguage\LocalLanguage;
use SMW\ProcessingErrorMsgHandler;
use SMW\Store;
use SMW\StoreFactory;

/**
 * Global functions specified and used by Semantic MediaWiki. In general, it is
 * tried to fit functions in suitable classes as static methods if they clearly
 * belong to some particular sub-function of SMW. Most functions here are used
 * in diverse contexts so that they do not have fonud a place in any such class
 * yet.
 *
 * @ingroup SMW
 */

/**
 * Convenience function for external users. Replaces the `smwgContLang` setting.
 *
 * @since 3.2
 *
 * @return LocalLanguage
 */
function smwfContLang(): LocalLanguage {
	return LocalLanguage::getInstance()->fetch( $GLOBALS['wgLanguageCode'] );
}

/**
 * Takes a title text and turns it safely into its DBKey. This function
 * reimplements most of the title normalization as done in Title.php in order
 * to achieve conversion with less overhead. The official code could be called
 * here if more advanced normalization is needed.
 *
 * @param string $text
 */
function smwfNormalTitleDBKey( $text ): string {
	global $wgCapitalLinks;

	$text = trim( $text );

	if ( $wgCapitalLinks ) {
		$text = ucfirst( $text );
	}

	return str_replace( ' ', '_', $text );
}

/**
 * @deprecated since 3.2, use `Localizer::normalizeTitleText`
 */
function smwfNormalTitleText( string $text ): string {
	return Localizer::getInstance()->normalizeTitleText( $text );
}

/**
 * Escapes text in a way that allows it to be used as XML content (e.g. as a
 * string value for some property).
 *
 * @param string|null $text
 */
function smwfXMLContentEncode( ?string $text ): string {
	return str_replace( [ '&', '<', '>' ], [ '&amp;', '&lt;', '&gt;' ], Sanitizer::decodeCharReferences( $text ?? '' ) );
}

/**
 * Decodes character references and inserts Unicode characters instead, using
 * the MediaWiki Sanitizer.
 *
 * @param string|null $text
 */
function smwfHTMLtoUTF8( ?string $text ): string {
	return Sanitizer::decodeCharReferences( $text ?? '' );
}

/**
 * @deprecated since 2.1, use NumberFormatter instead
 */
function smwfNumberFormat( $value, $decplaces = 3 ) {
	return IntlNumberFormatter::getInstance()->getLocalizedFormattedNumber( $value, $decplaces );
}

/**
 * @since 3.0
 *
 * @param string $text
 */
function smwfAbort( $text ): void {
	if ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) {
		$text = strip_tags( $text );
	}

	die( $text );
}

/**
 * Formats an array of message strings so that it appears as a tooltip.
 * $icon should be one of: 'warning' (default), 'info'.
 *
 * @param array $messages
 * @param string $type Acts like an enum. Callers must ensure safety, since this value is used directly in the output.
 * @param string $separator
 * @param bool $escape Should the messages be escaped or not (ie when they already are)
 *
 * @return string
 */
function smwfEncodeMessages( array $messages, $type = 'warning', $separator = ' <!--br-->', $escape = true ) {
	$messages = ProcessingErrorMsgHandler::normalizeAndDecodeMessages( $messages );

	if ( $messages === [] ) {
		return '';
	}

	if ( $escape ) {
		$messages = array_map( 'htmlspecialchars', $messages );
	}

	if ( count( $messages ) == 1 ) {
		$content = $messages[0];
	} else {
		foreach ( $messages as &$message ) {
			$message = '<li>' . $message . '</li>';
		}

		$content = '<ul>' . implode( $separator, $messages ) . '</ul>';
	}

	// Stop when a previous processing produced an error and it is expected to be
	// added to a new tooltip (e.g {{#info {{#show ...}} }} ) instance
	if ( Highlighter::hasHighlighterClass( $content, 'warning' ) ) {
		return $content;
	}

	$highlighter = Highlighter::factory( $type );

	$highlighter->setContent( [
		'caption' => null,
		'content' => Highlighter::decode( $content )
	] );

	return $highlighter->getHtml();
}

/**
 * Returns an instance for the storage back-end
 *
 * @return Store
 */
function &smwfGetStore() {
	$store = StoreFactory::getStore();
	return $store;
}

/**
 * @since 3.0
 *
 * @param string $namespace
 * @param string|array $key
 *
 * @return string
 */
function smwfCacheKey( $namespace, $key ): string {
	$cachePrefix = $GLOBALS['wgCachePrefix'] === false ?
		WikiMap::getCurrentWikiId() : $GLOBALS['wgCachePrefix'];

	if ( $namespace[0] !== ':' ) {
		$namespace = ':' . $namespace;
	}

	if ( is_array( $key ) ) {
		$key = json_encode( $key );
	}

	return $cachePrefix . $namespace . ':' . md5( $key );
}

/**
 * Compatibility helper for using Linker methods.
 * MW 1.16 has a Linker with non-static methods,
 * where in MW 1.19 they are static, and a DummyLinker
 * class is introduced, which can be instantiated for
 * compat reasons. As of MW 1.28, DummyLinker is being
 * deprecated, so always use Linker.
 *
 * @since 1.6
 *
 * @return Linker
 */
function smwfGetLinker() {
	static $linker = false;

	if ( $linker === false ) {
		$linker = new Linker();
	}

	return $linker;
}
