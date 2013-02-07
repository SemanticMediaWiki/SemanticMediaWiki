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

if ( version_compare( $wgVersion, '1.19c', '<' ) ) {
	die( '<b>Error:</b> This version of Semantic MediaWiki requires MediaWiki 1.19 or above; use SMW 1.8.x for MediaWiki 1.18.x or 1.17.x.' );
}

// Include the Validator extension if that hasn't been done yet, since it's required for SMW to work.
if ( !defined( 'Validator_VERSION' ) ) {
	@include_once( __DIR__ . '/../Validator/Validator.php' );
}

// Only initialize the extension when all dependencies are present.
if ( !defined( 'Validator_VERSION' ) ) {
	die( '<b>Error:</b> You need to have <a href="https://www.mediawiki.org/wiki/Extension:Validator">Validator</a> installed in order to use <a href="https://www.semantic-mediawiki.org">Semantic MediaWiki</a>.<br />' );
}

// Version check for Validator, which needs to be at 1.0 or greater.
if ( version_compare( Validator_VERSION, '1.0c', '<' ) ) {
	die(
		'<b>Error:</b> This version of SMW needs <a href="https://www.mediawiki.org/wiki/Extension:Validator">Validator</a> 1.0 or later.
		You are currently using version ' . Validator_VERSION . '.
		If for any reason you are stuck at Validator 0.5.x, you can use SMW 1.8.x<br />'
	);
}

// The SMW version number.
define( 'SMW_VERSION', '1.9 alpha' );

// Registration of the extension credits, see Special:Version.
$wgExtensionCredits['semantic'][] = array(
	'path' => __FILE__,
	'name' => 'Semantic MediaWiki',
	'version' => SMW_VERSION,
	'author' => array(
		'[http://korrekt.org Markus Krötzsch]',
		'[https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]',
		'James Hong Kong',
		'[https://semantic-mediawiki.org/wiki/Contributors ...]'
		),
	'url' => 'https://semantic-mediawiki.org',
	'descriptionmsg' => 'smw-desc'
);

// A flag used to indicate SMW defines a semantic extension type for extension credits.
// @deprecated, removal in SMW 1.11
define( 'SEMANTIC_EXTENSION_TYPE', true );

// Load global constants
require_once( __DIR__ . '/includes/Defines.php' );

// Load global functions
require_once( __DIR__ . '/includes/GlobalFunctions.php' );

// Load setup and autoloader classes
require_once( __DIR__ . '/includes/Setup.php' );

// Load default settings
require_once __DIR__ . '/SemanticMediaWiki.settings.php';

// Resource definitions
$wgResourceModules = array_merge( $wgResourceModules, include( __DIR__ . "/resources/Resources.php" ) );

$wgParamDefinitions['smwformat'] = array(
	'definition'=> 'SMWParamFormat',
);

$wgParamDefinitions['smwsource'] = array(
	'definition' => 'SMWParamSource',
);

$wgExtensionFunctions[] = 'smwfSetupExtension';
$wgExtensionMessagesFiles['SemanticMediaWiki'] = $smwgIP . 'languages/SMW_Messages.php';
$wgExtensionMessagesFiles['SemanticMediaWikiAlias'] = $smwgIP . 'languages/SMW_Aliases.php';
$wgExtensionMessagesFiles['SemanticMediaWikiMagic'] = $smwgIP . 'languages/SMW_Magic.php';

smwfRegisterHooks();
smwfRegisterClasses();
smwfRegisterSpecialPages();

$wgAPIModules['smwinfo'] = 'ApiSMWInfo';
$wgAPIModules['ask'] = 'ApiAsk';
$wgAPIModules['askargs'] = 'ApiAskArgs';

$wgFooterIcons['poweredby']['semanticmediawiki'] = array(
	'src' => null,
	'url' => 'http://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki',
	'alt' => 'Powered by Semantic MediaWiki',
);

$smwgNamespace = parse_url( $wgServer, PHP_URL_HOST );