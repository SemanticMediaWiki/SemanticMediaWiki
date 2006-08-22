<?php

$smwgVersion = '0.5';

// constants for special properties, used for datatype assignment and storage
define('SMW_SP_HAS_TYPE',1);
define('SMW_SP_HAS_URI',2);
define('SMW_SP_HAS_CATEGORY',4); 
define('SMW_SP_IS_SUBRELATION_OF',3);
define('SMW_SP_IS_SUBATTRIBUTE_OF',5);
define('SMW_SP_MAIN_DISPLAY_UNIT', 6);
define('SMW_SP_DISPLAY_UNIT', 7);
define('SMW_SP_IMPORTED_FROM',8);
define('SMW_SP_EXT_BASEURI',9);
define('SMW_SP_EXT_NSID',10);
define('SMW_SP_EXT_SECTION',11);
define('SMW_SP_CONVERSION_FACTOR', 12);

// constants for displaying the factbox
define('SMW_FACTBOX_HIDDEN', 1);
define('SMW_FACTBOX_NONEMPTY',  3);
define('SMW_FACTBOX_SHOWN',  5);
//default:
$smwgShowFactbox = SMW_FACTBOX_NONEMPTY;

// some default settings which usually need no modification

###
# This is the path to your installation of Semantic MediaWiki as
# seen from the web. Change it if required ($wgScriptPath is the
# path to the base directory of your wiki). No final slash.
##
$smwgScriptPath = $wgScriptPath . '/extensions/SemanticMediaWiki';
##

###
# This is the path to your installation of Semantic MediaWiki as
# seen on your local filesystem. Used against some PHP file path
# issues.
##
$smwgIP = $IP . '/extensions/SemanticMediaWiki';
##


// PHP fails to find relative includes at some level of inclusion:
$pathfix = $IP . $smwgScriptPath;

// load settings for inline queries first
require_once($smwgIP . '/includes/SMW_InlineQueries.php');

// get local configuration ...
require("SMW_LocalSettings.php");


/**********************************************/
/***** register specials                  *****/
/**********************************************/

//require_once($smwgIP . '/specials/SearchSemantic/SMW_SpecialSearchSemantic.php'); //really not longer functional!
require_once($smwgIP . '/specials/SearchTriple/SMW_SpecialSearchTriple.php');
require_once($smwgIP . '/specials/ExportRDF/SMW_SpecialExportRDF.php'); // coming soon
require_once($smwgIP . '/specials/SMWAdmin/SMW_SpecialSMWAdmin.php');
require_once($smwgIP . '/specials/OntologyImport/SMW_SpecialOntologyImport.php');

require_once($smwgIP . '/specials/Relations/SMW_SpecialRelations.php');
require_once($smwgIP . '/specials/Relations/SMW_SpecialUnusedRelations.php');
require_once($smwgIP . '/specials/Relations/SMW_SpecialAttributes.php');
require_once($smwgIP . '/specials/Relations/SMW_SpecialUnusedAttributes.php');
require_once($smwgIP . '/specials/Relations/SMW_SpecialTypes.php');

/**********************************************/
/***** register hooks                     *****/
/**********************************************/

require_once($smwgIP . '/includes/SMW_Hooks.php');
require_once($smwgIP . '/includes/SMW_RefreshTab.php');

if ($smwgEnableTemplateSupport===true) {
	$wgHooks['InternalParseBeforeLinks'][] = 'smwfParserHook'; //patch required;
} else {
	$wgHooks['ParserAfterStrip'][] = 'smwfParserHook'; //default setting
}

$wgHooks['ParserAfterTidy'][] = 'smwfParserAfterTidyHook';
$wgHooks['ArticleSaveComplete'][] = 'smwfSaveHook';
$wgHooks['ArticleDelete'][] = 'smwfDeleteHook';
$wgHooks['TitleMoveComplete'][]='smwfMoveHook';
$wgHooks['BeforePageDisplay'][]='smwfAddHTMLHeader';

/**********************************************/
/***** credits (see "Special:Version")    *****/
/**********************************************/

global $wgExtensionCredits;
$wgExtensionCredits['parserhook'][]= array('name'=>'Semantic MediaWiki', 'version'=>$smwgVersion, 'author'=>'Klaus Lassleben, Markus Kr&ouml;tzsch, Kai H&uuml;ner, Denny Vrandecic, S Page', 'url'=>'https://sourceforge.net/projects/semediawiki/', 'description' => 'Making your wiki more accessible&nbsp;&ndash; for machines and humans');

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
		global $smwgHeadersInPlace; // record whether headers were created already
		global $smwgArticleHeadersInPlace; // record whether article name specific headers are already there
		global $smwgScriptPath;

		if (!$smwgHeadersInPlace) {
			$toolTipScript = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SMW_tooltip.js"></script>';
			$out->addScript($toolTipScript);
			$sortTableScript = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SMW_sorttable.js"></script>';
			$out->addScript($sortTableScript);

			// Also we add a custom CSS file for our needs
			$customCssUrl = $smwgScriptPath . '/skins/SMW_custom.css';
			$out->addLink(array(
				'rel'   => 'stylesheet',
				'type'  => 'text/css',
				'media' => 'screen, projection',
				'href'  => $customCssUrl
			));
			$smwgHeadersInPlace = true;
		}

		if ((!$smwgArticleHeadersInPlace) && ($out->mIsarticle) && ($out->mPagetitle!='')) {
			//print_r('Article ADDHTML... "'. $out->mPagetitle .'"');
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


		return;
	}

/**********************************************/
/***** namespace settings                 *****/
/**********************************************/

	/**
	 * Init the additional namepsaces used by Semantic MediaWiki. The
	 * parameter denotes the least unused even namespace ID that is
	 * greater or equal to 100.
	 */
	function smwfInitNamespaces($base_idx) {
		global $wgExtraNamespaces, $wgNamespacesWithSubpages, $wgLanguageCode, $smwgContLang;

		smwfInitContentLanguage($wgLanguageCode);

		$namespaceIndex=$base_idx;

		define('SMW_NS_RELATION',       $namespaceIndex);
		define('SMW_NS_RELATION_TALK',  $namespaceIndex+1);
		define('SMW_NS_ATTRIBUTE',      $namespaceIndex+2);
		define('SMW_NS_ATTRIBUTE_TALK', $namespaceIndex+3);
		define('SMW_NS_TYPE',           $namespaceIndex+4);
		define('SMW_NS_TYPE_TALK',      $namespaceIndex+5);

		// Register namespace identifiers
		if (!is_array($wgExtraNamespaces)) { $wgExtraNamespaces=array(); }
		$wgExtraNamespaces = $wgExtraNamespaces +
							 $smwgContLang->getNamespaceArray();

		// Support subpages only for talk pages by default
		$wgNamespacesWithSubpages = $wgNamespacesWithSubpages + array(
			      SMW_NS_RELATION_TALK => true,
			      SMW_NS_ATTRIBUTE_TALK => true,
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
	}

	/**
	 * Initialise the global language object for user language. This
	 * must happen after the content language was initialised, since
	 * this language is used as a fallback.
	 */
	function smwfInitUserLanguage($langcode) {
		global $smwgIP, $smwgLang;

		if (!empty($smwgLang)) { return; }

		$smwLangClass = 'SMW_Language' . str_replace( '-', '_', ucfirst( $langcode ) );

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
	}

	/**
	 * Initialise messages. These settings must be applied later on, since
	 * the MessageCache does not exist yet when the settings are loaded in
	 * LocalSettings.php.
	 */
	function smwfInitMessages() {
		global $smwgMessagesInPlace; // record whether the function was already called

		if ($smwgMessagesInPlace) { return; }

		global $wgMessageCache, $smwgContLang, $smwgLang;
		global $wgContLanguageCode, $wgLanguageCode;
		// make sure that language objects exist
		smwfInitContentLanguage($wgContLanguageCode);
		smwfInitUserLanguage($wgLanguageCode);

		$wgMessageCache->addMessages($smwgContLang->getContentMsgArray());
		$wgMessageCache->addMessages($smwgLang->getUserMsgArray());

		$smwgMessagesInPlace = true;
	}


?>
