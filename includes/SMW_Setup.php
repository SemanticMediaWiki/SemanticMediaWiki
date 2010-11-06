<?php

/**
 * Global functions used for setting up the Semantic MediaWiki extension.
 * 
 * @file SMW_Setup.php
 * @ingroup SMW
 */

// The SMW version number.
define( 'SMW_VERSION', '1.5.3' );

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
	global $wgVersion, $wgExtensionFunctions, $wgAutoloadClasses, $wgSpecialPages, $wgSpecialPageGroups, $wgHooks, $wgExtensionMessagesFiles;
	global $smwgIP, $smwgNamespace, $wgJobClasses, $wgExtensionAliasesFiles, $wgServer;

	// The dot tells that the domain is not complete. It will be completed
	// in the Export since we do not want to create a title object here when
	// it is not needed in many cases.
	if ( $namespace === null ) {
		wfWarn( 'You should be providing the domain name to enableSemantics()' );
		$namespace = parse_url( $wgServer, PHP_URL_HOST );
	}
	if ( !$complete && ( $smwgNamespace !== '' ) ) {
		$smwgNamespace = '.' . $namespace;
	} else {
		$smwgNamespace = $namespace;
	}

	$wgExtensionFunctions[] = 'smwfSetupExtension';
	// FIXME: Can be removed when new style magic words are used (introduced in r52503)
	$wgHooks['LanguageGetMagic'][] = 'smwfAddMagicWords'; // setup names for parser functions (needed here)
	$wgExtensionMessagesFiles['SemanticMediaWiki'] = $smwgIP . 'languages/SMW_Messages.php'; // register messages (requires MW=>1.11)

	$wgHooks['ParserTestTables'][] = 'smwfOnParserTestTables';
	$wgHooks['AdminLinks'][] = 'smwfAddToAdminLinks';
	
	$wgHooks['ResourceLoaderRegisterModules'][] = 'SMWHooks::registerResourceLoaderModules';

	if ( version_compare( $wgVersion, '1.17alpha', '>=' ) ) {
		// For MediaWiki 1.17 alpha and later.
		$wgHooks['ExtensionTypes'][] = 'smwfAddSemanticExtensionType';
	} else {
		// For pre-MediaWiki 1.17 alpha.
		$wgHooks['SpecialVersionExtensionTypes'][] = 'smwfOldAddSemanticExtensionType';
	}

	// Register special pages aliases file
	$wgExtensionAliasesFiles['SemanticMediaWiki'] = $smwgIP . 'languages/SMW_Aliases.php';

	// Set up autoloading; essentially all classes should be autoloaded!
	$wgAutoloadClasses['SMWHooks']       			= $smwgIP . 'SMW.hooks.php';
	
	$wgAutoloadClasses['SMWParserExtensions']       = $smwgIP . 'includes/SMW_ParserExtensions.php';
	$wgAutoloadClasses['SMWInfolink']               = $smwgIP . 'includes/SMW_Infolink.php';
	$wgAutoloadClasses['SMWFactbox']                = $smwgIP . 'includes/SMW_Factbox.php';
	$wgAutoloadClasses['SMWParseData']              = $smwgIP . 'includes/SMW_ParseData.php';
	$wgAutoloadClasses['SMWOutputs']                = $smwgIP . 'includes/SMW_Outputs.php';
	$wgAutoloadClasses['SMWSemanticData']           = $smwgIP . 'includes/SMW_SemanticData.php';
	$wgAutoloadClasses['SMWResultPrinter']          = $smwgIP . 'includes/SMW_QueryPrinter.php';
	$wgAutoloadClasses['SMWDataValueFactory']   	= $smwgIP . 'includes/SMW_DataValueFactory.php';
	$wgAutoloadClasses['SMWDataValue']           	= $smwgIP . 'includes/SMW_DataValue.php';
	$wgAutoloadClasses['SMWQueryLanguage']          = $smwgIP . 'includes/SMW_QueryLanguage.php';

	// Article pages
	$apDir = $smwgIP . 'includes/articlepages/';
	$wgAutoloadClasses['SMWOrderedListPage']        = $apDir . 'SMW_OrderedListPage.php';
	$wgAutoloadClasses['SMWTypePage']               = $apDir . 'SMW_TypePage.php';
	$wgAutoloadClasses['SMWPropertyPage']           = $apDir . 'SMW_PropertyPage.php';
	$wgAutoloadClasses['SMWConceptPage']            = $apDir . 'SMW_ConceptPage.php';

	// Printers
	$qpDir = $smwgIP . 'includes/queryprinters/';
	$wgAutoloadClasses['SMWAutoResultPrinter']      = $qpDir . 'SMW_QP_Auto.php';
	$wgAutoloadClasses['SMWTableResultPrinter']     = $qpDir . 'SMW_QP_Table.php';
	$wgAutoloadClasses['SMWListResultPrinter']      = $qpDir . 'SMW_QP_List.php';
	$wgAutoloadClasses['SMWCategoryResultPrinter']  = $qpDir . 'SMW_QP_Category.php';
	$wgAutoloadClasses['SMWEmbeddedResultPrinter']  = $qpDir . 'SMW_QP_Embedded.php';
	$wgAutoloadClasses['SMWRSSResultPrinter']       = $qpDir . 'SMW_QP_RSSlink.php';
	$wgAutoloadClasses['SMWCsvResultPrinter']       = $qpDir . 'SMW_QP_CSV.php';
	$wgAutoloadClasses['SMWJSONResultPrinter']      = $qpDir . 'SMW_QP_JSONlink.php';

	// Datavalues
	$dvDir = $smwgIP . 'includes/datavalues/';
	$wgAutoloadClasses['SMWContainerValue']			= $dvDir . 'SMW_DV_Container.php';
	$wgAutoloadClasses['SMWRecordValue']         	= $dvDir . 'SMW_DV_Record.php';
	$wgAutoloadClasses['SMWErrorvalue']          	= $dvDir . 'SMW_DV_Error.php';
	$wgAutoloadClasses['SMWStringValue']         	= $dvDir . 'SMW_DV_String.php';
	$wgAutoloadClasses['SMWWikiPageValue']       	= $dvDir . 'SMW_DV_WikiPage.php';
	$wgAutoloadClasses['SMWSimpleWikiPageValue'] 	= $dvDir . 'SMW_DV_SimpleWikiPage.php';
	$wgAutoloadClasses['SMWPropertyValue']       	= $dvDir . 'SMW_DV_Property.php';
	$wgAutoloadClasses['SMWURIValue']            	= $dvDir . 'SMW_DV_URI.php';
	$wgAutoloadClasses['SMWTypesValue']          	= $dvDir . 'SMW_DV_Types.php';
	$wgAutoloadClasses['SMWTypeListValue']      	= $dvDir . 'SMW_DV_TypeList.php';
	$wgAutoloadClasses['SMWErrorValue']         	= $dvDir . 'SMW_DV_Error.php';
	$wgAutoloadClasses['SMWNumberValue']         	= $dvDir . 'SMW_DV_Number.php';
	$wgAutoloadClasses['SMWTemperatureValue']    	= $dvDir . 'SMW_DV_Temperature.php';
	$wgAutoloadClasses['SMWLinearValue']         	= $dvDir . 'SMW_DV_Linear.php';
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

	// Parser hooks
	$phDir = $smwgIP . 'includes/parserhooks/';
	$wgAutoloadClasses['SMWAsk']               		= $phDir . 'SMW_Ask.php';
	$wgAutoloadClasses['SMWShow']               	= $phDir . 'SMW_Show.php';
	$wgAutoloadClasses['SMWInfo']               	= $phDir . 'SMW_Info.php';
	$wgAutoloadClasses['SMWConcept']               	= $phDir . 'SMW_Concept.php';
	$wgAutoloadClasses['SMWSet']               		= $phDir . 'SMW_Set.php';
	$wgAutoloadClasses['SMWSetRecurringEvent']      = $phDir . 'SMW_SetRecurringEvent.php';
	$wgAutoloadClasses['SMWDeclare']              	= $phDir . 'SMW_Declare.php';
	
	// Stores & queries
	$wgAutoloadClasses['SMWQueryProcessor']         = $smwgIP . 'includes/SMW_QueryProcessor.php';
	$wgAutoloadClasses['SMWQueryParser']            = $smwgIP . 'includes/SMW_QueryParser.php';
	$wgAutoloadClasses['SMWRecordDescription']      = $smwgIP . 'includes/SMW_Record_Descriptions.php';
	$wgAutoloadClasses['SMWRecordFieldDescription'] = $smwgIP . 'includes/SMW_Record_Descriptions.php';

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
	$wgAutoloadClasses['SMWSQLStore2Table']         = $stoDir . 'SMW_SQLStore2Table.php';
	$wgAutoloadClasses['SMWSQLHelpers']             = $stoDir . 'SMW_SQLHelpers.php';

	// To ensure SMW remains compatible with pre 1.16.
	if ( !array_key_exists( 'Html', $wgAutoloadClasses ) ) {
		$wgAutoloadClasses['Html'] = $smwgIP . 'compat/Html.php';
	}
	
	// Do not autoload RAPStore, since some special pages load all autoloaded classes, which causes
	// troubles with RAP store if RAP is not installed (require_once fails).
	// $wgAutoloadClasses['SMWRAPStore']             = $smwgIP . 'includes/storage/SMW_RAPStore.php';
	$wgAutoloadClasses['SMWTestStore']              = $smwgIP . 'includes/storage/SMW_TestStore.php';

	///// Register specials, do that early on in case some other extension calls "addPage" /////
	$wgAutoloadClasses['SMWQueryPage']              = $smwgIP . 'specials/QueryPages/SMW_QueryPage.php';
	$wgAutoloadClasses['SMWAskPage']                = $smwgIP . 'specials/AskSpecial/SMW_SpecialAsk.php';
	$wgSpecialPages['Ask']                          = array( 'SMWAskPage' );
	$wgSpecialPageGroups['Ask']                     = 'smw_group';

	$wgAutoloadClasses['SMWSpecialBrowse']          = $smwgIP . 'specials/SearchTriple/SMW_SpecialBrowse.php';
	$wgSpecialPages['Browse']                       = array( 'SMWSpecialBrowse' );
	$wgSpecialPageGroups['Browse']                  = 'smw_group';

	$wgAutoloadClasses['SMWPageProperty']           = $smwgIP . 'specials/SearchTriple/SMW_SpecialPageProperty.php';
	$wgSpecialPages['PageProperty']                 = array( 'SMWPageProperty' );
	$wgSpecialPageGroups['PageProperty']            = 'smw_group';

	$wgAutoloadClasses['SMWSearchByProperty']       = $smwgIP . 'specials/SearchTriple/SMW_SpecialSearchByProperty.php';
	$wgSpecialPages['SearchByProperty']             = array( 'SMWSearchByProperty' );
	$wgSpecialPageGroups['SearchByProperty']        = 'smw_group';

	$wgAutoloadClasses['SMWURIResolver']            = $smwgIP . 'specials/URIResolver/SMW_SpecialURIResolver.php';
	$wgSpecialPages['URIResolver']                  = array( 'SMWURIResolver' );

	$wgAutoloadClasses['SMWAdmin']                  = $smwgIP . 'specials/SMWAdmin/SMW_SpecialSMWAdmin.php';
	$wgSpecialPages['SMWAdmin']                     = array( 'SMWAdmin' );
	$wgSpecialPageGroups['SMWAdmin']                = 'smw_group';

	$wgAutoloadClasses['SMWSpecialSemanticStatistics'] = $smwgIP . 'specials/Statistics/SMW_SpecialStatistics.php';
	$wgSpecialPages['SemanticStatistics']           = array( 'SMWSpecialSemanticStatistics' );
	$wgSpecialPageGroups['SemanticStatistics']      = 'wiki'; // Similar to Special:Statistics

	$wgAutoloadClasses['SMWOWLExport']       		= $smwgIP . 'includes/export/SMW_OWLExport.php';
	$wgAutoloadClasses['SMWSpecialOWLExport']       = $smwgIP . 'specials/Export/SMW_SpecialOWLExport.php';
	$wgSpecialPages['ExportRDF']                    = array( 'SMWSpecialOWLExport' );
	$wgSpecialPageGroups['ExportRDF']               = 'smw_group';

	$wgAutoloadClasses['SMWSpecialProperties']      = $smwgIP . 'specials/QueryPages/SMW_SpecialProperties.php';
	$wgSpecialPages['Properties']                   = array( 'SMWSpecialProperties' );
	$wgSpecialPageGroups['Properties']              = 'pages';

	$wgAutoloadClasses['SMWSpecialTypes']           = $smwgIP . 'specials/QueryPages/SMW_SpecialTypes.php';
	$wgSpecialPages['Types']                        = array( 'SMWSpecialTypes' );
	$wgSpecialPageGroups['Types']                   = 'pages';

	$wgAutoloadClasses['SMWSpecialUnusedProperties'] = $smwgIP . 'specials/QueryPages/SMW_SpecialUnusedProperties.php';
	$wgSpecialPages['UnusedProperties']             = array( 'SMWSpecialUnusedProperties' );
	$wgSpecialPageGroups['UnusedProperties']        = 'maintenance';

	$wgAutoloadClasses['SMWSpecialWantedProperties'] = $smwgIP . 'specials/QueryPages/SMW_SpecialWantedProperties.php';
	$wgSpecialPages['WantedProperties']             = array( 'SMWSpecialWantedProperties' );
	$wgSpecialPageGroups['WantedProperties']        = 'maintenance';

	// Register Jobs
	$wgJobClasses['SMWUpdateJob']                   = 'SMWUpdateJob';
	$wgAutoloadClasses['SMWUpdateJob']              = $smwgIP . 'includes/jobs/SMW_UpdateJob.php';

	$wgJobClasses['SMWRefreshJob']                  = 'SMWRefreshJob';
	$wgAutoloadClasses['SMWRefreshJob']             = $smwgIP . 'includes/jobs/SMW_RefreshJob.php';

	return true;
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
	global $smwgIP, $wgHooks, $wgParser, $wgExtensionCredits, $smwgEnableTemplateSupport, $smwgMasterStore, $smwgIQRunningNumber, $wgLanguageCode, $wgVersion, $smwgToolboxBrowseLink, $smwgMW_1_14;

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
	$smwgMW_1_14 = true; // assume latest 1.14 API

	// Registration of the extension credits, see Special:Version.
	$wgExtensionCredits['semantic'][] = array(
		'path' => __FILE__,
		'name' => 'Semantic MediaWiki',
		'version' => SMW_VERSION,
		'author' => "[http://korrekt.org Markus&#160;Krötzsch], [http://simia.net Denny&#160;Vrandecic] and [http://www.ohloh.net/p/smw/contributors others]. Maintained by [http://www.aifb.kit.edu/web/Wissensmanagement/en AIFB Karlsruhe].",
		'url' => 'http://semantic-mediawiki.org',
		'descriptionmsg' => 'smw-desc'
	);

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
 * Register special classes for displaying semantic content on Property/Type
 * pages.
 *
 * @param $title: Title
 * @param $article: Article or null
 *
 * @return true
 */
function smwfOnArticleFromTitle( Title &$title, /* Article */ &$article ) {
	if ( $title->getNamespace() == SMW_NS_TYPE ) {
		$article = new SMWTypePage( $title );
	} elseif ( $title->getNamespace() == SMW_NS_PROPERTY ) {
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
 * Init the additional namepsaces used by Semantic MediaWiki.
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