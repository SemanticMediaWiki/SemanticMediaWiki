<?php
/**
 * Global functions and constants for Semantic MediaWiki.
 */

define('SMW_VERSION','1.0-alpha-SVN');

// constants for special properties, used for datatype assignment and storage
define('SMW_SP_HAS_TYPE',1);
define('SMW_SP_HAS_URI',2);
define('SMW_SP_HAS_CATEGORY',4);
define('SMW_SP_MAIN_DISPLAY_UNIT', 6);
define('SMW_SP_DISPLAY_UNITS', 7);
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
define('SMW_FACTBOX_SPECIAL', 2);
define('SMW_FACTBOX_NONEMPTY',  3);
define('SMW_FACTBOX_SHOWN',  5);

// constants for regulating equality reasoning
define('SMW_EQ_NONE', 0);
define('SMW_EQ_SOME', 1);
define('SMW_EQ_FULL', 2);

// constants for identifying javascripts as used in smwfRequireHeadItem
define('SMW_HEADER_TIMELINE', 1);
define('SMW_HEADER_TOOLTIP', 2);
define('SMW_HEADER_SORTTABLE', 3);
define('SMW_HEADER_STYLE', 4);

// constants for denoting output modes in many functions: HTML or Wiki?
define('SMW_OUTPUT_HTML', 1);
define('SMW_OUTPUT_WIKI', 2);

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
	global $smwgNamespace, $wgExtensionFunctions, $wgSpecialPages, $wgAutoloadClasses, $smwgIP, $wgHooks;
	// The dot tells that the domain is not complete. It will be completed
	// in the Export since we do not want to create a title object here when
	// it is not needed in many cases.
	if ( !$complete && ($smwgNamespace !== '') ) {
		$smwgNamespace = '.' . $namespace;
	} else {
		$smwgNamespace = $namespace;
	}
	$wgExtensionFunctions[] = 'smwfSetupExtension';
	$wgHooks['LanguageGetMagic'][] = 'smwfParserFunctionMagic'; // setup names for parser functions (needed here)

	///// Set up autoloading
	///// All classes registered for autoloading here should be tagged with this information:
	///// Add "@note AUTOLOADED" to their class documentation. This avoids useless includes.

	// printers
	$wgAutoloadClasses['SMWResultPrinter']         = $smwgIP . '/includes/SMW_QueryPrinter.php';
	$wgAutoloadClasses['SMWTableResultPrinter']    = $smwgIP . '/includes/SMW_QP_Table.php';
	$wgAutoloadClasses['SMWListResultPrinter']     = $smwgIP . '/includes/SMW_QP_List.php';
	$wgAutoloadClasses['SMWTimelineResultPrinter'] = $smwgIP . '/includes/SMW_QP_Timeline.php';
	$wgAutoloadClasses['SMWEmbeddedResultPrinter'] = $smwgIP . '/includes/SMW_QP_Embedded.php';
	$wgAutoloadClasses['SMWTemplateResultPrinter'] = $smwgIP . '/includes/SMW_QP_Template.php';
	// datavalues
	$wgAutoloadClasses['SMWDataValue']             =  $smwgIP . '/includes/SMW_DataValue.php';
	// the builtin types are registered by SMWDataValueFactory if needed, will be reliably available
	// to other DV-implementations that register to the factory.

	///// Register specials, do that early on in case some other extension calls "addPage" /////
	$wgAutoloadClasses['SMWAskPage']          = $smwgIP . '/specials/AskSpecial/SMW_SpecialAsk.php';
	$wgSpecialPages['Ask']                    = array('SMWAskPage');
	$wgAutoloadClasses['SMWSpecialBrowse']    = $smwgIP . '/specials/SearchTriple/SMW_SpecialBrowse.php';
	$wgSpecialPages['Browse']                 = array('SMWSpecialBrowse');
	$wgAutoloadClasses['SMWPageProperty']     = $smwgIP . '/specials/SearchTriple/SMW_SpecialPageProperty.php';
	$wgSpecialPages['PageProperty']           = array('SMWPageProperty');
	$wgAutoloadClasses['SMWSearchByProperty'] = $smwgIP . '/specials/SearchTriple/SMW_SpecialSearchByProperty.php';
	$wgSpecialPages['SearchByProperty']       = array('SMWSearchByProperty');
	$wgAutoloadClasses['SMWURIResolver']      = $smwgIP . '/specials/URIResolver/SMW_SpecialURIResolver.php';
	$wgSpecialPages['URIResolver']            = array('SMWURIResolver');
	$wgAutoloadClasses['SMWAdmin']            = $smwgIP . '/specials/SMWAdmin/SMW_SpecialSMWAdmin.php';
	$wgSpecialPages['SMWAdmin']               = array('SMWAdmin');
	// suboptimal special pages using the SMWSpecialPage wrapper class:
	$wgAutoloadClasses['SMWSpecialPage']      = $smwgIP . '/includes/SMW_SpecialPage.php';
	$wgSpecialPages['Properties']             = array('SMWSpecialPage','Properties', 'smwfDoSpecialProperties', $smwgIP . '/specials/QueryPages/SMW_SpecialProperties.php');
	$wgSpecialPages['UnusedProperties']       = array('SMWSpecialPage','UnusedProperties', 'smwfDoSpecialUnusedProperties', $smwgIP . '/specials/QueryPages/SMW_SpecialUnusedProperties.php');
	$wgSpecialPages['WantedProperties']       = array('SMWSpecialPage','WantedProperties', 'smwfDoSpecialWantedProperties', $smwgIP . '/specials/QueryPages/SMW_SpecialWantedProperties.php');
	$wgSpecialPages['ExportRDF']              = array('SMWSpecialPage','ExportRDF', 'smwfDoSpecialExportRDF', $smwgIP . '/specials/ExportRDF/SMW_SpecialExportRDF.php');
	$wgSpecialPages['SemanticStatistics']     = array('SMWSpecialPage','SemanticStatistics', 'smwfExecuteSemanticStatistics', $smwgIP . '/specials/Statistics/SMW_SpecialStatistics.php');
	$wgSpecialPages['Types']                  = array('SMWSpecialPage','Types', 'smwfDoSpecialTypes', $smwgIP . '/specials/QueryPages/SMW_SpecialTypes.php');

	return true;
}

/**
 *  Do the actual intialisation of the extension. This is just a delayed init that makes sure
 *  MediaWiki is set up properly before we add our stuff.
 */
function smwfSetupExtension() {
	wfProfileIn('smwfSetupExtension (SMW)');
	global $smwgIP, $smwgStoreActive, $wgHooks, $wgExtensionCredits, $smwgEnableTemplateSupport, $smwgMasterStore, $smwgIQRunningNumber;

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
	smwfInitContentMessages(); // this really could not be done in enableSemantics()
	$smwgIQRunningNumber = 0;

	///// register hooks /////
	require_once($smwgIP . '/includes/SMW_Hooks.php');
	require_once($smwgIP . '/includes/SMW_RefreshTab.php');

	$wgHooks['InternalParseBeforeLinks'][] = 'smwfParserHook'; // parse annotations
	$wgHooks['ParserBeforeStrip'][] = 'smwfRegisterInlineQueries'; // register the <ask> parser hook
	$wgHooks['ArticleSave'][] = 'smwfPreSaveHook'; // check some settings here
	$wgHooks['ArticleSaveComplete'][] = 'smwfSaveHook'; // store annotations
	$wgHooks['ArticleUndelete'][] = 'smwfUndeleteHook'; // restore annotations
	$wgHooks['ArticleDelete'][] = 'smwfDeleteHook'; // delete annotations
	$wgHooks['TitleMoveComplete'][]='smwfMoveHook'; // move annotations
	$wgHooks['ParserAfterTidy'][] = 'smwfAddHTMLHeadersParser'; // add items to HTML header during parsing
	$wgHooks['BeforePageDisplay'][]='smwfAddHTMLHeadersOutput'; // add items to HTML header during output

	$wgHooks['ArticleFromTitle'][] = 'smwfShowListPage'; // special implementations for property/type articles
	$wgHooks['LoadAllMessages'][] = 'smwfLoadAllMessages'; // complete setup of all messages when requested by MW

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
	$parser->setFunctionHook( 'ask', 'smwfProcessInlineQueryParserFunction' );
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
 * The <ask> parser hook processing part.
 */
function smwfProcessInlineQuery($querytext, $params, &$parser) {
	global $smwgQEnabled, $smwgIP, $smwgIQRunningNumber;
	if ($smwgQEnabled) {
		$smwgIQRunningNumber++;
		require_once($smwgIP . '/includes/SMW_QueryProcessor.php');
		return SMWQueryProcessor::getResultFromHookParams($querytext,$params,SMW_OUTPUT_HTML);
	} else {
		return smwfEncodeMessages(array(wfMsgForContent('smw_iq_disabled')));
	}
}

/**
 * The {{#ask }} parser function processing part.
 */
function smwfProcessInlineQueryParserFunction(&$parser) {
	global $smwgQEnabled, $smwgIP, $smwgIQRunningNumber;
	if ($smwgQEnabled) {
		$smwgIQRunningNumber++;
		require_once($smwgIP . '/includes/SMW_QueryProcessor.php');
		$params = func_get_args();
		array_shift( $params ); // we already know the $parser ...
		return SMWQueryProcessor::getResultFromFunctionParams($params,SMW_OUTPUT_WIKI);
	} else {
		return smwfEncodeMessages(array(wfMsgForContent('smw_iq_disabled')));
	}
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
 * Hook function to insert HTML headers (CSS, JavaScript, and meta tags) into parser 
 * output. This is our preferred method of working off the required scripts, since it 
 * exploits parser caching.
 */
function smwfAddHTMLHeadersParser(&$parser, &$text) {
	global $smwgHeadItems, $smwgStoreActive;
	if (!$smwgStoreActive) return true; // avoid doing this in SMW-generated sub-parsers
	foreach ($smwgHeadItems as $key => $item) {
		$parser->mOutput->addHeadItem("\t\t" . $item . "\n", $key);
	}
	$smwgHeadItems = array(); // flush array so that smwfAddHTMLHeader does not take needless actions
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
		global $smwgNamespaceIndex, $wgExtraNamespaces, $wgNamespaceAliases, $wgNamespacesWithSubpages, $wgLanguageCode, $smwgContLang;

		if (!isset($smwgNamespaceIndex)) {
			$smwgNamespaceIndex = 100;
		}

		define('SMW_NS_PROPERTY',       $smwgNamespaceIndex+2);
		define('SMW_NS_PROPERTY_TALK',  $smwgNamespaceIndex+3);
		define('SMW_NS_TYPE',           $smwgNamespaceIndex+4);
		define('SMW_NS_TYPE_TALK',      $smwgNamespaceIndex+5);

		/// For backwards compatibility. Might vanish at some point.
		define('SMW_NS_RELATION',       $smwgNamespaceIndex);
		define('SMW_NS_RELATION_TALK',  $smwgNamespaceIndex+1);

		smwfInitContentLanguage($wgLanguageCode);

		// Register namespace identifiers
		if (!is_array($wgExtraNamespaces)) { $wgExtraNamespaces=array(); }
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
	 */
	function smwfParserFunctionMagic(&$magicWords, $langCode) {
		$magicWords['ask'] = array( 0, 'ask' );
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
		$decseparator = wfMsgForContent('smw_decseparator');
		$kiloseparator = wfMsgForContent('smw_kiloseparator');
	
		// If number is a trillion or more, then switch to scientific
		// notation. If number is less than 0.0000001 (i.e. twice decplaces),
		// then switch to scientific notation. Otherwise print number
		// using number_format. This may lead to 1.200, so then use trim to
		// remove trailing zeroes.
		$doScientific = false;
		//@TODO: Don't do all this magic for integers, since the formatting does not fit there
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
			$value = preg_replace('/(\\.\\d+?)0*e/', '${1}e', $value, 1);
			//NOTE: do not use the optional $count parameter with preg_replace. We need to
			//      remain compatible with PHP 4.something.
			if ($decseparator !== '.') {
				$value = str_replace('.', $decseparator, $value);
			}
		} else {
			// Format to some level of precision;
			// this does rounding and locale formatting.
			$value = number_format($value, $decplaces, $decseparator, wfMsgForContent('smw_kiloseparator'));
	
			// Make it more readable by removing ending .000 from nnn.000
			//    Assumes substr is faster than a regular expression replacement.
			$end = $decseparator . str_repeat('0', $decplaces);
			$lenEnd = strlen($end);
			if (substr($value, -$lenEnd) === $end ) {
				$value = substr($value, 0, -$lenEnd);
			} else {
				// If above replacement occurred, no need to do the next one.
				// Make it more readable by removing trailing zeroes from nn.n00.
				$value = preg_replace("/(\\$decseparator\\d+?)0*$/", '$1', $value, 1);
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
		global $smwgMasterStore;
		if ($smwgMasterStore === NULL) {
			// initialise main storage (there is no other store at the moment)
			global $smwgDefaultStore, $smwgIP;
			switch ($smwgDefaultStore) {
				case (SMW_STORE_TESTING):
					require_once($smwgIP . '/includes/storage/SMW_TestStore.php');
					$smwgMasterStore = new SMWTestStore();
				break;
				case (SMW_STORE_RAP):
					require_once($smwgIP . '/includes/storage/SMW_RAPStore.php');
					$smwgMasterStore = new SMWRAPStore();
				break;
				case (SMW_STORE_MWDB): default:
					require_once($smwgIP . '/includes/storage/SMW_SQLStore.php');
					$smwgMasterStore = new SMWSQLStore();
				break;
			}
		}
		return $smwgMasterStore;
	}

