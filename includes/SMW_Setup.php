<?php

/**
 * Global functions used for setting up the Semantic MediaWiki extension.
 * 
 * @file SMW_Setup.php
 * @ingroup SMW
 */

// The SMW version number.
define( 'SMW_VERSION', '1.6.2' );

// A flag used to indicate SMW defines a semantic extension type for extension crdits.
define( 'SEMANTIC_EXTENSION_TYPE', true );

require_once( 'SMW_GlobalFunctions.php' );

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
 */
function enableSemantics( $namespace = null, $complete = false ) {
	global $smwgIP, $wgFooterIcons, $wgExtensionFunctions,
		$wgExtensionMessagesFiles, $wgExtensionAliasesFiles,
		$smwgNamespace, $wgServer, $wgAPIModules;

	$wgExtensionFunctions[] = 'smwfSetupExtension';
	$wgExtensionMessagesFiles['SemanticMediaWiki'] = $smwgIP . 'languages/SMW_Messages.php';
	$wgExtensionAliasesFiles['SemanticMediaWiki'] = $smwgIP . 'languages/SMW_Aliases.php';

	smwfRegisterHooks();
	smwfRegisterResourceLoaderModules();
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

	// Initialize base namespace for URIs in data exports:
	if ( $namespace === null ) { 
		die ( 'You should be providing the domain name to enableSemantics()' );
		// fallback (bad because data URL will change depending on client URL)
//		wfWarn( 'You should be providing the domain name to enableSemantics()' );
//		$namespace = parse_url( $wgServer, PHP_URL_HOST );
	}
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
 */
function smwfRegisterHooks() {
	global $wgHooks, $wgVersion;

	// FIXME: The following can be removed when new style magic words are used (introduced in r52503)
	$wgHooks['LanguageGetMagic'][]    = 'smwfAddMagicWords'; // setup names for parser functions (needed here)
	$wgHooks['ParserTestTables'][]    = 'smwfOnParserTestTables';
	$wgHooks['AdminLinks'][]          = 'smwfAddToAdminLinks';
	$wgHooks['ParserFirstCallInit'][] = 'SMWSMWDoc::staticInit';
	$wgHooks['LanguageGetMagic'][]    = 'SMWSMWDoc::staticMagic';
	
	if ( version_compare( $wgVersion, '1.17alpha', '>=' ) ) {
		// For MediaWiki 1.17 alpha and later.
		$wgHooks['ExtensionTypes'][] = 'smwfAddSemanticExtensionType';
	} else {
		// For pre-MediaWiki 1.17 alpha.
		$wgHooks['SpecialVersionExtensionTypes'][] = 'smwfOldAddSemanticExtensionType';
	}

	$wgHooks['PageSchemasGetObject'][]            = 'SMWPageSchemas::createPageSchemasObject';
	$wgHooks['PageSchemasGeneratePages'][]        = 'SMWPageSchemas::generatePages';
	$wgHooks['PageSchemasGetFieldHTML'][]         = 'SMWPageSchemas::getFieldHTML';
	$wgHooks['PageSchemasGetFieldXML'][]          = 'SMWPageSchemas::getFieldXML';
	$wgHooks['PSParseFieldElements'][]            = 'SMWPageSchemas::parseFieldElements';
	$wgHooks['PageSchemasGetPageList'][]          = 'SMWPageSchemas::getPageList';
}

/**
 * Register all SMW modules with the MediaWiki Resource Loader.
 */
function smwfRegisterResourceLoaderModules() {
	global $wgResourceModules, $smwgIP, $smwgScriptPath, $wgVersion, $wgStylePath, $wgStyleVersion;

	$moduleTemplate = array(
		'localBasePath' => $smwgIP,
		'remoteBasePath' => $smwgScriptPath,
		'group' => 'ext.smw'
	);
	
	$wgResourceModules['ext.smw'] = $moduleTemplate + array(
		'scripts' => array(
			'resources/ext.smw.js',
			'resources/ext.smw.compat.js',
		),
	);
	
	$wgResourceModules['ext.smw.style'] = $moduleTemplate + array(
		'styles' => 'skins/SMW_custom.css'
	);
	
	$wgResourceModules['ext.smw.tooltips'] = $moduleTemplate + array(
		'scripts' => 'skins/SMW_tooltip.js',
		'dependencies' => array(
			'mediawiki.legacy.wikibits',
			'ext.smw.style'
		)
	);

	// Modules for jQuery UI before MW 1.17.0 (b/c code).
	// This can vanish when dropping MW 1.16 support.
	// Should we better do "defined( 'MW_SUPPORTS_RESOURCE_MODULES' )" here?
	if ( version_compare( $wgVersion, '1.17alpha', '<' ) ) {
		// TODO: should we better load our own "$smwgScriptPath/libs/jquery-1.4.2.min.js"?
		// MW 1.16 only has jQuery 1.3.2 with some patches.
		$wgResourceModules['jquery'] = array(
			'scripts' => "common/jquery.min.js?$wgStyleVersion",
			'localBasePath' => null, // irrelevant for pre 1.17 b/c code
			'remoteBasePath' => $wgStylePath,
			'group' => 'ext.smw'
		);
		$wgResourceModules['jquery.ui.core'] = $moduleTemplate + array(
			'scripts' => 'libs/jquery-ui/jquery.ui.core.min.js',
			'styles' => 'skins/jquery-ui/base/jquery.ui.all.css',
			'dependencies' => 'jquery'
		);
		$wgResourceModules['jquery.ui.widget'] = $moduleTemplate + array(
			'scripts' => 'libs/jquery-ui/jquery.ui.widget.min.js'
		);
		$wgResourceModules['jquery.ui.position'] = $moduleTemplate + array(
			'scripts' => 'libs/jquery-ui/jquery.ui.position.min.js'
		);
		$wgResourceModules['jquery.ui.button'] = $moduleTemplate + array(
			'scripts' => 'libs/jquery-ui/jquery.ui.button.min.js',
			'dependencies' => array( 'jquery.ui.core', 'jquery.ui.widget' )
		);
		$wgResourceModules['jquery.ui.autocomplete'] = $moduleTemplate + array(
			'scripts' => 'libs/jquery-ui/jquery.ui.autocomplete.min.js',
			'dependencies' => array( 'jquery.ui.core', 'jquery.ui.widget', 'jquery.ui.position' )
		);
		$wgResourceModules['jquery.ui.dialog'] = $moduleTemplate + array(
			'scripts' => 'libs/jquery-ui/jquery.ui.dialog.min.js',
			'dependencies' => array( 'jquery.ui.core', 'jquery.ui.widget', 'jquery.ui.position', 
				'jquery.ui.button' /*, 'jquery.ui.draggable', 'jquery.ui.mouse', 'jquery.ui.resizable'*/ )
		);
	}
}

/**
 * Register all SMW classes with the MediaWiki autoloader.
 */
function smwfRegisterClasses() {
	global $smwgIP, $wgAutoloadClasses, $wgJobClasses;

	$incDir = $smwgIP . 'includes/';
	$wgAutoloadClasses['SMWCompatibilityHelpers']   = $incDir . 'SMW_CompatibilityHelpers.php';
	$wgAutoloadClasses['SMWDataValueFactory']   	= $incDir . 'SMW_DataValueFactory.php';
	$wgAutoloadClasses['SMWFactbox']                = $incDir . 'SMW_Factbox.php';
	$wgAutoloadClasses['SMWInfolink']               = $incDir . 'SMW_Infolink.php';
	$wgAutoloadClasses['SMWOutputs']                = $incDir . 'SMW_Outputs.php';
	$wgAutoloadClasses['SMWParseData']              = $incDir . 'SMW_ParseData.php';
	$wgAutoloadClasses['SMWParserExtensions']       = $incDir . 'SMW_ParserExtensions.php';
	$wgAutoloadClasses['SMWQueryLanguage']          = $incDir . 'SMW_QueryLanguage.php';
	$wgAutoloadClasses['SMWSemanticData']           = $incDir . 'SMW_SemanticData.php';
	$wgAutoloadClasses['SMWPageLister']             = $incDir . 'SMW_PageLister.php';

	// Article pages
	$apDir = $smwgIP . 'includes/articlepages/';
	$wgAutoloadClasses['SMWOrderedListPage']        = $apDir . 'SMW_OrderedListPage.php';
	$wgAutoloadClasses['SMWPropertyPage']           = $apDir . 'SMW_PropertyPage.php';
	$wgAutoloadClasses['SMWConceptPage']            = $apDir . 'SMW_ConceptPage.php';

	// Printers
	$qpDir = $smwgIP . 'includes/queryprinters/';
	$wgAutoloadClasses['SMWResultPrinter']          = $qpDir . 'SMW_QueryPrinter.php';
	$wgAutoloadClasses['SMWTableResultPrinter']     = $qpDir . 'SMW_QP_Table.php';
	$wgAutoloadClasses['SMWListResultPrinter']      = $qpDir . 'SMW_QP_List.php';
	$wgAutoloadClasses['SMWCategoryResultPrinter']  = $qpDir . 'SMW_QP_Category.php';
	$wgAutoloadClasses['SMWEmbeddedResultPrinter']  = $qpDir . 'SMW_QP_Embedded.php';
	$wgAutoloadClasses['SMWRSSResultPrinter']       = $qpDir . 'SMW_QP_RSSlink.php';
	$wgAutoloadClasses['SMWCsvResultPrinter']       = $qpDir . 'SMW_QP_CSV.php';
	$wgAutoloadClasses['SMWDSVResultPrinter']       = $qpDir . 'SMW_QP_DSV.php';
	$wgAutoloadClasses['SMWJSONResultPrinter']      = $qpDir . 'SMW_QP_JSONlink.php';
	$wgAutoloadClasses['SMWRDFResultPrinter']       = $qpDir . 'SMW_QP_RDF.php';

	// Data items
	$diDir = $smwgIP . 'includes/dataitems/';
	$wgAutoloadClasses['SMWDataItem']		= $diDir . 'SMW_DataItem.php';
	$wgAutoloadClasses['SMWDataItemException']	= $diDir . 'SMW_DataItem.php';
	$wgAutoloadClasses['SMWDIProperty']		= $diDir . 'SMW_DI_Property.php';
	$wgAutoloadClasses['SMWDIBoolean']		= $diDir . 'SMW_DI_Bool.php';
	$wgAutoloadClasses['SMWDINumber']		= $diDir . 'SMW_DI_Number.php';
	$wgAutoloadClasses['SMWDIBlob']			= $diDir . 'SMW_DI_Blob.php';
	$wgAutoloadClasses['SMWDIString']		= $diDir . 'SMW_DI_String.php';
	$wgAutoloadClasses['SMWStringLengthException']	= $diDir . 'SMW_DI_String.php';
	$wgAutoloadClasses['SMWDIUri']			= $diDir . 'SMW_DI_URI.php';
	$wgAutoloadClasses['SMWDIWikiPage']		= $diDir . 'SMW_DI_WikiPage.php';
	$wgAutoloadClasses['SMWDITime']			= $diDir . 'SMW_DI_Time.php';
	$wgAutoloadClasses['SMWDIConcept']		= $diDir . 'SMW_DI_Concept.php';
	$wgAutoloadClasses['SMWDIError']		= $diDir . 'SMW_DI_Error.php';
	$wgAutoloadClasses['SMWDIGeoCoord']		= $diDir . 'SMW_DI_GeoCoord.php';
	$wgAutoloadClasses['SMWContainerSemanticData']	= $diDir . 'SMW_DI_Container.php';
	$wgAutoloadClasses['SMWDIContainer']		= $diDir . 'SMW_DI_Container.php';

	// Datavalues
	$dvDir = $smwgIP . 'includes/datavalues/';
	$wgAutoloadClasses['SMWDataValue']           	= $dvDir . 'SMW_DataValue.php';
	$wgAutoloadClasses['SMWContainerValue']			= $dvDir . 'SMW_DV_Container.php';
	$wgAutoloadClasses['SMWRecordValue']         	= $dvDir . 'SMW_DV_Record.php';
	$wgAutoloadClasses['SMWErrorValue']          	= $dvDir . 'SMW_DV_Error.php';
	$wgAutoloadClasses['SMWStringValue']         	= $dvDir . 'SMW_DV_String.php';
	$wgAutoloadClasses['SMWWikiPageValue']       	= $dvDir . 'SMW_DV_WikiPage.php';
	$wgAutoloadClasses['SMWPropertyValue']       	= $dvDir . 'SMW_DV_Property.php';
	$wgAutoloadClasses['SMWURIValue']            	= $dvDir . 'SMW_DV_URI.php';
	$wgAutoloadClasses['SMWTypesValue']          	= $dvDir . 'SMW_DV_Types.php';
	$wgAutoloadClasses['SMWPropertyListValue']		= $dvDir . 'SMW_DV_PropertyList.php';
	$wgAutoloadClasses['SMWNumberValue']         	= $dvDir . 'SMW_DV_Number.php';
	$wgAutoloadClasses['SMWTemperatureValue']    	= $dvDir . 'SMW_DV_Temperature.php';
	$wgAutoloadClasses['SMWQuantityValue']         	= $dvDir . 'SMW_DV_Quantity.php';
	$wgAutoloadClasses['SMWTimeValue']           	= $dvDir . 'SMW_DV_Time.php';
	$wgAutoloadClasses['SMWBoolValue']           	= $dvDir . 'SMW_DV_Bool.php';
	$wgAutoloadClasses['SMWConceptValue']        	= $dvDir . 'SMW_DV_Concept.php';
	$wgAutoloadClasses['SMWImportValue']         	= $dvDir . 'SMW_DV_Import.php';

	// Export
	$expDir = $smwgIP . 'includes/export/';
	$wgAutoloadClasses['SMWExporter']               = $expDir . 'SMW_Exporter.php';
	$wgAutoloadClasses['SMWExpData']                = $expDir . 'SMW_Exp_Data.php';
	$wgAutoloadClasses['SMWExpElement']             = $expDir . 'SMW_Exp_Element.php';
	$wgAutoloadClasses['SMWExpLiteral']             = $expDir . 'SMW_Exp_Element.php';
	$wgAutoloadClasses['SMWExpResource']            = $expDir . 'SMW_Exp_Element.php';
	$wgAutoloadClasses['SMWExpNsResource']          = $expDir . 'SMW_Exp_Element.php';
	$wgAutoloadClasses['SMWExportController']		= $expDir . 'SMW_ExportController.php';
	$wgAutoloadClasses['SMWSerializer']	        	= $expDir . 'SMW_Serializer.php';
	$wgAutoloadClasses['SMWRDFXMLSerializer']       = $expDir . 'SMW_Serializer_RDFXML.php';
	$wgAutoloadClasses['SMWTurtleSerializer']       = $expDir . 'SMW_Serializer_Turtle.php';

	// Parameter classes
	$parDir = $smwgIP . 'includes/params/';
	$wgAutoloadClasses['SMWParamFormat']			= $parDir . 'SMW_ParamFormat.php';
	
	// Parser hooks
	$phDir = $smwgIP . 'includes/parserhooks/';
	$wgAutoloadClasses['SMWAsk']                    = $phDir . 'SMW_Ask.php';
	$wgAutoloadClasses['SMWShow']                   = $phDir . 'SMW_Show.php';
	$wgAutoloadClasses['SMWInfo']                   = $phDir . 'SMW_Info.php';
	$wgAutoloadClasses['SMWConcept']                = $phDir . 'SMW_Concept.php';
	$wgAutoloadClasses['SMWSet']                    = $phDir . 'SMW_Set.php';
	$wgAutoloadClasses['SMWSetRecurringEvent']      = $phDir . 'SMW_SetRecurringEvent.php';
	$wgAutoloadClasses['SMWDeclare']                = $phDir . 'SMW_Declare.php';
	$wgAutoloadClasses['SMWSMWDoc']                 = $phDir . 'SMW_SMWDoc.php';
	
	// Stores & queries
	$wgAutoloadClasses['SMWQueryProcessor']         = $smwgIP . 'includes/SMW_QueryProcessor.php';
	$wgAutoloadClasses['SMWQueryParser']            = $smwgIP . 'includes/SMW_QueryParser.php';

	$wgAutoloadClasses['SMWSparqlDatabase']         = $smwgIP . 'includes/sparql/SMW_SparqlDatabase.php';
	$wgAutoloadClasses['SMWSparqlDatabase4Store']   = $smwgIP . 'includes/sparql/SMW_SparqlDatabase4Store.php';
	$wgAutoloadClasses['SMWSparqlDatabaseError']    = $smwgIP . 'includes/sparql/SMW_SparqlDatabase.php';
	$wgAutoloadClasses['SMWSparqlResultWrapper']    = $smwgIP . 'includes/sparql/SMW_SparqlResultWrapper.php';
	$wgAutoloadClasses['SMWSparqlResultParser']     = $smwgIP . 'includes/sparql/SMW_SparqlResultParser.php';

	$stoDir = $smwgIP . 'includes/storage/';
	$wgAutoloadClasses['SMWQuery']                  = $stoDir . 'SMW_Query.php';
	$wgAutoloadClasses['SMWQueryResult']            = $stoDir . 'SMW_QueryResult.php';
	$wgAutoloadClasses['SMWStore']                  = $stoDir . 'SMW_Store.php';
	$wgAutoloadClasses['SMWStringCondition']        = $stoDir . 'SMW_Store.php';
	$wgAutoloadClasses['SMWRequestOptions']         = $stoDir . 'SMW_Store.php';
	$wgAutoloadClasses['SMWPrintRequest']           = $stoDir . 'SMW_PrintRequest.php';
	$wgAutoloadClasses['SMWThingDescription']       = $stoDir . 'SMW_Description.php';
	$wgAutoloadClasses['SMWClassDescription']       = $stoDir . 'SMW_Description.php';
	$wgAutoloadClasses['SMWConceptDescription']     = $stoDir . 'SMW_Description.php';
	$wgAutoloadClasses['SMWNamespaceDescription']   = $stoDir . 'SMW_Description.php';
	$wgAutoloadClasses['SMWValueDescription']       = $stoDir . 'SMW_Description.php';
	$wgAutoloadClasses['SMWConjunction']            = $stoDir . 'SMW_Description.php';
	$wgAutoloadClasses['SMWDisjunction']            = $stoDir . 'SMW_Description.php';
	$wgAutoloadClasses['SMWSomeProperty']           = $stoDir . 'SMW_Description.php';
	$wgAutoloadClasses['SMWSQLStore2']              = $stoDir . 'SMW_SQLStore2.php';
	$wgAutoloadClasses['SMWSqlStubSemanticData']    = $stoDir . 'SMW_SqlStubSemanticData.php';
	$wgAutoloadClasses['SMWSqlStore2IdCache']       = $stoDir . 'SMW_SqlStore2IdCache.php';
	$wgAutoloadClasses['SMWSQLStore2Table']         = $stoDir . 'SMW_SQLStore2Table.php';
	$wgAutoloadClasses['SMWSQLHelpers']             = $stoDir . 'SMW_SQLHelpers.php';
	$wgAutoloadClasses['SMWSparqlStore']            = $stoDir . 'SMW_SparqlStore.php';
	$wgAutoloadClasses['SMWSparqlStoreQueryEngine'] = $stoDir . 'SMW_SparqlStoreQueryEngine.php';

	// To ensure SMW remains compatible with pre 1.16.
	if ( !class_exists( 'Html' ) ) {
		$wgAutoloadClasses['Html'] = $smwgIP . 'compat/Html.php';
	}

	// Special pages and closely related helper classes
	$specDir = $smwgIP . 'specials/';
	$wgAutoloadClasses['SMWQueryPage']                 = $specDir . 'QueryPages/SMW_QueryPage.php';
	$wgAutoloadClasses['SMWAskPage']                   = $specDir . 'AskSpecial/SMW_SpecialAsk.php';
	$wgAutoloadClasses['SMWQueryUIHelper']             = $specDir . 'AskSpecial/SMW_QueryUIHelper.php';
	$wgAutoloadClasses['SMWQueryUI']                   = $specDir . 'AskSpecial/SMW_QueryUI.php';
	$wgAutoloadClasses['SMWQueryCreatorPage']          = $specDir . 'AskSpecial/SMW_SpecialQueryCreator.php';
	$wgAutoloadClasses['SMWSpecialBrowse']             = $specDir . 'SearchTriple/SMW_SpecialBrowse.php';
	$wgAutoloadClasses['SMWPageProperty']              = $specDir . 'SearchTriple/SMW_SpecialPageProperty.php';
	$wgAutoloadClasses['SMWSearchByProperty']          = $specDir . 'SearchTriple/SMW_SpecialSearchByProperty.php';
	$wgAutoloadClasses['SMWURIResolver']               = $specDir . 'URIResolver/SMW_SpecialURIResolver.php';
	$wgAutoloadClasses['SMWAdmin']                     = $specDir . 'SMWAdmin/SMW_SpecialSMWAdmin.php';
	$wgAutoloadClasses['SMWSpecialSemanticStatistics'] = $specDir . 'Statistics/SMW_SpecialStatistics.php';
	$wgAutoloadClasses['SMWSpecialOWLExport']          = $specDir . 'Export/SMW_SpecialOWLExport.php';
	$wgAutoloadClasses['SMWSpecialProperties']         = $specDir . 'QueryPages/SMW_SpecialProperties.php';
	$wgAutoloadClasses['SMWSpecialTypes']              = $specDir . 'QueryPages/SMW_SpecialTypes.php';
	$wgAutoloadClasses['SMWSpecialUnusedProperties']   = $specDir . 'QueryPages/SMW_SpecialUnusedProperties.php';
	$wgAutoloadClasses['SMWSpecialWantedProperties']   = $specDir . 'QueryPages/SMW_SpecialWantedProperties.php';

	// Jobs
	$wgJobClasses['SMWUpdateJob']       = 'SMWUpdateJob';
	$wgAutoloadClasses['SMWUpdateJob']  = $smwgIP . 'includes/jobs/SMW_UpdateJob.php';
	$wgJobClasses['SMWRefreshJob']      = 'SMWRefreshJob';
	$wgAutoloadClasses['SMWRefreshJob'] = $smwgIP . 'includes/jobs/SMW_RefreshJob.php';

	// API modules
	$wgAutoloadClasses['ApiSMWQuery'] = $smwgIP . 'includes/api/ApiSMWQuery.php';
	$wgAutoloadClasses['ApiAsk'] = $smwgIP . 'includes/api/ApiAsk.php';
	$wgAutoloadClasses['ApiAskArgs'] = $smwgIP . 'includes/api/ApiAskArgs.php';
	$wgAutoloadClasses['ApiSMWInfo'] = $smwgIP . 'includes/api/ApiSMWInfo.php';

	// Other extensions
	$wgAutoloadClasses['SMWPageSchemas'] = $smwgIP . 'includes/SMW_PageSchemas.php';
}

/**
 * Register all SMW special pages with MediaWiki.
 */
function smwfRegisterSpecialPages() {
	global $wgSpecialPages, $wgSpecialPageGroups;

	$wgSpecialPages['Ask']                          = 'SMWAskPage';
	$wgSpecialPageGroups['Ask']                     = 'smw_group';

	$wgSpecialPages['QueryCreator']                 = 'SMWQueryCreatorPage';
	$wgSpecialPageGroups['QueryCreator']            = 'smw_group';

	$wgSpecialPages['Browse']                       = 'SMWSpecialBrowse';
	$wgSpecialPageGroups['Browse']                  = 'smw_group';

	$wgSpecialPages['PageProperty']                 = 'SMWPageProperty';
	$wgSpecialPageGroups['PageProperty']            = 'smw_group';

	$wgSpecialPages['SearchByProperty']             = 'SMWSearchByProperty';
	$wgSpecialPageGroups['SearchByProperty']        = 'smw_group';

	$wgSpecialPages['URIResolver']                  = 'SMWURIResolver';

	$wgSpecialPages['SMWAdmin']                     = 'SMWAdmin';
	$wgSpecialPageGroups['SMWAdmin']                = 'smw_group';

	$wgSpecialPages['SemanticStatistics']           = 'SMWSpecialSemanticStatistics';
	$wgSpecialPageGroups['SemanticStatistics']      = 'wiki'; // Similar to Special:Statistics

	$wgSpecialPages['ExportRDF']                    = 'SMWSpecialOWLExport';
	$wgSpecialPageGroups['ExportRDF']               = 'smw_group';

	$wgSpecialPages['Properties']                   = 'SMWSpecialProperties';
	$wgSpecialPageGroups['Properties']              = 'pages';

	$wgSpecialPages['Types']                        = 'SMWSpecialTypes';
	$wgSpecialPageGroups['Types']                   = 'pages';

	$wgSpecialPages['UnusedProperties']             = 'SMWSpecialUnusedProperties';
	$wgSpecialPageGroups['UnusedProperties']        = 'maintenance';

	$wgSpecialPages['WantedProperties']             = 'SMWSpecialWantedProperties';
	$wgSpecialPageGroups['WantedProperties']        = 'maintenance';
}

/**
 * Do the actual intialisation of the extension. This is just a delayed init
 * that makes sure MediaWiki is set up properly before we add our stuff.
 *
 * The main things this function does are: register all hooks, set up extension
 * credits, and init some globals that are not for configuration settings.
 */
function smwfSetupExtension() {
	wfProfileIn( 'smwfSetupExtension (SMW)' );
	global $smwgIP, $smwgScriptPath, $wgHooks, $wgFooterIcons, $smwgMasterStore, $smwgIQRunningNumber, $smwgToolboxBrowseLink;

	$smwgMasterStore = null;
	$smwgIQRunningNumber = 0;

	///// register hooks /////
	require_once( $smwgIP . 'includes/SMW_RefreshTab.php' );

	$wgHooks['InternalParseBeforeLinks'][] = 'SMWParserExtensions::onInternalParseBeforeLinks'; // parse annotations in [[link syntax]]
	$wgHooks['ArticleDelete'][] = 'SMWParseData::onArticleDelete'; // delete annotations
	$wgHooks['TitleMoveComplete'][] = 'SMWParseData::onTitleMoveComplete'; // move annotations
	$wgHooks['LinksUpdateConstructed'][] = 'SMWParseData::onLinksUpdateConstructed'; // update data after template change and at save
	$wgHooks['ParserAfterTidy'][] = 'SMWParseData::onParserAfterTidy'; // fetch some MediaWiki data for replication in SMW's store
	$wgHooks['NewRevisionFromEditComplete'][] = 'SMWParseData::onNewRevisionFromEditComplete'; // fetch some MediaWiki data for replication in SMW's store
	$wgHooks['OutputPageParserOutput'][] = 'SMWFactbox::onOutputPageParserOutput'; // copy some data for later Factbox display
	$wgHooks['ArticleFromTitle'][] = 'smwfOnArticleFromTitle'; // special implementations for property/type articles
	$wgHooks['ParserFirstCallInit'][] = 'smwfRegisterParserFunctions';

	if ( $smwgToolboxBrowseLink ) {
		$wgHooks['SkinTemplateToolboxEnd'][] = 'smwfShowBrowseLink';
	}

	$wgHooks['SkinAfterContent'][] = 'SMWFactbox::onSkinAfterContent'; // draw Factbox below categories
	$wgHooks['SkinGetPoweredBy'][] = 'smwfAddPoweredBySMW';
	if ( isset($wgFooterIcons["poweredby"])
	  && isset($wgFooterIcons["poweredby"]["semanticmediawiki"])
	  && $wgFooterIcons["poweredby"]["semanticmediawiki"]["src"] === null ) {
		$wgFooterIcons["poweredby"]["semanticmediawiki"]["src"] = "$smwgScriptPath/skins/images/smw_button.png";
	}

	wfProfileOut( 'smwfSetupExtension (SMW)' );
	return true;
}

/**
 * Adds the 'semantic' extension type to the type list.
 *
 * @since 1.5.2
 *
 * @param $aExtensionTypes Array
 *
 * @return true
 */
function smwfAddSemanticExtensionType( array &$aExtensionTypes ) {
	smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	$aExtensionTypes = array_merge( array( 'semantic' => wfMsg( 'version-semantic' ) ), $aExtensionTypes );
	return true;
}

/**
 * @see smwfAddSemanticExtensionType
 *
 * @since 1.5.2
 *
 * @param $oSpecialVersion SpecialVersion
 * @param $aExtensionTypes Array
 *
 * @return true
 */
function smwfOldAddSemanticExtensionType( SpecialVersion &$oSpecialVersion, array &$aExtensionTypes ) {
	return smwfAddSemanticExtensionType( $aExtensionTypes );
}

/**
 * Adds links to Admin Links page.
 */
function smwfAddToAdminLinks( &$admin_links_tree ) {
	smwfLoadExtensionMessages( 'SemanticMediaWiki' );

	$data_structure_section = new ALSection( wfMsg( 'smw_adminlinks_datastructure' ) );

	$smw_row = new ALRow( 'smw' );
	$smw_row->addItem( ALItem::newFromSpecialPage( 'Categories' ) );
	$smw_row->addItem( ALItem::newFromSpecialPage( 'Properties' ) );
	$smw_row->addItem( ALItem::newFromSpecialPage( 'UnusedProperties' ) );
	$smw_row->addItem( ALItem::newFromSpecialPage( 'SemanticStatistics' ) );

	$data_structure_section->addRow( $smw_row );
	$smw_admin_row = new ALRow( 'smw_admin' );
	$smw_admin_row->addItem( ALItem::newFromSpecialPage( 'SMWAdmin' ) );

	$data_structure_section->addRow( $smw_admin_row );
	$smw_docu_row = new ALRow( 'smw_docu' );
	$smw_name = wfMsg( 'specialpages-group-smw_group' );
	$smw_docu_label = wfMsg( 'adminlinks_documentation', $smw_name );
	$smw_docu_row->addItem( AlItem::newFromExternalLink( 'http://semantic-mediawiki.org/wiki/Help:User_manual', $smw_docu_label ) );

	$data_structure_section->addRow( $smw_docu_row );
	$admin_links_tree->addSection( $data_structure_section, wfMsg( 'adminlinks_browsesearch' ) );
	$smw_row = new ALRow( 'smw' );
	$displaying_data_section = new ALSection( wfMsg( 'smw_adminlinks_displayingdata' ) );
	$smw_row->addItem( AlItem::newFromExternalLink( 'http://semantic-mediawiki.org/wiki/Help:Inline_queries', wfMsg( 'smw_adminlinks_inlinequerieshelp' ) ) );

	$displaying_data_section->addRow( $smw_row );
	$admin_links_tree->addSection( $displaying_data_section, wfMsg( 'adminlinks_browsesearch' ) );
	$browse_search_section = $admin_links_tree->getSection( wfMsg( 'adminlinks_browsesearch' ) );

	$smw_row = new ALRow( 'smw' );
	$smw_row->addItem( ALItem::newFromSpecialPage( 'Browse' ) );
	$smw_row->addItem( ALItem::newFromSpecialPage( 'Ask' ) );
	$smw_row->addItem( ALItem::newFromSpecialPage( 'SearchByProperty' ) );
	$browse_search_section->addRow( $smw_row );

	return true;
}


/**
 * Register special classes for displaying semantic content on Property and
 * Concept pages.
 *
 * @param $title Title
 * @param $article Article or null
 *
 * @return true
 */
function smwfOnArticleFromTitle( Title &$title, /* Article */ &$article ) {
	if ( $title->getNamespace() == SMW_NS_PROPERTY ) {
		$article = new SMWPropertyPage( $title );
	} elseif ( $title->getNamespace() == SMW_NS_CONCEPT ) {
		$article = new SMWConceptPage( $title );
	}

	return true;
}

/**
 * Register tables to be added to temporary tables for parser tests.
 * @todo Hard-coding this thwarts the modularity/exchangability of the SMW
 * storage backend. The actual list of required tables depends on the backend
 * implementation and cannot really be fixed here.
 */
function smwfOnParserTestTables( &$tables ) {
	$tables[] = 'smw_ids';
	$tables[] = 'smw_redi2';
	$tables[] = 'smw_atts2';
	$tables[] = 'smw_rels2';
	$tables[] = 'smw_text2';
	$tables[] = 'smw_spec2';
	$tables[] = 'smw_inst2';
	$tables[] = 'smw_subs2';
	return true;
}

/**
 * Add a link to the toolbox to view the properties of the current page in
 * Special:Browse. The links has the CSS id "t-smwbrowselink" so that it can be
 * skinned or hidden with all standard mechanisms (also by individual users
 * with custom CSS).
 */
function smwfShowBrowseLink( $skintemplate ) {
	if ( $skintemplate->data['isarticle'] ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$browselink = SMWInfolink::newBrowsingLink( wfMsg( 'smw_browselink' ),
						$skintemplate->data['titleprefixeddbkey'], false );
		echo '<li id="t-smwbrowselink">' . $browselink->getHTML() . '</li>';
	}
	return true;
}

/**********************************************/
/***** namespace settings                 *****/
/**********************************************/

/**
 * Init the additional namespaces used by Semantic MediaWiki.
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
 * Set up (possibly localised) names for SMW's parser functions.
 * @todo Can be removed when new style magic words are used (introduced in r52503).
 */
function smwfAddMagicWords( &$magicWords, $langCode ) {
	$magicWords['ask']     = array( 0, 'ask' );
	$magicWords['show']    = array( 0, 'show' );
	$magicWords['info']    = array( 0, 'info' );
	$magicWords['concept'] = array( 0, 'concept' );
	$magicWords['set']     = array( 0, 'set' );
	$magicWords['set_recurring_event']     = array( 0, 'set_recurring_event' );
	$magicWords['declare'] = array( 0, 'declare' );
	$magicWords['SMW_NOFACTBOX'] = array( 0, '__NOFACTBOX__' );
	$magicWords['SMW_SHOWFACTBOX'] = array( 0, '__SHOWFACTBOX__' );
	return true;
}

/**
 * Initialise a global language object for content language. This must happen
 * early on, even before user language is known, to determine labels for
 * additional namespaces. In contrast, messages can be initialised much later
 * when they are actually needed.
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

/**
 * This hook registers parser functions and hooks to the given parser. It is
 * called during SMW initialisation. Note that parser hooks are something different
 * than MW hooks in general, which explains the two-level registration.
 * 
 * @since 1.5.3
 */
function smwfRegisterParserFunctions( Parser &$parser ) {
	$parser->setFunctionHook( 'ask', array( 'SMWAsk', 'render' ) );
	$parser->setFunctionHook( 'show', array( 'SMWShow', 'render' ) );
	$parser->setFunctionHook( 'info', array( 'SMWInfo', 'render' ) );
	$parser->setFunctionHook( 'concept', array( 'SMWConcept', 'render' ) );
	$parser->setFunctionHook( 'set', array( 'SMWSet', 'render' ) );
	$parser->setFunctionHook( 'set_recurring_event', array( 'SMWSetRecurringEvent', 'render' ) );
	$parser->setFunctionHook( 'declare', array( 'SMWDeclare', 'render' ), SFH_OBJECT_ARGS );

	return true; // Always return true, in order not to stop MW's hook processing!
}

/**
 * Adds the 'Powered by Semantic MediaWiki' button right next to the default
 * 'Powered by MediaWiki' button at the bottom of every page. This works
 * only with MediaWiki 1.17+.
 * It might make sense to make this configurable via a variable, if some
 * admins don't want it.
 *
 * @since 1.5.4
 *
 * @return true
 */
function smwfAddPoweredBySMW( &$text, $skin ) {
	global $smwgScriptPath;
	$url = htmlspecialchars( "$smwgScriptPath/skins/images/smw_button.png" );
	$text .= ' <a href="http://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki"><img src="'. $url . '" alt="Powered by Semantic MediaWiki" /></a>';

	return true; // Always return true, in order not to stop MW's hook processing!
}
