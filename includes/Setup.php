<?php

/**
 * Global functions used for setting up the Semantic MediaWiki extension.
 *
 * @file SMW_Setup.php
 * @ingroup SMW
 */

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
 * @deprecated since 1.9, just set $smwgNamespace after the inclusion of SemanticMediaWiki.php
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

	if ( !$complete && ( $smwgNamespace !== '' ) ) {
		// The dot tells that the domain is not complete. It will be completed
		// in the Export since we do not want to create a title object here when
		// it is not needed in many cases.
		$smwgNamespace = '.' . $namespace;
	} else {
		$smwgNamespace = $namespace;
	}

	return true;
}

/**
 * Register all SMW hooks with MediaWiki.
 *
 * @codeCoverageIgnore
 */
function smwfRegisterHooks() {
	global $wgHooks;

	$wgHooks['LoadExtensionSchemaUpdates'][] = 'SMWHooks::onSchemaUpdate';

	$wgHooks['ParserTestTables'][]    = 'SMWHooks::onParserTestTables';
	$wgHooks['AdminLinks'][]          = 'SMWHooks::addToAdminLinks';
	$wgHooks['PageSchemasRegisterHandlers'][] = 'SMWHooks::onPageSchemasRegistration';

	$wgHooks['ParserFirstCallInit'][] = 'SMW\DocumentationParserFunction::staticInit';
	$wgHooks['ParserFirstCallInit'][] = 'SMW\InfoParserFunction::staticInit';

	// Register wikipage that has been manually purged (?action=purge)
	$wgHooks['ArticlePurge'][] = 'SMWHooks::onArticlePurge';

	// Fetch some MediaWiki data for replication in SMW's store
	$wgHooks['ParserAfterTidy'][] = 'SMWHooks::onParserAfterTidy';

	// Update data after template change and at save
	$wgHooks['LinksUpdateConstructed'][] = 'SMWHooks::onLinksUpdateConstructed';

	// Delete annotations
	$wgHooks['ArticleDelete'][] = 'SMWHooks::onArticleDelete';

	// Move annotations
	$wgHooks['TitleMoveComplete'][] = 'SMWHooks::onTitleMoveComplete';

	// Additional special properties (modification date etc.)
	$wgHooks['NewRevisionFromEditComplete'][] = 'SMWHooks::onNewRevisionFromEditComplete';

	// Parsing [[link::syntax]] and resolves property annotations
	$wgHooks['InternalParseBeforeLinks'][] = 'SMWHooks::onInternalParseBeforeLinks';

	$wgHooks['ArticleFromTitle'][] = 'SMWHooks::onArticleFromTitle'; // special implementations for property/type articles
	$wgHooks['ParserFirstCallInit'][] = 'SMWHooks::onParserFirstCallInit';

	// Alter the structured navigation links in SkinTemplates
	$wgHooks['SkinTemplateNavigation'][] = 'SMWHooks::onSkinTemplateNavigation';

	//UnitTests
	$wgHooks['UnitTestsList'][] = 'SMWHooks::registerUnitTests';
	$wgHooks['ResourceLoaderTestModules'][] = 'SMWHooks::registerQUnitTests';

	// Statistics
	$wgHooks['SpecialStatsAddExtra'][] = 'SMWHooks::onSpecialStatsAddExtra';

	// User preference
	$wgHooks['GetPreferences'][] = 'SMWHooks::onGetPreferences';

	// Add changes to the output page
	$wgHooks['BeforePageDisplay'][] = 'SMWHooks::onBeforePageDisplay';
	$wgHooks['TitleIsAlwaysKnown'][] = 'SMWHooks::onTitleIsAlwaysKnown';
	$wgHooks['BeforeDisplayNoArticleText'][] = 'SMWHooks::onBeforeDisplayNoArticleText';

	// ResourceLoader
	$wgHooks['ResourceLoaderGetConfigVars'][] = 'SMWHooks::onResourceLoaderGetConfigVars';

	if ( $GLOBALS['smwgToolboxBrowseLink'] ) {
		$wgHooks['SkinTemplateToolboxEnd'][] = 'SMWHooks::showBrowseLink';
	}

	// Draw Factbox below categories
	$wgHooks['SkinAfterContent'][] = 'SMWHooks::onSkinAfterContent';

	// Copy some data for later Factbox display
	$wgHooks['OutputPageParserOutput'][] = 'SMWHooks::onOutputPageParserOutput';

	$wgHooks['ExtensionTypes'][] = 'SMWHooks::addSemanticExtensionType';
}

/**
 * Register all SMW special pages with MediaWiki.
 *
 * @codeCoverageIgnore
 */
function smwfRegisterSpecialPages() {
	$specials = array(
		'Ask' => array(
			'page' => 'SMWAskPage',
			'group' => 'smw_group'
		),
		'Browse' => array(
			'page' =>  'SMWSpecialBrowse',
			'group' => 'smw_group'
		),
		'PageProperty' => array(
			'page' =>  'SMWPageProperty',
			'group' => 'smw_group'
		),
		'SearchByProperty' => array(
			'page' => 'SMWSearchByProperty',
			'group' => 'smw_group'
		),
		'SMWAdmin' => array(
			'page' => 'SMWAdmin',
			'group' => 'smw_group'
		),
		'SemanticStatistics' => array(
			'page' => 'SMW\SpecialSemanticStatistics',
			'group' => 'wiki'
		),
		'Concepts' => array(
			'page' => 'SMW\SpecialConcepts',
			'group' => 'pages'
		),
		'ExportRDF' => array(
			'page' => 'SMWSpecialOWLExport',
			'group' => 'smw_group'
		),
		'Types' => array(
			'page' => 'SMWSpecialTypes',
			'group' => 'pages'
		),
		'URIResolver' => array(
			'page' => 'SMWURIResolver'
		),
		'Properties' => array(
			'page' => 'SMW\SpecialProperties',
			'group' => 'pages'
		),
		'UnusedProperties' => array(
			'page' => 'SMW\SpecialUnusedProperties',
			'group' => 'maintenance'
		),
		'WantedProperties' => array(
			'page' => 'SMW\SpecialWantedProperties',
			'group' => 'maintenance'
		),
	);

	// Register data
	foreach ( $specials as $special => $page ) {
		$GLOBALS['wgSpecialPages'][$special] = $page['page'];

		if ( isset( $page['group'] ) ) {
			$GLOBALS['wgSpecialPageGroups'][$special] = $page['group'];
		}
	}
}

/**
 * Do the actual intialisation of the extension. This is just a delayed init
 * that makes sure MediaWiki is set up properly before we add our stuff.
 *
 * The main things this function does are: register all hooks, set up extension
 * credits, and init some globals that are not for configuration settings.
 *
 * @codeCoverageIgnore
 */
function smwfSetupExtension() {
	wfProfileIn( 'smwfSetupExtension (SMW)' );
	global $smwgScriptPath, $smwgMasterStore, $smwgIQRunningNumber;

	$smwgMasterStore = null;
	$smwgIQRunningNumber = 0;

	wfProfileOut( 'smwfSetupExtension (SMW)' );
	return true;
}

/**********************************************/
/***** namespace settings                 *****/
/**********************************************/

/**
 * Init the additional namespaces used by Semantic MediaWiki.
 *
 * @codeCoverageIgnore
 */
function smwfInitNamespaces() {
	global $smwgNamespaceIndex, $wgExtraNamespaces, $wgNamespaceAliases, $wgNamespacesWithSubpages, $wgLanguageCode, $smwgContLang;

	if ( !isset( $smwgNamespaceIndex ) ) {
		$smwgNamespaceIndex = 100;
	}
	// 100 and 101 used to be occupied by SMW's now obsolete namespaces "Relation" and "Relation_Talk"
	define( 'SMW_NS_PROPERTY',       $smwgNamespaceIndex + 2 );
	define( 'SMW_NS_PROPERTY_TALK',  $smwgNamespaceIndex + 3 );
	define( 'SMW_NS_TYPE',           $smwgNamespaceIndex + 4 );
	define( 'SMW_NS_TYPE_TALK',      $smwgNamespaceIndex + 5 );
	// 106 and 107 are occupied by the Semantic Forms, we define them here to offer some (easy but useful) support to SF
	define( 'SF_NS_FORM',            $smwgNamespaceIndex + 6 );
	define( 'SF_NS_FORM_TALK',       $smwgNamespaceIndex + 7 );
	define( 'SMW_NS_CONCEPT',        $smwgNamespaceIndex + 8 );
	define( 'SMW_NS_CONCEPT_TALK',   $smwgNamespaceIndex + 9 );

	smwfInitContentLanguage( $wgLanguageCode );

	// Register namespace identifiers
	if ( !is_array( $wgExtraNamespaces ) ) {
		$wgExtraNamespaces = array();
	}
	$wgExtraNamespaces = $wgExtraNamespaces + $smwgContLang->getNamespaces();
	$wgNamespaceAliases = $wgNamespaceAliases + $smwgContLang->getNamespaceAliases();

	// Support subpages only for talk pages by default
	$wgNamespacesWithSubpages = $wgNamespacesWithSubpages + array(
				SMW_NS_PROPERTY_TALK => true,
				SMW_NS_TYPE_TALK => true
	);

	// not modified for Semantic MediaWiki
	/* $wgNamespacesToBeSearchedDefault = array(
		NS_MAIN           => true,
		);
	*/
}

/**********************************************/
/***** language settings                  *****/
/**********************************************/

/**
 * Initialise a global language object for content language. This must happen
 * early on, even before user language is known, to determine labels for
 * additional namespaces. In contrast, messages can be initialised much later
 * when they are actually needed.
 *
 * @codeCoverageIgnore
 */
function smwfInitContentLanguage( $langcode ) {
	global $smwgIP, $smwgContLang;

	if ( !empty( $smwgContLang ) ) {
		return;
	}
	wfProfileIn( 'smwfInitContentLanguage (SMW)' );

	$smwContLangFile = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
	$smwContLangClass = 'SMWLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );

	if ( file_exists( $smwgIP . 'languages/' . $smwContLangFile . '.php' ) ) {
		include_once( $smwgIP . 'languages/' . $smwContLangFile . '.php' );
	}

	// Fallback if language not supported.
	if ( !class_exists( $smwContLangClass ) ) {
		include_once( $smwgIP . 'languages/SMW_LanguageEn.php' );
		$smwContLangClass = 'SMWLanguageEn';
	}

	$smwgContLang = new $smwContLangClass();

	wfProfileOut( 'smwfInitContentLanguage (SMW)' );
}
