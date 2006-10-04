<?php

$smwgVersion = '0.5a';

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
define('SMW_SP_SERVICE_LINK', 13);

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
//$pathfix = $IP . $smwgScriptPath;

// load global functions
require_once($smwgIP . '/includes/SMW_GlobalFunctions.php');

// load (default) settings for inline queries first
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
$wgExtensionCredits['parserhook'][]= array('name'=>'Semantic MediaWiki', 'version'=>$smwgVersion, 'author'=>'Klaus Lassleben, Markus Kr&ouml;tzsch, Kai H&uuml;ner, Denny Vrandecic, S Page', 'url'=>'http://sourceforge.net/projects/semediawiki/', 'description' => 'Making your wiki more accessible&nbsp;&ndash; for machines and humans');

?>
