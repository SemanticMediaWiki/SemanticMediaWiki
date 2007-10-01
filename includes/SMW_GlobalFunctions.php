<?php
/**
 * Global functions and constants for Semantic MediaWiki.
 */

define('SMW_VERSION','1.0prealpha-4');

// constants for special properties, used for datatype assignment and storage
define('SMW_SP_HAS_TYPE',1);
define('SMW_SP_HAS_URI',2);
define('SMW_SP_HAS_CATEGORY',4);
define('SMW_SP_MAIN_DISPLAY_UNIT', 6);
define('SMW_SP_DISPLAY_UNIT', 7);
define('SMW_SP_IMPORTED_FROM',8);
define('SMW_SP_EXT_BASEURI',9);
define('SMW_SP_EXT_NSID',10);
define('SMW_SP_EXT_SECTION',11);
define('SMW_SP_CONVERSION_FACTOR', 12);
define('SMW_SP_SERVICE_LINK', 13);
define('SMW_SP_POSSIBLE_VALUE', 14);
define('SMW_SP_REDIRECTS_TO', 15);
define('SMW_SP_SUBPROPERTY_OF',17);

// constants for displaying the factbox
define('SMW_FACTBOX_HIDDEN', 1);
define('SMW_FACTBOX_NONEMPTY',  3);
define('SMW_FACTBOX_SHOWN',  5);

/**
 * Switch on Semantic MediaWiki. This function must be called in LocalSettings.php
 * after incldung this file. It is used to ensure that required parameters for SMW
 * are really provided, without requiring the existence of a dedicated file
 * SMW_LocalSettings.php. For readability, this is the only global function that
 * does not adhere to the naming conventions.
 */
function enableSemantics($namespace = "", $complete = false) {
	global $smwgNamespace, $wgExtensionFunctions, $wgHooks;
	$smwgNamespace = $namespace;

	// The dot tells that the domain is not complete. It will be completed
	// in the Export since we do not want to create a title object here when
	// it is not needed in many cases.
	if (!$complete && !($smwgNamespace === '')) $smwgNamespace = ".$smwgNamespace";
	$wgExtensionFunctions[] = 'smwfSetupExtension';
	return true;
}

/**
 *  Do the actual intialisation of the extension. This is just a delayed init that makes sure
 *  MediaWiki is set up properly before we add our stuff.
 */
function smwfSetupExtension() {
	wfProfileIn('smwfSetupExtension (SMW)');
	global $smwgIP, $smwgStoreActive, $wgHooks, $wgExtensionCredits, $smwgEnableTemplateSupport, $smwgMasterStore, $wgSpecialPages, $wgAutoloadClasses;

	/**
	* Setting this to false prevents any new data from being stored in
	* the static SMWSemanticData store, and disables printing of the
	* factbox, and clearing of the existing data.
	* This is a hack to enable parsing of included articles in a save
	* way without importing their annotations. Unfortunatelly, there
	* appears to be no way for finding out whether the current parse
	* is the "main" parse, or whether some intro, docu, or whatever
	* text is parsed. Using the hook mechanism, we have to rely on
	* globals/static fields -- so we cannot somehow differentiate this
	* store between parsers.
	*/
	$smwgStoreActive = true;

	$smwgMasterStore = NULL;
	smwfInitContentMessages();
	
	///// setup some autoloading /////
	$wgAutoloadClasses[SMWResultPrinter]         = $smwgIP . '/includes/SMW_QueryPrinter.php';
	$wgAutoloadClasses[SMWTableResultPrinter]    = $smwgIP . '/includes/SMW_QP_Table.php';
	$wgAutoloadClasses[SMWListResultPrinter]     = $smwgIP . '/includes/SMW_QP_List.php';
	$wgAutoloadClasses[SMWTimelineResultPrinter] = $smwgIP . '/includes/SMW_QP_Timeline.php';
	$wgAutoloadClasses[SMWEmbeddedResultPrinter] = $smwgIP . '/includes/SMW_QP_Embedded.php';
	$wgAutoloadClasses[SMWTemplateResultPrinter] = $smwgIP . '/includes/SMW_QP_Template.php';

	///// register specials /////
	$wgAutoloadClasses['SMWAskPage'] = $smwgIP . '/specials/AskSpecial/SMW_SpecialAsk.php';
	$wgSpecialPages['Ask'] = array('SMWAskPage');
	$wgAutoloadClasses['SMWSpecialBrowse'] = $smwgIP . '/specials/SearchTriple/SMW_SpecialBrowse.php';
	$wgSpecialPages['Browse'] = array('SMWSpecialBrowse');
	$wgAutoloadClasses['SMWPageProperty'] = $smwgIP . '/specials/SearchTriple/SMW_SpecialPageProperty.php';
	$wgSpecialPages['PageProperty'] = array('SMWPageProperty');
	$wgAutoloadClasses['SMWSearchByProperty'] = $smwgIP . '/specials/SearchTriple/SMW_SpecialSearchByProperty.php';
	$wgSpecialPages['SearchByProperty'] = array('SMWSearchByProperty');
	$wgAutoloadClasses['SMWURIResolver'] = $smwgIP . '/specials/URIResolver/SMW_SpecialURIResolver.php';
	$wgSpecialPages['URIResolver'] = array('SMWURIResolver');
	$wgAutoloadClasses['SMWAdmin'] = $smwgIP . '/specials/SMWAdmin/SMW_SpecialSMWAdmin.php';
	$wgSpecialPages['SMWAdmin'] = array('SMWAdmin');

	$wgAutoloadClasses['SMWSpecialPage'] = $smwgIP . '/includes/SMW_SpecialPage.php';
	$wgSpecialPages['Properties'] = array('SMWSpecialPage','Properties', 'smwfDoSpecialProperties', $smwgIP . '/specials/QueryPages/SMW_SpecialProperties.php');
	$wgSpecialPages['UnusedProperties'] = array('SMWSpecialPage','UnusedProperties', 'smwfDoSpecialUnusedProperties', $smwgIP . '/specials/QueryPages/SMW_SpecialUnusedProperties.php');
	$wgSpecialPages['WantedProperties'] = array('SMWSpecialPage','WantedProperties', 'smwfDoSpecialWantedProperties', $smwgIP . '/specials/QueryPages/SMW_SpecialWantedProperties.php');
	$wgSpecialPages['ExportRDF'] = array('SMWSpecialPage','ExportRDF', 'smwfDoSpecialExportRDF', $smwgIP . '/specials/ExportRDF/SMW_SpecialExportRDF.php');
	$wgSpecialPages['SemanticStatistics'] = array('SMWSpecialPage','SemanticStatistics', 'smwfExecuteSemanticStatistics', $smwgIP . '/specials/Statistics/SMW_SpecialStatistics.php');
	$wgSpecialPages['Types'] = array('SMWSpecialPage','Types', 'smwfDoSpecialTypes', $smwgIP . '/specials/QueryPages/SMW_SpecialTypes.php');

	//require_once($smwgIP . '/specials/OntologyImport/SMW_SpecialOntologyImport.php'); // broken, TODO: fix or delete

	///// register hooks /////
	require_once($smwgIP . '/includes/SMW_Hooks.php');
	require_once($smwgIP . '/includes/SMW_RefreshTab.php');

	if ($smwgEnableTemplateSupport===true) {
		$wgHooks['InternalParseBeforeLinks'][] = 'smwfParserHook'; //patch required;
	} else {
		$wgHooks['ParserAfterStrip'][] = 'smwfParserHook'; //default setting
	}

	$wgHooks['ArticleSaveComplete'][] = 'smwfSaveHook';
	$wgHooks['ArticleDelete'][] = 'smwfDeleteHook';
	$wgHooks['TitleMoveComplete'][]='smwfMoveHook';
	$wgHooks['BeforePageDisplay'][]='smwfAddHTMLHeader';
	$wgHooks['ParserBeforeStrip'][] = 'smwfRegisterInlineQueries'; // a hook for registering the <ask> parser hook
	$wgHooks['ArticleFromTitle'][] = 'smwfShowListPage';
	$wgHooks['LoadAllMessages'][] = 'smwfLoadAllMessages'; // enable a complete display of all messages when requested by MW

	///// credits (see "Special:Version") /////
	$wgExtensionCredits['parserhook'][]= array('name'=>'Semantic&nbsp;MediaWiki', 'version'=>SMW_VERSION, 'author'=>"Klaus&nbsp;Lassleben, Markus&nbsp;Kr&ouml;tzsch, Denny&nbsp;Vrandecic, S&nbsp;Page, and others. Maintained by [http://www.aifb.uni-karlsruhe.de/Forschungsgruppen/WBS/english AIFB Karlsruhe].", 'url'=>'http://ontoworld.org/wiki/Semantic_MediaWiki', 'description' => 'Making your wiki more accessible&nbsp;&ndash; for machines \'\'and\'\' humans. [http://ontoworld.org/wiki/Help:Semantics View online documentation.]');

	wfProfileOut('smwfSetupExtension (SMW)');
	return true;
}

/**
 * This hook registers a parser-hook to the current parser.
 * Note that parser hooks are something different than MW hooks
 * in general, which explains the two-level registration.
 */
function smwfRegisterInlineQueries( &$parser, &$text, &$stripstate ) {
	$parser->setHook( 'ask', 'smwfProcessInlineQuery' );
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
 * The <ask> parser hook processing part.
 */
function smwfProcessInlineQuery($text, $param) {
	global $smwgQEnabled, $smwgIP;
	if ($smwgQEnabled) {
		require_once($smwgIP . '/includes/SMW_QueryProcessor.php');
		return SMWQueryProcessor::getResultHTML($text,$param);
	} else {
		return smwfEncodeMessages(array(wfMsgForContent('smw_iq_disabled')));
	}
}

/**********************************************/
/***** Header modifications               *****/
/**********************************************/

	/**
	*  This method is in charge of inserting additional CSS, JScript, and meta tags
	*  into the html header of each page. It is either called after initialising wgout
	*  (requiring a patch in MediaWiki), or during parsing. Calling it during parsing,
	*  however, is not sufficient to get the header modifiactions into every page that
	*  is shipped to a reader, since the parser cache can make parsing obsolete.
	*
	*  $out is the modified OutputPage.
	*/
	function smwfAddHTMLHeader(&$out) {
		wfProfileIn('smwfAddHTMLHeader (SMW)');
		global $smwgHeadersInPlace; // record whether headers were created already
		global $smwgArticleHeadersInPlace; // record whether article name specific headers are already there
		global $smwgScriptPath;

		if (!$smwgHeadersInPlace) {
			$sortTableScript = '<script type="text/javascript" id="SMW_sorttable_script_inclusion" src="' . $smwgScriptPath .  '/skins/SMW_sorttable.js"></script>';
			// The above id is essential for the JavaScript to find out the $smwgScriptPath to
			// include images. Changes in the above must always be coordinated with the script!
			$out->addScript($sortTableScript);

			$toolTipScript = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SMW_tooltip.js"></script>';
			$out->addScript($toolTipScript);

			// TODO: we should rather have a script that only pulls the whole Timeline on demand, if possible
			$TimelineScript = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SimileTimeline/timeline-api.js"></script>';
			$SMWTimelineScript = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SMW_timeline.js"></script>';
			$out->addScript($TimelineScript);
			$out->addScript($SMWTimelineScript);

			// Also we add a custom CSS file for our needs
			$customCssUrl = $smwgScriptPath . '/skins/SMW_custom.css';
			$out->addLink(array(
				'rel'   => 'stylesheet',
				'type'  => 'text/css',
				'media' => 'screen, projection',
				'href'  => $customCssUrl
			));

			//BEGIN RTL PATCH
			global $wgContLang;
			if ($wgContLang->isRTL()) {
				$customCssUrl = $smwgScriptPath . '/skins/SMW_custom_rtl.css';
				$out->addLink(array(
					'rel'   => 'stylesheet',
					'type'  => 'text/css',
					'media' => 'screen, projection',
					'href'  => $customCssUrl
				));
			}
			//END RTL PATCH
			$smwgHeadersInPlace = true;
		}

		if ((!$smwgArticleHeadersInPlace) && ($out->mIsarticle) && ($out->mPagetitle!='')) {
			global $wgContLang, $wgServer, $wgScript;

			$out->addLink(array(
				'rel'   => 'alternate',
				'type'  => 'application/rdf+xml',
				'title' => $out->mPagetitle,
				'href'  => $wgServer . $wgScript . '/' .
				           $wgContLang->getNsText(NS_SPECIAL) . ':ExportRDF/' .
				           str_replace('%2F', "/", urlencode(str_replace(' ', '_', $out->mPagetitle))) . '?xmlmime=rdf'
			));
			$smwgArticleHeadersInPlace = true;
		}
		wfProfileOut('smwfAddHTMLHeader (SMW)');

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
		global $smwgNamespaceIndex, $wgExtraNamespaces, $wgNamespacesWithSubpages, $wgLanguageCode, $smwgContLang;

		if (!isset($smwgNamespaceIndex)) {
			$smwgNamespaceIndex = 100;
		}

		smwfInitContentLanguage($wgLanguageCode);

		define('SMW_NS_PROPERTY',       $smwgNamespaceIndex+2);
		define('SMW_NS_PROPERTY_TALK',  $smwgNamespaceIndex+3);
		define('SMW_NS_TYPE',           $smwgNamespaceIndex+4);
		define('SMW_NS_TYPE_TALK',      $smwgNamespaceIndex+5);

		/// @DEPRECATED
		define('SMW_NS_ATTRIBUTE',      $smwgNamespaceIndex+2);
		define('SMW_NS_ATTRIBUTE_TALK', $smwgNamespaceIndex+3);
		define('SMW_NS_RELATION',       $smwgNamespaceIndex);
		define('SMW_NS_RELATION_TALK',  $smwgNamespaceIndex+1);

		// Register namespace identifiers
		if (!is_array($wgExtraNamespaces)) { $wgExtraNamespaces=array(); }
		$wgExtraNamespaces = $wgExtraNamespaces + $smwgContLang->getNamespaceArray();

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
	 * Initialise a global language object for content language. This
	 * must happen early on, even before user language is known, to
	 * determine labels for additional namespaces. In contrast, messages
	 * can be initialised much later when they are actually needed.
	 */
	function smwfInitContentLanguage($langcode) {
		global $smwgIP, $smwgContLang;
		if (!empty($smwgContLang)) { return; }
		wfProfileIn('smwfInitContentLanguage (SMW)');

		$smwContLangClass = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );

		if (file_exists($smwgIP . '/languages/'. $smwContLangClass . '.php')) {
			include_once( $smwgIP . '/languages/'. $smwContLangClass . '.php' );
		}

		// fallback if language not supported
		if ( !class_exists($smwContLangClass)) {
			include_once($smwgIP . '/languages/SMW_LanguageEn.php');
			$smwContLangClass = 'SMW_LanguageEn';
		}
		$smwgContLang = new $smwContLangClass();

		wfProfileOut('smwfInitContentLanguage (SMW)');
	}

	/**
	 * Set up the content messages.
	 */
	function smwfInitContentMessages() {
		global $smwgContMessagesInPlace;
		if ($smwgContMessagesInPlace) { return; }
		wfProfileIn('smwfInitContentMessages (SMW)');
		global $wgMessageCache, $smwgContLang, $wgLanguageCode;
		smwfInitContentLanguage($wgLanguageCode);

		$wgMessageCache->addMessages($smwgContLang->getContentMsgArray(), $wgLanguageCode);
		$smwgContMessagesInPlace = true;
		wfProfileOut('smwfInitContentMessages (SMW)');
	}

	/**
	 * Initialise the global language object and messages for user language. This
	 * must happen after the content language was initialised, since
	 * this language is used as a fallback.
	 */
	function smwfInitUserMessages() {
		global $smwgIP, $smwgLang;
		if (!empty($smwgLang)) { return; }
		wfProfileIn('smwfInitUserMessages (SMW)');
		global $wgMessageCache, $wgLang;

		$smwLangClass = 'SMW_Language' . str_replace( '-', '_', ucfirst( $wgLang->getCode() ) );

		if (file_exists($smwgIP . '/languages/'. $smwLangClass . '.php')) {
			include_once( $smwgIP . '/languages/'. $smwLangClass . '.php' );
		}
		// fallback if language not supported
		if ( !class_exists($smwLangClass)) {
			global $smwgContLang;
			$smwgLang = $smwgContLang;
		} else {
			$smwgLang = new $smwLangClass();
		}

		$wgMessageCache->addMessages($smwgLang->getUserMsgArray(), $wgLang->getCode());
		wfProfileOut('smwfInitUserMessages (SMW)');
	}

	/**
	* Set up all messages if requested explicitly by MediaWiki.
	* $pagelist was used in earlier MW versions and is kept for compatibility.
	*/
	function smwfLoadAllMessages($pagelist = NULL) {
		smwfInitContentMessages();
		smwfInitUserMessages();
		return true;
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
		return str_replace(' ', '_', ucfirst($text));
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
		return str_replace('_', ' ', ucfirst($text));
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
		global $IP;
		include_once($IP . '/includes/Sanitizer.php');
		return str_replace(array('&','<','>'), array('&amp;','&lt;','&gt;'), Sanitizer::decodeCharReferences($text));
	}

	/**
	 * Formats an array of message strings so that it appears as a tooltip.
	 */
	function smwfEncodeMessages($msgarray) {
		if (count($msgarray) > 0) {
			$msgs = implode(' ', $msgarray);
			return '<span class="smwttpersist"><span class="smwtticon">warning.png</span><span class="smwttcontent">' . $msgs . '</span></span>';
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
		global $smwgMasterStore;
		if ($smwgMasterStore === NULL) {
			// initialise main storage (there is no other store at the moment)
			global $smwgDefaultStore, $smwgIP;
			switch ($smwgDefaultStore) {
				case (SMW_STORE_TESTING):
					require_once($smwgIP . '/includes/storage/SMW_TestStore.php');
					$smwgMasterStore = new SMWTestStore();
				break;
				case (SMW_STORE_MWDB): default:
					require_once($smwgIP . '/includes/storage/SMW_SQLStore.php');
					$smwgMasterStore = new SMWSQLStore();
				break;
			}
		}
		return $smwgMasterStore;
	}

