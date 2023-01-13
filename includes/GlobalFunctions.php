<?php

use SMW\DataValues\Number\IntlNumberFormatter;
use SMW\Highlighter;
use SMW\NamespaceManager;
use SMW\ProcessingErrorMsgHandler;
use SMW\Localizer\LocalLanguage\LocalLanguage;
use SMW\ConfigPreloader;

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
function smwfContLang() : LocalLanguage {
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
function smwfNormalTitleDBKey( $text ) {
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
function smwfNormalTitleText( $text ) {
	return Localizer::getInstance()->normalizeTitleText( $text );
}

/**
 * Escapes text in a way that allows it to be used as XML content (e.g. as a
 * string value for some property).
 *
 * @param string $text
 */
function smwfXMLContentEncode( $text ) {
	return str_replace( [ '&', '<', '>' ], [ '&amp;', '&lt;', '&gt;' ], Sanitizer::decodeCharReferences( $text ) );
}

/**
 * Decodes character references and inserts Unicode characters instead, using
 * the MediaWiki Sanitizer.
 *
 * @param string $text
 */
function smwfHTMLtoUTF8( $text ) {
	return Sanitizer::decodeCharReferences( $text );
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
function smwfAbort( $text ) {

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
 * @param string $icon Acts like an enum. Callers must ensure safety, since this value is used directly in the output.
 * @param string $separator
 * @param boolean $escape Should the messages be escaped or not (ie when they already are)
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
 * @return SMWStore
 */
function &smwfGetStore() {
	$store = \SMW\StoreFactory::getStore();
	return $store;
}

/**
 * @since 3.0
 *
 * @param string $namespace
 * @param string $key
 *
 * @return string
 */
function smwfCacheKey( $namespace, $key ) {
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

/**
 * Function to switch on Semantic MediaWiki. This function must be called in
 * LocalSettings.php after including SMW_Settings.php. It is used to ensure
 * that required parameters for SMW are really provided explicitly. For
 * readability, this is the only global function that does not adhere to the
 * naming conventions.
 *
 * This function also sets up all autoloading, such that all SMW classes are
 * available as early on. Moreover, jobs and special pages are registered.
 *
 * @param mixed $namespace
 * @param boolean $complete
 *
 * @return ConfigPreloader
 *
 * @codeCoverageIgnore
 */
function enableSemantics( $namespace = null, $complete = false ) {
	global $smwgNamespace;

	SemanticMediaWiki::setupDefines();
	SemanticMediaWiki::setupGlobals();

	if ( !$complete && ( $smwgNamespace !== '' ) ) {
		// The dot tells that the domain is not complete. It will be completed
		// in the Export since we do not want to create a title object here when
		// it is not needed in many cases.
		$smwgNamespace = '.' . $namespace;
	} else {
		$smwgNamespace = $namespace;
	}

	return new ConfigPreloader();
}
