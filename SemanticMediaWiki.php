<?php

/**
 * Main entry point for the Semantic MediaWiki extension.
 */

/**
 * This documentation group collects source code files belonging to Semantic
 * MediaWiki.
 *
 * For documenting extensions of SMW, please do not use groups starting with
 * "SMW" but make your own groups instead. Browsing at
 * https://semantic-mediawiki.org/doc/  is assumed to be easier this way.
 *
 * @defgroup SMW Semantic MediaWiki
 */

if ( !defined( 'SMW_VERSION' ) ) {
	include_once( __DIR__ . '/SemanticMediaWiki.init.php' );
}