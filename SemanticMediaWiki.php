<?php

use SMW\NamespaceManager;
use SMW\ApplicationFactory;
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

define( 'SMW_VERSION', '2.3.1' );

if ( version_compare( $GLOBALS['wgVersion'], '1.19c', '<' ) ) {
	die( '<b>Error:</b> This version of Semantic MediaWiki requires MediaWiki 1.19 or above; use SMW 1.8.x for MediaWiki 1.18.x or 1.17.x.' );
}

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	include_once __DIR__ . '/vendor/autoload.php';
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
	'descriptionmsg' => 'smw-desc',
	'license-name'   => 'GPL-2.0+'
);

// Compatibility aliases for classes that got moved into the SMW namespace in 1.9.
class_alias( 'SMW\Store', 'SMWStore' );
class_alias( 'SMW\MediaWiki\Jobs\UpdateJob', 'SMWUpdateJob' );
class_alias( 'SMW\MediaWiki\Jobs\RefreshJob', 'SMWRefreshJob' );
class_alias( 'SMW\SemanticData', 'SMWSemanticData' );
class_alias( 'SMW\DIWikiPage', 'SMWDIWikiPage' );
class_alias( 'SMW\DIProperty', 'SMWDIProperty' );
class_alias( 'SMW\Serializers\QueryResultSerializer', 'SMWDISerializer' );
class_alias( 'SMW\DataValueFactory', 'SMWDataValueFactory' );
class_alias( 'SMW\DataItemException', 'SMWDataItemException' );
class_alias( 'SMW\SQLStore\TableDefinition', 'SMWSQLStore3Table' );
class_alias( 'SMW\DIConcept', 'SMWDIConcept' );
class_alias( 'SMW\TableResultPrinter', 'SMWTableResultPrinter' );

// 2.0
class_alias( 'SMW\FileExportPrinter', 'SMWExportPrinter' );
class_alias( 'SMW\ResultPrinter', 'SMWResultPrinter' );
class_alias( 'SMW\AggregatablePrinter', 'SMWAggregatablePrinter' );
class_alias( 'SMW\CategoryResultPrinter', 'SMWCategoryResultPrinter' );
class_alias( 'SMW\DsvResultPrinter', 'SMWDSVResultPrinter' );
class_alias( 'SMW\EmbeddedResultPrinter', 'SMWEmbeddedResultPrinter' );
class_alias( 'SMW\RdfResultPrinter', 'SMWRDFResultPrinter' );
class_alias( 'SMW\ListResultPrinter', 'SMWListResultPrinter' );
class_alias( 'SMW\QueryResultPrinter', 'SMWIResultPrinter' );
class_alias( 'SMW\RawResultPrinter', 'SMW\ApiResultPrinter' );

// 2.0
class_alias( 'SMW\SPARQLStore\SPARQLStore', 'SMWSparqlStore' );
class_alias( 'SMW\SPARQLStore\RepositoryConnector\FourstoreHttpRepositoryConnector', 'SMWSparqlDatabase4Store' );
class_alias( 'SMW\SPARQLStore\RepositoryConnector\VirtuosoHttpRepositoryConnector', 'SMWSparqlDatabaseVirtuoso' );
class_alias( 'SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector', 'SMWSparqlDatabase' );

// 2.1
class_alias( 'SMWSQLStore3', 'SMW\SQLStore\SQLStore' );
class_alias( 'SMW\Query\Language\Description', 'SMWDescription' );
class_alias( 'SMW\Query\Language\ThingDescription', 'SMWThingDescription' );
class_alias( 'SMW\Query\Language\ClassDescription', 'SMWClassDescription' );
class_alias( 'SMW\Query\Language\ConceptDescription', 'SMWConceptDescription' );
class_alias( 'SMW\Query\Language\NamespaceDescription', 'SMWNamespaceDescription' );
class_alias( 'SMW\Query\Language\ValueDescription', 'SMWValueDescription' );
class_alias( 'SMW\Query\Language\Conjunction', 'SMWConjunction' );
class_alias( 'SMW\Query\Language\Disjunction', 'SMWDisjunction' );
class_alias( 'SMW\Query\Language\SomeProperty', 'SMWSomeProperty' );
class_alias( 'SMW\Query\PrintRequest', 'SMWPrintRequest' );
class_alias( 'SMW\MediaWiki\Search\Search', 'SMWSearch' );

// 2.2
// Some weird SF dependency needs to be removed as quick as possible
class_alias( 'SMW\SQLStore\Lookup\ListLookup', 'SMW\SQLStore\PropertiesCollector' );
class_alias( 'SMW\SQLStore\Lookup\ListLookup', 'SMW\SQLStore\UnusedPropertiesCollector' );

class_alias( 'SMW\Exporter\Element\ExpElement', 'SMWExpElement' );
class_alias( 'SMW\Exporter\Element\ExpResource', 'SMWExpResource' );
class_alias( 'SMW\Exporter\Element\ExpNsResource', 'SMWExpNsResource' );
class_alias( 'SMW\Exporter\Element\ExpLiteral', 'SMWExpLiteral' );
class_alias( 'SMW\DataValues\ImportValue', 'SMWImportValue' );
class_alias( 'SMW\SQLStore\QueryEngine\QueryEngine', 'SMWSQLStore3QueryEngine' );

// 2.3
class_alias( 'SMW\ParserParameterProcessor', 'SMW\ParserParameterFormatter' );
class_alias( 'SMW\ParameterProcessorFactory', 'SMW\ParameterFormatterFactory' );

// A flag used to indicate SMW defines a semantic extension type for extension credits.
// @deprecated, removal in SMW 3.0
define( 'SEMANTIC_EXTENSION_TYPE', true );

// Load global constants
require_once __DIR__ . '/includes/Defines.php';

// Temporary measure to ease Composer/MW 1.22 migration
require_once __DIR__ . '/includes/NamespaceManager.php';

// Load global functions
require_once __DIR__ . '/includes/GlobalFunctions.php';

// Load default settings
require_once __DIR__ . '/SemanticMediaWiki.settings.php';

// Because of MW 1.19 we need to register message files here
$GLOBALS['wgMessagesDirs']['SemanticMediaWiki'] = $GLOBALS['smwgIP'] . 'i18n';
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWiki'] = $GLOBALS['smwgIP'] . 'languages/SMW_Messages.php';
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiAlias'] = $GLOBALS['smwgIP'] . 'languages/SMW_Aliases.php';
$GLOBALS['wgExtensionMessagesFiles']['SemanticMediaWikiMagic'] = $GLOBALS['smwgIP'] . 'languages/SMW_Magic.php';

/**
 * Setup and initialization
 *
 * @note $wgExtensionFunctions variable is an array that stores
 * functions to be called after most of MediaWiki initialization
 * has finalized
 *
 * @see https://www.mediawiki.org/wiki/Manual:$wgExtensionFunctions
 *
 * @since  1.9
 */
$GLOBALS['wgExtensionFunctions'][] = function() {

	$applicationFactory = ApplicationFactory::getInstance();

	$namespace = new NamespaceManager( $GLOBALS, __DIR__ );
	$namespace->run();

	$setup = new Setup( $applicationFactory, $GLOBALS, __DIR__ );
	$setup->run();
};
