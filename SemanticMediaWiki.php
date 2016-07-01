<?php

/**
 * This documentation group collects source code files belonging to Semantic
 * MediaWiki.
 *
 * For documenting extensions of SMW, please do not use groups starting with
 * "SMW" but make your own groups instead. Browsing at
 * http://doc.semantic-mediawiki.org/ is assumed to be easier this way.
 *
 * @defgroup SMW Semantic MediaWiki
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( defined( 'SMW_VERSION' ) ) {
	// Do not load SMW more than once
	return 1;
}

if ( version_compare( $GLOBALS['wgVersion'], '1.19c', '<' ) ) {
	die( '<b>Error:</b> This version of Semantic MediaWiki requires MediaWiki 1.19 or above; use SMW 1.8.x for MediaWiki 1.18.x or 1.17.x.' );
}

/**
 * THIS IS A TEMPORARY HACK to get around the #1699 issue in connection with the
 * tarball release that conflicts with the Composer autoloading when invoked
 * via the LocalSettings.
 *
 * By the time `extension.json` is used, the content from load.php is to be moved
 * into this file.
 */
require_once __DIR__ . "/load.php";

/**
 * `extension.json` should only be introduced by the time:
 *
 * - A major SMW release change (e.g. 3.x) occurs
 * - `requires` section in extension.json is supported for extensions
 * - MW 1.27 to be a minimum requirement
 *
 * @note Only remove the SemanticMediaWiki.php from the `files` section in the
 * composer.json, any other `files` entry remains to ensure that initial
 * settings, aliases are loaded before `wfLoadExtension( 'SemanticMediaWiki' );`
 * is invoked.
 *
 * Furthermore, remove the `require_once` from the SemanticMediaWiki::initExtension
 * as those are loaded using Composer.
 *
 * Expected format:
 *
 * {
 *	"name": "Semantic MediaWiki",
 *	"version": "3.0.0-alpha",
 *	"author": [
 *		"..."
 *	],
 *	"url": "https://www.semantic-mediawiki.org",
 *	"descriptionmsg": "smw-desc",
 *	"license-name": "GPL-2.0+",
 *	"type": "semantic",
 *	"requires": {
 *		"MediaWiki": ">= 1.27"
 *	},
 *	"MessagesDirs": {
 *		"SemanticMediaWiki": [
 *			"i18n"
 *		]
 *	},
 *	"AutoloadClasses": {
 *		"SemanticMediaWiki": "SemanticMediaWiki.php"
 *	},
 *	"callback": "SemanticMediaWiki::initExtension",
 *	"ExtensionFunctions": [
 *		"SemanticMediaWiki::onExtensionFunction"
 *	],
 *	"load_composer_autoloader":true,
 *	"manifest_version": 1
 * }
 *
 */
