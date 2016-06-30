<?php

use SMW\CompatibilityMode;
use SMW\NamespaceManager;
use SMW\NumberFormatter;
use SMW\SPARQLStore\SparqlDBConnectionProvider;

/**
 * Global functions specified and used by Semantic MediaWiki. In general, it is
 * tried to fit functions in suitable classes as static methods if they clearly
 * belong to some particular sub-function of SMW. Most functions here are used
 * in diverse contexts so that they do not have fonud a place in any such class
 * yet.
 * @ingroup SMW
 */

/**
 * @see NamespaceExaminer
 *
 * @return boolean
 * @deprecated since 1.9 and will be removed in 1.11
 */
function smwfIsSemanticsProcessed( $namespace ) {
	return \SMW\NamespaceExaminer::getInstance()->isSemanticEnabled( $namespace );
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
 * Takes a text and turns it into a normalised version. This function
 * reimplements the title normalization as done in Title.php in order to
 * achieve conversion with less overhead. The official code could be called
 * here if more advanced normalization is needed.
 *
 * @param string $text
 */
function smwfNormalTitleText( $text ) {
	global $wgCapitalLinks, $wgContLang;

	$text = trim( $text );

	if ( $wgCapitalLinks ) {
		$text = $wgContLang->ucfirst( $text );
	}

	return str_replace( '_', ' ', $text );
}

/**
 * Escapes text in a way that allows it to be used as XML content (e.g. as a
 * string value for some property).
 *
 * @param string $text
 */
function smwfXMLContentEncode( $text ) {
	return str_replace( array( '&', '<', '>' ), array( '&amp;', '&lt;', '&gt;' ), Sanitizer::decodeCharReferences( $text ) );
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
	return NumberFormatter::getInstance()->getLocalizedFormattedNumber( $value, $decplaces );
}

/**
 * Formats an array of message strings so that it appears as a tooltip.
 * $icon should be one of: 'warning' (default), 'info'.
 *
 * @param array $messages
 * @param string $icon Acts like an enum. Callers must ensure safety, since this value is used directly in the output.
 * @param string $seperator
 * @param boolean $escape Should the messages be escaped or not (ie when they already are)
 *
 * @return string
 */
function smwfEncodeMessages( array $messages, $type = 'warning', $seperator = ' <!--br-->', $escape = true ) {
	if (  $messages !== array() ) {

		if ( $escape ) {
			$messages = array_map( 'htmlspecialchars', $messages );
		}

		if ( count( $messages ) == 1 )  {
			$errorList = $messages[0];
		}
		else {
			foreach ( $messages as &$message ) {
				$message = '<li>' . $message . '</li>';
			}

			$errorList = '<ul>' . implode( $seperator, $messages ) . '</ul>';
		}

		// Type will be converted internally
		$highlighter = SMW\Highlighter::factory( $type );
		$highlighter->setContent( array (
			'caption'   => null,
			'content'   => $errorList
		) );

		return $highlighter->getHtml();
	} else {
		return '';
	}
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
 * @codeCoverageIgnore
 *
 * Get the SMWSparqlDatabase object to use for connecting to a SPARQL store,
 * or null if no SPARQL backend has been set up.
 *
 * Currently, it just returns one globally defined object, but the
 * infrastructure allows to set up load balancing and task-dependent use of
 * stores (e.g. using other stores for fast querying than for storing new
 * facts), somewhat similar to MediaWiki's DB implementation.
 *
 * @since 1.6
 *
 * @return SMWSparqlDatabase or null
 */
function &smwfGetSparqlDatabase() {

	if ( !isset( $GLOBALS['smwgSparqlDatabaseMaster'] ) ) {
		$connectionProvider = new SparqlDBConnectionProvider();
		$GLOBALS['smwgSparqlDatabaseMaster'] = $connectionProvider->getConnection();
	}

	return $GLOBALS['smwgSparqlDatabaseMaster'];
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
 * @return true
 *
 * @codeCoverageIgnore
 */
function enableSemantics( $namespace = null, $complete = false ) {
	global $smwgNamespace;

	// $GLOBALS ought to be injected from the top-level but that would require
	// to change the interface which would bring no benefits for the end user
	// as enableSemantics() is only targeted to be included in LocalSettings
	NamespaceManager::initCustomNamespace( $GLOBALS );

	if ( !$complete && ( $smwgNamespace !== '' ) ) {
		// The dot tells that the domain is not complete. It will be completed
		// in the Export since we do not want to create a title object here when
		// it is not needed in many cases.
		$smwgNamespace = '.' . $namespace;
	} else {
		$smwgNamespace = $namespace;
	}

	$GLOBALS['smwgSemanticsEnabled'] = true;

	return true;
}

/**
 * To disable Semantic MediaWiki's operational functionality
 *
 * @note This function can be used to temporary disable SMW but it is paramount
 * that after SMW is re-enabled to run `rebuildData.php` in order for data to
 * represent a state that mirrors the actual environment (deleted, moved pages
 * are not tracked when disabled).
 */
function disableSemantics() {
	CompatibilityMode::disableSemantics();
}
