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

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

global $smwgIP;

if ( version_compare( $wgVersion, '1.17c', '<' ) ) {
	die( '<b>Error:</b> This version of Semantic MediaWiki requires MediaWiki 1.17 or above; use SMW 1.7.x for MediaWiki 1.16.x.' );
}

// Include the Validator extension if that hasn't been done yet, since it's required for SMW to work.
if ( !defined( 'Validator_VERSION' ) ) {
	@include_once( dirname( __FILE__ ) . '/../Validator/Validator.php' );
}

// Only initialize the extension when all dependencies are present.
if ( !defined( 'Validator_VERSION' ) ) {
	die( '<b>Error:</b> You need to have <a href="https://www.mediawiki.org/wiki/Extension:Validator">Validator</a> installed in order to use <a href="https://www.semantic-mediawiki.org">Semantic MediaWiki</a>.<br />' );
}

// Version check for Validator, which needs to be at 0.5 or greater.
if ( version_compare( Validator_VERSION, '0.5c', '<' ) ) {
	die(
		'<b>Error:</b> This version of SMW needs <a href="https://www.mediawiki.org/wiki/Extension:Validator">Validator</a> 0.5 or later.
		You are currently using version ' . Validator_VERSION . '.
		If for any reason you are stuck at Validator 0.4.x, you can use SMW 1.7.x and 1.6.x.<br />'
	);
}

// The SMW version number.
define( 'SMW_VERSION', '1.8' );

// Registration of the extension credits, see Special:Version.
$wgExtensionCredits['semantic'][] = array(
	'path' => __FILE__,
	'name' => 'Semantic MediaWiki',
	'version' => SMW_VERSION,
	'author' => array(
		'[http://korrekt.org Markus Krötzsch]',
		'[http://simia.net Denny Vrandecic]',
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
		'...'
		),
	'url' => 'https://semantic-mediawiki.org',
	'descriptionmsg' => 'smw-desc'
);

// A flag used to indicate SMW defines a semantic extension type for extension credits.
// @deprecated
define( 'SEMANTIC_EXTENSION_TYPE', true );

// A flag used to indicate SMW supports Validator style parameter definitions and validation in the SMWResultPrinter class.
// @deprecated, removal in 1.9
define( 'SMW_SUPPORTS_VALIDATOR', true );

// Default settings
require_once dirname( __FILE__ ) . '/SMW_Settings.php';

// Resource definitions
require_once dirname( __FILE__ ) . '/SMW_Resources.php';
