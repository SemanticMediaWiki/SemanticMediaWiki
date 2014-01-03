<?php

use SMW\SimpleDependencyBuilder;
use SMW\SharedDependencyContainer;
use SMW\ExtensionContext;
use SMW\NamespaceManager;
use SMW\Setup;

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

if ( defined( 'SMW_VERSION' ) ) {
	// Do not load SMW more than once
	return 1;
}

define( 'SMW_VERSION', '1.9' );

if ( version_compare( $GLOBALS['wgVersion'], '1.19c', '<' ) ) {
	die( '<b>Error:</b> This version of Semantic MediaWiki requires MediaWiki 1.19 or above; use SMW 1.8.x for MediaWiki 1.18.x or 1.17.x.' );
}

if ( !defined( 'Validator_VERSION' ) && is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once( __DIR__ . '/vendor/autoload.php' );
}

// Only initialize the extension when all dependencies are present.
if ( !defined( 'Validator_VERSION' ) ) {
	throw new Exception( 'You need to have https://www.mediawiki.org/wiki/Extension:ParamProcessor installed in order to use SMW' );
}

// Registration of the extension credits, see Special:Version.
$GLOBALS['wgExtensionCredits']['semantic'][] = array(
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

// Compatibility aliases for classes that got moved into the SMW namespace in 1.9.
class_alias( 'SMW\Store', 'SMWStore' );
class_alias( 'SMW\UpdateJob', 'SMWUpdateJob' );
class_alias( 'SMW\RefreshJob', 'SMWRefreshJob' );
class_alias( 'SMW\SemanticData', 'SMWSemanticData' );
class_alias( 'SMW\DIWikiPage', 'SMWDIWikiPage' );
class_alias( 'SMW\DIProperty', 'SMWDIProperty' );
class_alias( 'SMW\Serializers\QueryResultSerializer', 'SMWDISerializer' );
class_alias( 'SMW\DataValueFactory', 'SMWDataValueFactory' );
class_alias( 'SMW\DataItemException', 'SMWDataItemException' );
class_alias( 'SMW\FileExportPrinter', 'SMWExportPrinter' );
class_alias( 'SMW\ResultPrinter', 'SMWResultPrinter' );
class_alias( 'SMW\SQLStore\TableDefinition', 'SMWSQLStore3Table' );
class_alias( 'SMW\AggregatablePrinter', 'SMWAggregatablePrinter' );
class_alias( 'SMW\ListResultPrinter', 'SMWListResultPrinter' );

// A flag used to indicate SMW defines a semantic extension type for extension credits.
// @deprecated, removal in SMW 1.11
define( 'SEMANTIC_EXTENSION_TYPE', true );

// Load global constants
require_once( __DIR__ . '/includes/Defines.php' );

// Temporary measure to ease Composer/MW 1.22 migration
require_once __DIR__ . '/includes/NamespaceManager.php';

// Load global functions
require_once( __DIR__ . '/includes/GlobalFunctions.php' );

// Load default settings
require_once __DIR__ . '/SemanticMediaWiki.settings.php';

// Because of MW 1.19 we need to register message files here
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWiki'] = $GLOBALS['smwgIP'] . 'languages/SMW_Messages.php';
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $GLOBALS['smwgIP'] . 'languages/SMW_Aliases.php';
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $GLOBALS['smwgIP'] . 'languages/SMW_Magic.php';
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiNamespaces'] = $GLOBALS['smwgIP'] . 'languages/SemanticMediaWiki.namespaces.php';

/**
 * Setup and initialization
 *
 * @note $wgExtensionFunctions variable is an array that stores
 * functions to be called after most of MediaWiki initialization
 * is complete
 *
 * @see https://www.mediawiki.org/wiki/Manual:$wgExtensionFunctions
 *
 * @since  1.9
 */
$GLOBALS['wgExtensionFunctions'][] = function() {

	$builder = new SimpleDependencyBuilder( new SharedDependencyContainer() );
	$context = new ExtensionContext( $builder );

	$namespace = new NamespaceManager( $GLOBALS, __DIR__ );
	$namespace->run();

	$setup = new Setup( $GLOBALS, __DIR__, $context );
	$setup->run();

};
