<?php

/**
 * Initialization code of SMW.
 *
 * This is NOT an entry point, and should NEVER be included from outside of SMW.
 * Include SemanticMediaWiki.php in order to load SMW.
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

// The SMW version number.
define( 'SMW_VERSION', '1.9 alpha-3' );

if ( version_compare( $GLOBALS['wgVersion'], '1.19c', '<' ) ) {
	die( '<b>Error:</b> This version of Semantic MediaWiki requires MediaWiki 1.19 or above; use SMW 1.8.x for MediaWiki 1.18.x or 1.17.x.' );
}

if ( !defined( 'Validator_VERSION' ) && is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once( __DIR__ . '/vendor/autoload.php' );
}

// Include the Validator extension if that hasn't been done yet, since it's required for SMW to work.
if ( !defined( 'Validator_VERSION' ) ) {
	@include_once( __DIR__ . '/../Validator/Validator.php' );
}

// Only initialize the extension when all dependencies are present.
if ( !defined( 'Validator_VERSION' ) ) {
	throw new Exception( 'You need to have https://www.mediawiki.org/wiki/Extension:ParamProcessor installed in order to use SMW' );
}

// Version check for Validator, which needs to be at 1.0 or greater.
if ( version_compare( Validator_VERSION, '1.0c', '<' ) ) {
	throw new Exception(
		'This version of SMW needs https://www.mediawiki.org/wiki/Extension:ParamProcessor 1.0 or later.
		You are currently using version ' . Validator_VERSION . '.
		If for any reason you are stuck at Validator 0.5.x, you can use SMW 1.8.x<br />'
	);
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

/**
 * Register all SMW classes
 *
 * @since  1.9
 */
spl_autoload_register( function ( $className ) {
	// @codeCoverageIgnoreStart
	static $classes = false;

	if ( $classes === false ) {
		$classes = include( __DIR__ . '/' . 'SemanticMediaWiki.classes.php' );
	}

	if ( array_key_exists( $className, $classes ) ) {
		include_once __DIR__ . '/' . $classes[$className];
	}

	// MW's total disregard for a different autoloading method forces
	// AutoLoader::autoload to check only the $wgAutoloadLocalClasses classes
	// which means that any other class registered in a different way is not
	// being recognized and therefore causes wfDebug "... not found; skipped loading"
	// messages in the error log
	foreach ( $classes as $class => $file ) {
		if ( !array_key_exists( $class, $GLOBALS['wgAutoloadLocalClasses'] ) ) {
			$GLOBALS['wgAutoloadClasses'][$class] = __DIR__ . '/' . $file;
		}
	}

	// @codeCoverageIgnoreEnd
} );

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

// A flag used to indicate SMW defines a semantic extension type for extension credits.
// @deprecated, removal in SMW 1.11
define( 'SEMANTIC_EXTENSION_TYPE', true );

// Load global constants
require_once( __DIR__ . '/includes/Defines.php' );

// Load global functions
require_once( __DIR__ . '/includes/GlobalFunctions.php' );

// Causes trouble in autoloader during testing because the test returns with
// Class 'PSExtensionHandler' not found
$GLOBALS['wgAutoloadClasses']['SMWPageSchemas'] = __DIR__ . '/' . 'includes/SMW_PageSchemas.php';

// Load default settings
require_once __DIR__ . '/SemanticMediaWiki.settings.php';

// Resource definitions
$GLOBALS['wgResourceModules'] = array_merge( $GLOBALS['wgResourceModules'], include( __DIR__ . "/resources/Resources.php" ) );

// Because of MW 1.19 we need to register message files here
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWiki'] = $GLOBALS['smwgIP'] . 'languages/SMW_Messages.php';
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $GLOBALS['smwgIP'] . 'languages/SMW_Aliases.php';
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $GLOBALS['smwgIP'] . 'languages/SMW_Magic.php';

$GLOBALS['smwgNamespace'] = parse_url( $GLOBALS['wgServer'], PHP_URL_HOST );

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
	$setup = new \SMW\Setup( $GLOBALS );
	$setup->run();
};
