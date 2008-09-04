<?php
/**
 * Global functions and constants for Semantic MediaWiki.
 * @file
 * @ingroup SMW
 */

/**
 * This documenation group collects source code files belonging to Semantic MediaWiki.
 *
 * For documenting extensions of SMW, please do not use groups starting with "SMW"
 * but make your own groups instead. Browsing at http://semantic-mediawiki.org/doc/
 * is assumed to be easier this way.
 * @defgroup SMW Semantic MediaWiki
 */

define('SMW_VERSION','1.3c-SVN');

// constants for special properties, used for datatype assignment and storage
define('SMW_SP_HAS_TYPE',1);
define('SMW_SP_HAS_URI',2);
define('SMW_SP_INSTANCE_OF',4);
define('SMW_SP_DISPLAY_UNITS', 7);
define('SMW_SP_IMPORTED_FROM',8);
define('SMW_SP_CONVERSION_FACTOR', 12);
define('SMW_SP_SERVICE_LINK', 13);
define('SMW_SP_POSSIBLE_VALUE', 14);
define('SMW_SP_REDIRECTS_TO', 15);
define('SMW_SP_SUBPROPERTY_OF',17);
define('SMW_SP_SUBCLASS_OF',18);
define('SMW_SP_CONCEPT_DESC',19);

/** @deprecated This constant will be removed in SMW 1.4. Use SMW_SP_INSTANCE_OF or SMW_SP_SUBCLASS_OF as appropriate. */
define('SMW_SP_HAS_CATEGORY',4); // name specific for categories, use "instance of" to distinguish from future explicit "subclass of"

// constants for displaying the factbox
define('SMW_FACTBOX_HIDDEN', 1);
define('SMW_FACTBOX_SPECIAL', 2);
define('SMW_FACTBOX_NONEMPTY',  3);
define('SMW_FACTBOX_SHOWN',  5);

// constants for regulating equality reasoning
define('SMW_EQ_NONE', 0);
define('SMW_EQ_SOME', 1);
define('SMW_EQ_FULL', 2);

// flags to classify available query descriptions, used to enable/disable certain features
define('SMW_PROPERTY_QUERY', 1);     // [[some property::...]]
define('SMW_CATEGORY_QUERY', 2);     // [[Category:...]]
define('SMW_CONCEPT_QUERY', 4);      // [[Concept:...]]
define('SMW_NAMESPACE_QUERY', 8);    // [[User:+]] etc.
define('SMW_CONJUNCTION_QUERY', 16); // any conjunctions
define('SMW_DISJUNCTION_QUERY', 32); // any disjunctions (OR, ||)
define('SMW_ANY_QUERY', 0xFFFFFFFF);  // subsumes all other options

// constants for defining which concepts to show only if cached
define('CONCEPT_CACHE_ALL', 4); //show concept elements anywhere only if cached
define('CONCEPT_CACHE_HARD',1); //show without cache if concept is not harder than permitted inline queries
define('CONCEPT_CACHE_NONE',0); //show all concepts even without any cache

// constants for identifying javascripts as used in smwfRequireHeadItem
define('SMW_HEADER_TIMELINE', 1);
define('SMW_HEADER_TOOLTIP', 2);
define('SMW_HEADER_SORTTABLE', 3);
define('SMW_HEADER_STYLE', 4);

// constants for denoting output modes in many functions: HTML or Wiki?
// "File" is for printing results into stand-alone files (e.g. building RSS)
// and should be treated like HTML when building single strings. Only query
// printers tend to have special handling for that.
define('SMW_OUTPUT_HTML', 1);
define('SMW_OUTPUT_WIKI', 2);
define('SMW_OUTPUT_FILE', 3);

// comparators for datavalues:
define('SMW_CMP_EQ',1); // matches only datavalues that are equal to the given value
define('SMW_CMP_LEQ',2); // matches only datavalues that are less or equal than the given value
define('SMW_CMP_GEQ',3); // matches only datavalues that are greater or equal to the given value
define('SMW_CMP_NEQ',4); // matches only datavalues that are unequal to the given value
define('SMW_CMP_LIKE',5); // matches only datavalues that are LIKE the given value

// HTML items to load in current page, use smwfRequireHeadItem to extend
$smwgHeadItems = array();

/**
 * Switch on Semantic MediaWiki. This function must be called in LocalSettings.php
 * after incldung this file. It is used to ensure that required parameters for SMW
 * are really provided, without requiring the existence of a dedicated file
 * SMW_LocalSettings.php. For readability, this is the only global function that
 * does not adhere to the naming conventions.
 */
function enableSemantics($namespace = '', $complete = false) {
	global $smwgIP, $smwgNamespace, $wgExtensionFunctions, $wgAutoloadClasses, $wgSpecialPages, $wgSpecialPageGroups, $wgHooks, $wgExtensionMessagesFiles, $wgJobClasses, $wgExtensionAliasesFiles;
	// The dot tells that the domain is not complete. It will be completed
	// in the Export since we do not want to create a title object here when
	// it is not needed in many cases.
	if ( !$complete && ($smwgNamespace !== '') ) {
		$smwgNamespace = '.' . $namespace;
	} else {
		$smwgNamespace = $namespace;
	}
	$wgExtensionFunctions[] = 'smwfSetupExtension';
	$wgHooks['LanguageGetMagic'][] = 'smwfAddMagicWords'; // setup names for parser functions (needed here)
	$wgExtensionMessagesFiles['SemanticMediaWiki'] = $smwgIP . '/languages/SMW_Messages.php'; // register messages (requires MW=>1.11)

	// Register special pages aliases file
	$wgExtensionAliasesFiles['SemanticMediaWiki'] = $smwgIP . '/languages/SMW_Aliases.php';

	///// Set up autoloading
	///// All classes registered for autoloading here should be tagged with this information:
	///// Add "@note AUTOLOADED" to their class documentation. This avoids useless includes.
	$wgAutoloadClasses['SMWInfolink']               = $smwgIP . '/includes/SMW_Infolink.php';
	$wgAutoloadClasses['SMWFactbox']                = $smwgIP . '/includes/SMW_Factbox.php';
	$wgAutoloadClasses['SMWSemanticData']           = $smwgIP . '/includes/SMW_SemanticData.php';
	$wgAutoloadClasses['SMWOrderedListPage']        = $smwgIP . '/includes/articlepages/SMW_OrderedListPage.php';
	$wgAutoloadClasses['SMWTypePage']               = $smwgIP . '/includes/articlepages/SMW_TypePage.php';
	$wgAutoloadClasses['SMWPropertyPage']           = $smwgIP . '/includes/articlepages/SMW_PropertyPage.php';
	$wgAutoloadClasses['SMWConceptPage']            = $smwgIP . '/includes/articlepages/SMW_ConceptPage.php';
	//// printers
	$wgAutoloadClasses['SMWResultPrinter']          = $smwgIP . '/includes/SMW_QueryPrinter.php';
	$wgAutoloadClasses['SMWTableResultPrinter']     = $smwgIP . '/includes/SMW_QP_Table.php';
	$wgAutoloadClasses['SMWListResultPrinter']      = $smwgIP . '/includes/SMW_QP_List.php';
	$wgAutoloadClasses['SMWTimelineResultPrinter']  = $smwgIP . '/includes/SMW_QP_Timeline.php';
	$wgAutoloadClasses['SMWEmbeddedResultPrinter']  = $smwgIP . '/includes/SMW_QP_Embedded.php';
	$wgAutoloadClasses['SMWTemplateResultPrinter']  = $smwgIP . '/includes/SMW_QP_Template.php';
	$wgAutoloadClasses['SMWRSSResultPrinter']       = $smwgIP . '/includes/SMW_QP_RSSlink.php';
	$wgAutoloadClasses['SMWiCalendarResultPrinter'] = $smwgIP . '/includes/SMW_QP_iCalendar.php';
	$wgAutoloadClasses['SMWvCardResultPrinter']     = $smwgIP . '/includes/SMW_QP_vCard.php';
	$wgAutoloadClasses['SMWCsvResultPrinter']       = $smwgIP . '/includes/SMW_QP_CSV.php';
	//// datavalues
	$wgAutoloadClasses['SMWDataValue']              = $smwgIP . '/includes/SMW_DataValue.php';
	$wgAutoloadClasses['SMWErrorvalue']             = $smwgIP . '/includes/SMW_DV_Error.php';
	///NOTE: other DataValues are registered for autoloading later on by the factory, use the hook
	/// smwInitDatatypes to modify paths for datatype implementations and for registering new types.
	$wgAutoloadClasses['SMWDataValueFactory']       = $smwgIP . '/includes/SMW_DataValueFactory.php';
	// the builtin types are registered by SMWDataValueFactory if needed, will be reliably available
	// to other DV-implementations that register to the factory.
	//// export
	$wgAutoloadClasses['SMWExporter']               = $smwgIP . '/includes/export/SMW_Exporter.php';
	$wgAutoloadClasses['SMWExpData']                = $smwgIP . '/includes/export/SMW_Exp_Data.php';
	$wgAutoloadClasses['SMWExpElement']             = $smwgIP . '/includes/export/SMW_Exp_Element.php';
	$wgAutoloadClasses['SMWExpLiteral']             = $smwgIP . '/includes/export/SMW_Exp_Element.php';
	$wgAutoloadClasses['SMWExpResource']            = $smwgIP . '/includes/export/SMW_Exp_Element.php';
	//// stores & queries
	$wgAutoloadClasses['SMWQueryProcessor']         = $smwgIP . '/includes/SMW_QueryProcessor.php';
	$wgAutoloadClasses['SMWQueryParser']            = $smwgIP . '/includes/SMW_QueryProcessor.php';
	$wgAutoloadClasses['SMWQuery']                  = $smwgIP . '/includes/storage/SMW_Query.php';
	$wgAutoloadClasses['SMWQueryResult']            = $smwgIP . '/includes/storage/SMW_QueryResult.php';
	$wgAutoloadClasses['SMWStore']                  = $smwgIP . '/includes/storage/SMW_Store.php';
	$wgAutoloadClasses['SMWStringCondition']        = $smwgIP . '/includes/storage/SMW_Store.php';
	$wgAutoloadClasses['SMWRequestOptions']         = $smwgIP . '/includes/storage/SMW_Store.php';
	$wgAutoloadClasses['SMWPrintRequest']           = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWThingDescription']       = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWClassDescription']       = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWConceptDescription']     = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWNamespaceDescription']   = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWValueDescription']       = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWValueList']              = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWConjunction']            = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWDisjunction']            = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWSomeProperty']           = $smwgIP . '/includes/storage/SMW_Description.php';
	$wgAutoloadClasses['SMWSQLStore']               = $smwgIP . '/includes/storage/SMW_SQLStore.php';
	$wgAutoloadClasses['SMWSQLStore2']              = $smwgIP . '/includes/storage/SMW_SQLStore2.php';
	// Do not autoload RAPStore, since some special pages load all autoloaded classes, which causes
	// troubles with RAP store if RAP is not installed (require_once fails).
	//$wgAutoloadClasses['SMWRAPStore']             = $smwgIP . '/includes/storage/SMW_RAPStore.php';
	$wgAutoloadClasses['SMWTestStore']              = $smwgIP . '/includes/storage/SMW_TestStore.php';

	///// Register specials, do that early on in case some other extension calls "addPage" /////
	$wgAutoloadClasses['SMWQueryPage']              = $smwgIP . '/specials/QueryPages/SMW_QueryPage.php';
	$wgAutoloadClasses['SMWAskPage']                = $smwgIP . '/specials/AskSpecial/SMW_SpecialAsk.php';
	$wgSpecialPages['Ask']                          = array('SMWAskPage');
	$wgSpecialPageGroups['Ask']                     = 'smw_group';
	$wgAutoloadClasses['SMWSpecialBrowse']          = $smwgIP . '/specials/SearchTriple/SMW_SpecialBrowse.php';
	$wgSpecialPages['Browse']                       = array('SMWSpecialBrowse');
	$wgSpecialPageGroups['Browse']                  = 'smw_group';
	$wgAutoloadClasses['SMWPageProperty']           = $smwgIP . '/specials/SearchTriple/SMW_SpecialPageProperty.php';
	$wgSpecialPages['PageProperty']                 = array('SMWPageProperty');
	$wgSpecialPageGroups['PageProperty']            = 'smw_group';
	$wgAutoloadClasses['SMWSearchByProperty']       = $smwgIP . '/specials/SearchTriple/SMW_SpecialSearchByProperty.php';
	$wgSpecialPages['SearchByProperty']             = array('SMWSearchByProperty');
	$wgSpecialPageGroups['SearchByProperty']        = 'smw_group';
	$wgAutoloadClasses['SMWURIResolver']            = $smwgIP . '/specials/URIResolver/SMW_SpecialURIResolver.php';
	$wgSpecialPages['URIResolver']                  = array('SMWURIResolver');
	$wgAutoloadClasses['SMWAdmin']                  = $smwgIP . '/specials/SMWAdmin/SMW_SpecialSMWAdmin.php';
	$wgSpecialPages['SMWAdmin']                     = array('SMWAdmin');
	$wgSpecialPageGroups['SMWAdmin']                = 'smw_group';
	
	// suboptimal special pages using the SMWSpecialPage wrapper class:
	$wgAutoloadClasses['SMWSpecialPage']            = $smwgIP . '/includes/SMW_SpecialPage.php';
	$wgSpecialPages['Properties']                   = array('SMWSpecialPage','Properties', 'smwfDoSpecialProperties', $smwgIP . '/specials/QueryPages/SMW_SpecialProperties.php');
	$wgSpecialPageGroups['Properties']              = 'pages';
	$wgSpecialPages['UnusedProperties']             = array('SMWSpecialPage','UnusedProperties', 'smwfDoSpecialUnusedProperties', $smwgIP . '/specials/QueryPages/SMW_SpecialUnusedProperties.php', true, '');
	$wgSpecialPageGroups['UnusedProperties']        = 'maintenance';
	$wgSpecialPages['WantedProperties']             = array('SMWSpecialPage','WantedProperties', 'smwfDoSpecialWantedProperties', $smwgIP . '/specials/QueryPages/SMW_SpecialWantedProperties.php', true, '');
	$wgSpecialPageGroups['WantedProperties']        = 'maintenance';
	$wgSpecialPages['ExportRDF']                    = array('SMWSpecialPage','ExportRDF', 'smwfDoSpecialOWLExport', $smwgIP . '/specials/Export/SMW_SpecialOWLExport.php', true, '');
	$wgSpecialPageGroups['ExportRDF']               = 'smw_group';
	$wgSpecialPages['SemanticStatistics']           = array('SMWSpecialPage','SemanticStatistics', 'smwfExecuteSemanticStatistics', $smwgIP . '/specials/Statistics/SMW_SpecialStatistics.php', true, '');
	$wgSpecialPageGroups['SemanticStatistics']      = 'wiki'; // like Special:Statistics
	$wgSpecialPages['Types']                        = array('SMWSpecialPage','Types', 'smwfDoSpecialTypes', $smwgIP . '/specials/QueryPages/SMW_SpecialTypes.php');
	$wgSpecialPageGroups['Types']                   = 'pages';

	///// Register Jobs
	$wgAutoloadClasses['SMWUpdateJob']              = $smwgIP . '/includes/jobs/SMW_UpdateJob.php';
	$wgJobClasses['SMWUpdateJob']                   = 'SMWUpdateJob';
	$wgJobClasses['SMWRefreshJob']                  = 'SMWRefreshJob';
	$wgAutoloadClasses['SMWRefreshJob']             = $smwgIP . '/includes/jobs/SMW_RefreshJob.php';

	return true;
}

/**
 *  Do the actual intialisation of the extension. This is just a delayed init that makes sure
 *  MediaWiki is set up properly before we add our stuff.
 */
function smwfSetupExtension() {
	wfProfileIn('smwfSetupExtension (SMW)');
	global $smwgIP, $smwgStoreActive, $wgHooks, $wgParser, $wgExtensionCredits, $smwgEnableTemplateSupport, $smwgMasterStore, $smwgIQRunningNumber, $wgLanguageCode;

	/**
	* Setting this to false prevents any new data from being stored in
	* the static SMWSemanticData store, and disables printing of the
	* factbox, and clearing of the existing data.
	* This is a hack to enable parsing of included articles in a save
	* way without importing their annotations. Unfortunately, there
	* appears to be no way for finding out whether the current parse
	* is the "main" parse, or whether some intro, docu, or whatever
	* text is parsed. Using the hook mechanism, we have to rely on
	* globals/static fields -- so we cannot somehow differentiate this
	* store between parsers.
	*/
	$smwgStoreActive = true;

	$smwgMasterStore = NULL;
	smwfInitContentLanguage($wgLanguageCode); // this really could not be done in enableSemantics()
	wfLoadExtensionMessages('SemanticMediaWiki'); /// TODO: this is extremely slow; up to 10% of page display time (on a page with queries!) are consumed by loading unnecessary messages from a large file ...
	/// Past SMW releases had an average of about 1% extension loading time per call, while we are now up at 10%!
	/// (if no PHP caching is enabled, things become better with ACP but impact still is noticeable)
	/// Should we return to our earlier message management for releases?
	$smwgIQRunningNumber = 0;

	///// register hooks /////
	require_once($smwgIP . '/includes/SMW_Hooks.php');
	require_once($smwgIP . '/includes/SMW_RefreshTab.php');

	$wgHooks['InternalParseBeforeLinks'][] = 'smwfParserHook'; // parse annotations
	$wgHooks['ArticleSave'][] = 'smwfPreSaveHook'; // check some settings here
	$wgHooks['ArticleUndelete'][] = 'smwfUndeleteHook'; // restore annotations
	$wgHooks['ArticleDelete'][] = 'smwfDeleteHook'; // delete annotations
	$wgHooks['TitleMoveComplete'][]='smwfMoveHook'; // move annotations
	$wgHooks['ParserAfterTidy'][] = 'smwfParserAfterTidy'; // add items to HTML header during parsing
	$wgHooks['BeforePageDisplay'][]='smwfAddHTMLHeadersOutput'; // add items to HTML header during output
    $wgHooks['LinksUpdateConstructed'][] = 'smwfLinkUpdateHook'; // update data after template change and at safe
	$wgHooks['BeforeParserrenderImageGallery'][] = 'smwfBlockFactboxFromImageGallery';

	$wgHooks['ArticleFromTitle'][] = 'smwfShowListPage'; // special implementations for property/type articles

	if( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
		$wgHooks['ParserFirstCallInit'][] = 'smwfRegisterParserFunctions';
	} else {
		if ( class_exists( 'StubObject' ) && !StubObject::isRealObject( $wgParser ) ) {
			$wgParser->_unstub();
		}
		smwfRegisterParserFunctions( $wgParser );
	}

	///// credits (see "Special:Version") /////
	$wgExtensionCredits['parserhook'][]= array('name'=>'Semantic&nbsp;MediaWiki', 'version'=>SMW_VERSION, 'author'=>"Klaus&nbsp;Lassleben, [http://korrekt.org Markus&nbsp;Kr&ouml;tzsch], [http://simia.net Denny&nbsp;Vrandecic], S&nbsp;Page, and others. Maintained by [http://www.aifb.uni-karlsruhe.de/Forschungsgruppen/WBS/english AIFB Karlsruhe].", 'url'=>'http://semantic-mediawiki.org', 'description' => 'Making your wiki more accessible&nbsp;&ndash; for machines \'\'and\'\' humans. [http://semantic-mediawiki.org/wiki/Help:User_manual View online documentation.]');

	wfProfileOut('smwfSetupExtension (SMW)');
	return true;
}

/**
 * This hook registers parser functions and hooks to the given parser.
 * Note that parser hooks are something different than MW hooks
 * in general, which explains the two-level registration.
 */
function smwfRegisterParserFunctions( &$parser) {
	$parser->setHook( 'ask', 'smwfProcessInlineQuery' );
	$parser->setFunctionHook( 'ask', 'smwfProcessInlineQueryParserFunction' );
	$parser->setFunctionHook( 'show', 'smwfProcessShowParserFunction' );
	$parser->setFunctionHook( 'info', 'smwfProcessInfoParserFunction' );
	$parser->setFunctionHook( 'concept', 'smwfProcessConceptParserFunction' );
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
 * The <ask> parser hook processing part.
 */
function smwfProcessInlineQuery($querytext, $params, &$parser) {
	global $smwgQEnabled, $smwgIQRunningNumber;
	if ($smwgQEnabled) {
		$smwgIQRunningNumber++;
		return SMWQueryProcessor::getResultFromHookParams($querytext,$params,SMW_OUTPUT_HTML);
	} else {
		wfLoadExtensionMessages('SemanticMediaWiki');
		return smwfEncodeMessages(array(wfMsgForContent('smw_iq_disabled')));
	}
}

/**
 * The {{#ask }} parser function processing part.
 */
function smwfProcessInlineQueryParserFunction(&$parser) {
	global $smwgQEnabled, $smwgIQRunningNumber;
	if ($smwgQEnabled) {
		$smwgIQRunningNumber++;
		$params = func_get_args();
		array_shift( $params ); // we already know the $parser ...
		return SMWQueryProcessor::getResultFromFunctionParams($params,SMW_OUTPUT_WIKI);
	} else {
		wfLoadExtensionMessages('SemanticMediaWiki');
		return smwfEncodeMessages(array(wfMsgForContent('smw_iq_disabled')));
	}
}

/**
 * The {{#show }} parser function processing part.
 */
function smwfProcessShowParserFunction(&$parser) {
	global $smwgQEnabled, $smwgIQRunningNumber;
	if ($smwgQEnabled) {
		$smwgIQRunningNumber++;
		$params = func_get_args();
		array_shift( $params ); // we already know the $parser ...
		return SMWQueryProcessor::getResultFromFunctionParams($params,SMW_OUTPUT_WIKI,SMWQueryProcessor::INLINE_QUERY,true);
	} else {
		wfLoadExtensionMessages('SemanticMediaWiki');
		return smwfEncodeMessages(array(wfMsgForContent('smw_iq_disabled')));
	}
}

/**
 * The {{#concept }} parser function processing part.
 */
function smwfProcessConceptParserFunction(&$parser) {
	global $smwgQDefaultNamespaces, $smwgQMaxSize, $smwgQMaxDepth, $smwgPreviousConcept, $wgContLang;
	wfLoadExtensionMessages('SemanticMediaWiki');
	// The global $smwgConceptText is used to pass information to the MW hooks for storing it,
	// $smwgPreviousConcept is used to detect if we already have a concept defined for this page.
	$title = $parser->getTitle();
	SMWFactbox::initStorage($title); // make sure we have the right title
	if ($title->getNamespace() != SMW_NS_CONCEPT) {
		return smwfEncodeMessages(array(wfMsgForContent('smw_no_concept_namespace')));
	} elseif (isset($smwgPreviousConcept) && ($smwgPreviousConcept == $title->getText())) {
		return smwfEncodeMessages(array(wfMsgForContent('smw_multiple_concepts')));
	}
	$smwgPreviousConcept = $title->getText();

	// process input:
	$params = func_get_args();
	array_shift( $params ); // we already know the $parser ...
	$concept_input = array_shift( $params ); // use first parameter as concept (query) string
	$query = SMWQueryProcessor::createQuery($concept_input, array('limit' => 20, 'format' => 'list'), SMWQueryProcessor::CONCEPT_DESC);
	$concept_text = $query->getDescription()->getQueryString();
	$concept_docu = array_shift( $params ); // second parameter, if any, might be a description

	$dv = SMWDataValueFactory::newSpecialValue(SMW_SP_CONCEPT_DESC);
	$dv->setValues($concept_text, $concept_docu, $query->getDescription()->getQueryFeatures(), $query->getDescription()->getSize(), $query->getDescription()->getDepth());
	if (SMWFactbox::$semdata !== NULL) {
		SMWFactbox::$semdata->addSpecialValue(SMW_SP_CONCEPT_DESC,$dv);
	}

	// display concept box:
	$rdflink = SMWInfolink::newInternalLink(wfMsgForContent('smw_viewasrdf'), $wgContLang->getNsText(NS_SPECIAL) . ':ExportRDF/' . $title->getPrefixedText(), 'rdflink');
	smwfRequireHeadItem(SMW_HEADER_STYLE);

// 	$qresult = smwfGetStore()->getQueryResult($query);
// 	$printer = SMWQueryProcessor::getResultPrinter('list', SMWQueryProcessor::CONCEPT_DESC, $qresult);
// 	$printer->setShowErrors(false);
// 	$resultlink = $printer->getResult($qresult, array('sep' => ',_'), SMW_OUTPUT_WIKI);

	$result = '<div class="smwfact"><span class="smwfactboxhead">' . wfMsgForContent('smw_concept_description',$title->getText()) .
	          (count($query->getErrors())>0?' ' . smwfEncodeMessages($query->getErrors()):'') .
	          '</span>' . '<span class="smwrdflink">' . $rdflink->getWikiText() . '</span>' . '<br />' .
	          ($concept_docu?"<p>$concept_docu</p>":'') .
	          '<pre>' . str_replace('[', '&#x005B;', $concept_text) . "</pre>\n" .
// 	          $resultlink . 
	          '</div>';
	return $result;
}

/**
 * The {{#info }} parser function processing part.
 */
function smwfProcessInfoParserFunction(&$parser) {
	$params = func_get_args();
	array_shift( $params ); // we already know the $parser ...
	$content = array_shift( $params ); // use only first parameter, ignore rest (may get meaning later)
	return smwfEncodeMessages(array($content), 'info');
}

/**********************************************/
/***** Header modifications               *****/
/**********************************************/

/**
 * Add some head items (e.g. JavaScripts) to the current list of things 
 * that SMW will add to the returned HTML page. The ID can be one of SMW's
 * SMW_HEADER_... constants, or a string id followed by the actual item
 * that should be added to the output html header. In the first case, the
 * $item parameter should be left empty.
 */
function smwfRequireHeadItem($id, $item = '') {
	global $smwgHeadItems;
	if (is_numeric($id)) {
		global $smwgScriptPath;
		switch ($id) {
			case SMW_HEADER_TIMELINE:
				smwfRequireHeadItem(SMW_HEADER_STYLE);
				$smwgHeadItems['smw_tl'] = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SimileTimeline/timeline-api.js"></script>';
				$smwgHeadItems['smw_tlhelper'] = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SMW_timeline.js"></script>';
			return;
			case SMW_HEADER_TOOLTIP:
				smwfRequireHeadItem(SMW_HEADER_STYLE);
				$smwgHeadItems['smw_tt'] = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SMW_tooltip.js"></script>';
			return;
			case SMW_HEADER_SORTTABLE:
				smwfRequireHeadItem(SMW_HEADER_STYLE);
				$smwgHeadItems['smw_st'] = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SMW_sorttable.js"></script>';
			return;
			case SMW_HEADER_STYLE:
				global $wgContLang;
				$smwgHeadItems['smw_css'] = '<link rel="stylesheet" type="text/css" media="screen, projection" href="' . $smwgScriptPath . '/skins/SMW_custom.css" />';
				if ($wgContLang->isRTL()) { // right-to-left support
					$smwgHeadItems['smw_cssrtl'] = '<link rel="stylesheet" type="text/css" media="screen, projection" href="' . $smwgScriptPath . '/skins/SMW_custom_rtl.css" />';
				}
			return;
		}
	} else { // custom head item
		$smwgHeadItems[$id] = $item;
	}
}

/**
 * Hook function for three tasks:
 * (1) insert HTML headers (CSS, JavaScript, and meta tags) into parser 
 * output. This is our preferred method of working off the required scripts, since it 
 * exploits parser caching.
 * (2) Fetch category information and other final settings from parser output.
 */
function smwfParserAfterTidy(&$parser, &$text) {
	global $smwgHeadItems, $smwgStoreActive;
	SMWFactbox::initStorage($parser->getTitle()); // be sure we have our title, strange things happen in parsing
	if (!$smwgStoreActive || (SMWFactbox::$semdata === NULL)) return true; // avoid doing this in SMW-generated sub-parsers
	// make HTML header
	foreach ($smwgHeadItems as $key => $item) {
		$parser->mOutput->addHeadItem("\t\t" . $item . "\n", $key);
	}
	$smwgHeadItems = array(); // flush array so that smwfAddHTMLHeader does not take needless actions
	// fetch category data
	$categories = $parser->mOutput->getCategoryLinks();
	foreach ($categories as $name) {
		$dv = SMWDataValueFactory::newSpecialValue(SMW_SP_INSTANCE_OF);
		$dv->setValues($name,NS_CATEGORY);
		SMWFactbox::$semdata->addSpecialValue(SMW_SP_INSTANCE_OF,$dv);
		if (SMWFactbox::$semdata->getSubject()->getNamespace() == NS_CATEGORY) {
			SMWFactbox::$semdata->addSpecialValue(SMW_SP_SUBCLASS_OF,$dv);
		}
	}
	$sortkey = ($parser->mDefaultSort?$parser->mDefaultSort:SMWFactbox::$semdata->getSubject()->getText());
	SMWFactbox::$semdata->getSubject()->setSortkey($sortkey);
	return true;
}

/**
 * This method is in charge of inserting additional CSS, JavaScript, and meta tags
 * into the HTML header of each page. This method is needed for pages that are not 
 * parsed, especially for special pages. All others get their headers with the parser
 * output (exploiting parser caching).
 */
function smwfAddHTMLHeadersOutput(&$out) {
	global $smwgHeadItems, $smwgStoreActive;
	if (!$smwgStoreActive) return true; // avoid doing this in SMW-generated sub-parsers
	// Add scripts to output if not done already (should happen only if we are
	// not using a parser, e.g on special pages).
	foreach ($smwgHeadItems as $key => $item) {
		$out->addHeadItem($key, "\t\t" . $item . "\n");
	}
	$smwgHeadItems = array(); // flush array
	return true; // always return true, in order not to stop MW's hook processing!
}

/**********************************************/
/***** namespace settings                 *****/
/**********************************************/

	/**
	 * Init the additional namepsaces used by Semantic MediaWiki. The
	 * parameter denotes the least unused even namespace ID that is
	 * greater or equal to 100.
	 */
	function smwfInitNamespaces() {
		global $smwgNamespaceIndex, $wgExtraNamespaces, $wgNamespaceAliases, $wgNamespacesWithSubpages, $wgLanguageCode, $smwgContLang, $smwgSMWBetaCompatible;

		if (!isset($smwgNamespaceIndex)) {
			$smwgNamespaceIndex = 100;
		}

		define('SMW_NS_PROPERTY',       $smwgNamespaceIndex+2);
		define('SMW_NS_PROPERTY_TALK',  $smwgNamespaceIndex+3);
		define('SMW_NS_TYPE',           $smwgNamespaceIndex+4);
		define('SMW_NS_TYPE_TALK',      $smwgNamespaceIndex+5);
		// 106 and 107 are occupied by the Semantic Forms
		define('SMW_NS_CONCEPT',        $smwgNamespaceIndex+8);
		define('SMW_NS_CONCEPT_TALK',   $smwgNamespaceIndex+9);

		/// For backwards compatibility. The namespaces are only registered if $smwgSMWBetaCompatible.
		define('SMW_NS_RELATION',       $smwgNamespaceIndex);
		define('SMW_NS_RELATION_TALK',  $smwgNamespaceIndex+1);

		smwfInitContentLanguage($wgLanguageCode);

		// Register namespace identifiers
		if (!is_array($wgExtraNamespaces)) { $wgExtraNamespaces=array(); }
		$namespaces = $smwgContLang->getNamespaces();
		$namespacealiases = $smwgContLang->getNamespaceAliases();
		if (!$smwgSMWBetaCompatible) { // redirect obsolete namespaces to new ones
			$namespacealiases[$namespaces[SMW_NS_RELATION]] = SMW_NS_PROPERTY;
			$namespacealiases[$namespaces[SMW_NS_RELATION_TALK]] = SMW_NS_PROPERTY_TALK;
			unset($namespaces[SMW_NS_RELATION]);
			unset($namespaces[SMW_NS_RELATION_TALK]);
			foreach ($namespacealiases as $alias => $namespace) { // without this, links using aliases break
				if ( ($namespace == SMW_NS_RELATION) || ($namespace == SMW_NS_RELATION_TALK) ) {
					$namespacealiases[$alias] = $namespace+2;
				}
			}
		}
		$wgExtraNamespaces = $wgExtraNamespaces + $namespaces;
		$wgNamespaceAliases = $wgNamespaceAliases + $namespacealiases;

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
	 */
	function smwfAddMagicWords(&$magicWords, $langCode) {
		$magicWords['ask']  = array( 0, 'ask' );
		$magicWords['show'] = array( 0, 'show' );
		$magicWords['info'] = array( 0, 'info' );
		$magicWords['concept'] = array( 0, 'concept' );
		$magicWords['SMW_NOFACTBOX'] = array( 0, '__NOFACTBOX__' );
		$magicWords['SMW_SHOWFACTBOX'] = array( 0, '__SHOWFACTBOX__' );
		return true;
	}

	/**
	 * Initialise a global language object for content language. This
	 * must happen early on, even before user language is known, to
	 * determine labels for additional namespaces. In contrast, messages
	 * can be initialised much later when they are actually needed.
	 */
	function smwfInitContentLanguage($langcode) {
		global $smwgIP, $smwgContLang;
		if (!empty($smwgContLang)) { return; }
		wfProfileIn('smwfInitContentLanguage (SMW)');

		$smwContLangFile = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );
		$smwContLangClass = 'SMWLanguage' . str_replace( '-', '_', ucfirst( $langcode ) );
		if (file_exists($smwgIP . '/languages/'. $smwContLangFile . '.php')) {
			include_once( $smwgIP . '/languages/'. $smwContLangFile . '.php' );
		}

		// fallback if language not supported
		if ( !class_exists($smwContLangClass)) {
			include_once($smwgIP . '/languages/SMW_LanguageEn.php');
			$smwContLangClass = 'SMWLanguageEn';
		}
		$smwgContLang = new $smwContLangClass();

		wfProfileOut('smwfInitContentLanguage (SMW)');
	}

/**********************************************/
/***** other global helpers               *****/
/**********************************************/

	/**
	 * Return true if semantic data should be processed and displayed for this page.
	 * @return bool
	 */
	function smwfIsSemanticsProcessed($namespace) {
		global $smwgNamespacesWithSemanticLinks;
		return !empty($smwgNamespacesWithSemanticLinks[$namespace]);
	}


	/**
	 * Takes a title text and turns it safely into its DBKey.
	 * This function reimplements the title normalization as done
	 * in Title.php in order to achieve conversion with less overhead.
	 */
	function smwfNormalTitleDBKey( $text ) {
		global $wgCapitalLinks;
		$text = trim($text);
		if ($wgCapitalLinks) {
			$text = ucfirst($text);
		}
		return str_replace(' ', '_', $text);
		///// The long and secure way. Use if problems occur.
		// 		$t = Title::newFromText( $text );
		// 		if ($t != NULL) {
		// 			return $t->getDBkey();
		// 		}
		// 		return $text;
	}

	/**
	 * Takes a text and turns it into a normalised version.
	 * This function reimplements the title normalization as done
	 * in Title.php in order to achieve conversion with less overhead.
	 */
	function smwfNormalTitleText( $text ) {
		global $wgCapitalLinks;
		$text = trim($text);
		if ($wgCapitalLinks) {
			$text = ucfirst($text);
		}
		return str_replace('_', ' ', $text);
		///// The long and secure way. Use if problems occur.
		// 		$t = Title::newFromText( $text );
		// 		if ($t != NULL) {
		// 			return $t->getText();
		// 		}
		// 		return $text;
	}

	/**
	 * Escapes text in a way that allows it to be used as XML
	 * content (e.g. as a string value for some property).
	 */
	function smwfXMLContentEncode($text) {
		return str_replace(array('&','<','>'), array('&amp;','&lt;','&gt;'), Sanitizer::decodeCharReferences($text));
	}

	/**
	 * Escapes text in a way that allows it to be used as XML
	 * content (e.g. as a string value for some property).
	 */
	function smwfHTMLtoUTF8($text) {
		return Sanitizer::decodeCharReferences($text);
	}

	/**
	* This method formats a float number value according to the given
	* language and precision settings, with some intelligence to
	* produce readable output. Use it whenever you get a number that
	* was not hand-formatted by a user.
	* @param $value input number
	* @param $decplaces optional positive integer, controls how many
	*                   digits after the decimal point (but not in
	*                   scientific notation)
	*/
	function smwfNumberFormat($value, $decplaces=3) {
		wfLoadExtensionMessages('SemanticMediaWiki');
		$decseparator = wfMsgForContent('smw_decseparator');
	
		// If number is a trillion or more, then switch to scientific
		// notation. If number is less than 0.0000001 (i.e. twice decplaces),
		// then switch to scientific notation. Otherwise print number
		// using number_format. This may lead to 1.200, so then use trim to
		// remove trailing zeroes.
		$doScientific = false;
		//@todo: Don't do all this magic for integers, since the formatting does not fit there
		//       correctly. E.g. one would have integers formatted as 1234e6, not as 1.234e9, right?
		//The "$value!=0" is relevant: we want to scientify numbers that are close to 0, but never 0!
		if ( ($decplaces > 0) && ($value != 0) ) {
			$absValue = abs($value);
			if ($absValue >= 1000000000) {
				$doScientific = true;
			} elseif ($absValue <= pow(10,-$decplaces)) {
				$doScientific = true;
			} elseif ($absValue < 1) {
				if ($absValue <= pow(10,-$decplaces)) {
					$doScientific = true;
				} else {
					// Increase decimal places for small numbers, e.g. .00123 should be 5 places.
					for ($i=0.1; $absValue <= $i; $i*=0.1) {
						$decplaces++;
					}
				}
			}
		}
		if ($doScientific) {
			// Should we use decimal places here?
			$value = sprintf("%1.6e", $value);
			// Make it more readable by removing trailing zeroes from n.n00e7.
			$value = preg_replace('/(\\.\\d+?)0*e/u', '${1}e', $value, 1);
			//NOTE: do not use the optional $count parameter with preg_replace. We need to
			//      remain compatible with PHP 4.something.
			if ($decseparator !== '.') {
				$value = str_replace('.', $decseparator, $value);
			}
		} else {
			// Format to some level of precision; number_format does rounding and locale formatting,
			// x and y are used temporarily since number_format supports only single characters for either
			$value = number_format($value, $decplaces, 'x', 'y');
			$value = str_replace(array('x','y'),array($decseparator, wfMsgForContent('smw_kiloseparator')),$value);

			// Make it more readable by removing ending .000 from nnn.000
			//    Assumes substr is faster than a regular expression replacement.
			$end = $decseparator . str_repeat('0', $decplaces);
			$lenEnd = strlen($end);
			if (substr($value, -$lenEnd) === $end ) {
				$value = substr($value, 0, -$lenEnd);
			} else {
				// If above replacement occurred, no need to do the next one.
				// Make it more readable by removing trailing zeroes from nn.n00.
				$value = preg_replace("/(\\$decseparator\\d+?)0*$/u", '$1', $value, 1);
			}
		}
		return $value;
	}

	/**
	 * Formats an array of message strings so that it appears as a tooltip.
	 * $icon should be one of: 'warning' (default), 'info'
	 */
	function smwfEncodeMessages($msgarray, $icon = 'warning', $sep = " <!--br-->") {
		if (count($msgarray) > 0) {
			smwfRequireHeadItem(SMW_HEADER_TOOLTIP);
			$msgs = implode($sep, $msgarray);
			return '<span class="smwttpersist"><span class="smwtticon">' . $icon . '.png</span><span class="smwttcontent">' . $msgs . '</span> </span>';
			// Note: the space is essential to make FF (and maybe other) align icons properly in tables
		} else {
			return '';
		}
	}

	/**
	 * Get a semantic storage object. Currently, it just returns one globally defined
	 * object, but the infrastructure allows to set up load balancing and task-dependent
	 * use of stores (e.g. using other stores for fast querying than for storing new facts),
	 * similar to MediaWiki's DB implementation.
	 */
	function &smwfGetStore() {
		global $smwgMasterStore, $smwgDefaultStore, $smwgIP;
		if ($smwgDefaultStore == 'SMWRAPStore') { // no autoloading for RAP store, since autoloaded classes are in rare cases loaded by MW even if not used in code -- this is not possible for RAPstore, which depends on RAP being installed
			include_once($smwgIP . '/includes/storage/SMW_RAPStore.php');
		}
		if ($smwgDefaultStore == 'SMWRAPStore2') { // no autoloading for RAP store, since autoloaded classes are in rare cases loaded by MW even if not used in code -- this is not possible for RAPstore, which depends on RAP being installed
			include_once($smwgIP . '/includes/storage/SMW_RAPStore2.php');
		}
		if ($smwgMasterStore === NULL) {
			$smwgMasterStore = new $smwgDefaultStore();
		}
		return $smwgMasterStore;
	}

