<?php

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

// load global functions
require_once('SMW_GlobalFunctions.php');

###
# If you already have custom namespaces on your site, insert
# $smwgNamespaceIndex = ???;
# into your LocalSettings.php *before* including this file.
# The number ??? must be the smallest even namespace number
# that is not in use yet. However, it must not be smaller
# than 100.
##
if (!isset($smwgNamespaceIndex)) {
	smwfInitNamespaces(100);
} else {
	smwfInitNamespaces();
}

###
# This setting allows you to select in which cases you want to have a factbox
# appear below an article. The default setting is "SMW_FACTBOX_NONEMPTY"
# which shows only those factboxes that have some content. Other options:
##
$smwgShowFactbox = SMW_FACTBOX_NONEMPTY;
//$smwgShowFactbox = SMW_FACTBOX_SPECIAL # show only if special properties were set
//$smwgShowFactbox = SMW_FACTBOX_HIDDEN; # hide always
//$smwgShowFactbox = SMW_FACTBOX_SHOWN; # show always, buggy and not recommended
##

###
# Same as $smwgShowFactbox but for edit mode and same possible values.
##
$smwgShowFactboxEdit = SMW_FACTBOX_NONEMPTY;
##

###
# Should warnings be displayed in wikitexts right after the problematic
# input? This currently affects only semantic annotations, not warnings
# that are displayed by inline queries.
##
$smwgInlineErrors = true;
##

###
# Number results shown in the listings on pages of properties (attributes or
# relations) and types.
##
$smwgTypePagingLimit = 200; // same as for categories
$smwgPropertyPagingLimit = 25; // use smaller value since property lists are much longer
##

###
# Settings for inline queries (<ask>) and for semantic queries in general.
# Especially meant to prevent overly high server-load by complex queries.
##
$smwgQEnabled = true;         // (De)activates all query related features and interfaces
$smwgQMaxSize = 12;           // Maximal number of conditions in queries, use format="debug" for example sizes
$smwgQMaxDepth = 4;           // Maximal property depth of queries, e.g. [[rel::<q>[[rel2::Test]]</q>]] has depth 2
$smwgQSubcategoryDepth = 10;  // Restrict level of sub-category inclusion (steps within category hierarchy)
$smwgQSubpropertyDepth = 10;  // Restrict level of sub-property inclusion (steps within property hierarchy)
                              // (Use 0 to disable hierarchy-inferencing in queries)
$smwgQEqualitySupport = SMW_EQ_SOME; // Evaluate #redirects as equality between page names in simple cases
  //$smwgQEqualitySupport = SMW_EQ_FULL; // Evaluate #redirects as equality between page names in all cases
  //$smwgQEqualitySupport = SMW_EQ_NONE; // Never evaluate #redirects as equality between page names
$smwgQSortingSupport  = true; // (De)activate sorting of results.
$smwgQDefaultNamespaces = NULL; // Which namespaces should be searched by default?
                              // (value NULL switches off default restrictions on searching -- this is faster)
                              // Example with namespaces: $smwgQDefaultNamespaces = array(NS_MAIN, NS_IMAGE);
$smwgQMaxLimit = 10000;       // Max number of results ever retrieved, even when using special query pages.
$smwgQDisjunctionSupport = true; // Support disjunctions in queries (||)?
                             // (Note: things like namespace defaults and property/category hierarchies
                             //        can also cause disjunctions!)
$smwgQComparators = '<|>|!'; // List of comparator characters supported by queries, separated by '|'
                             // Available entries: < (smaller than), < (greater than), ! (unequal to),
                             //                    % (pattern with '%' as wildcard, only for Type:String)
                             // If unsupported comparators are used, they are treated as part of the queried value

### Settings about printout of (especially inline) queries:
$smwgQDefaultLimit = 50;    // Default number of rows returned in a query. Can be increased with <ask limit="num">...
$smwgQMaxInlineLimit = 500; // Max number of rows ever printed in a single inline query on a single page.
$smwgQPrintoutLimit = 10;   // Max number of supported printouts (added columns in result table, * statements)

### Formatting settings
$smwgQDefaultLinking = 'subject'; // Default linking behaviour. Can be one of "none", "subject", "all"

###
# Settings for RSS export
##
$smwgRSSEnabled = true;  // use to switch off RSS (it's not worse than querying Special:Ask, but attracts more users)
$smwgRSSWithPages = true; // Should RSS feeds deliver whole pages or just link to them?
##

###
# Settings for RDF export
##
$smwgAllowRecursiveExport = false; // can normal users request recursive export?
$smwgExportBacklinks = true; // should backlinks be included by default?
$smwgOWLFullExport = false; // decides, if the RDF export will export, by default,
// OWL Full or rather nice OWL DL. Can be overriden in the RDF export class.
// global $smwgNamespace;                     // The Namespace of exported URIs.
// $smwgNamespace = "http://example.org/id/"; // Will be set automatically if 
// nothing is given, but in order to make pretty URIs you will need to set this
// to something nice and adapt your Apache configuration appropriately. See the
// documentation on http://ontoworld.org/wiki/Help:Cool_URIs
##

###
# Overwriting the following array, you can define for which namespaces
# the semantic links and annotations are to be evaluated. On other
# pages, annotations can be given but are silently ignored. This is
# useful since, e.g., talk pages usually do not have attributes and
# the like. In fact, is is not obvious what a meaningful attribute of
# a talk page could be. Pages without annotations will also be ignored
# during full RDF export, unless they are referred to from another
# article.
##
$smwgNamespacesWithSemanticLinks = array(
	              NS_MAIN => true,
	              NS_TALK => false,
	              NS_USER => true,
	         NS_USER_TALK => false,
	           NS_PROJECT => true,
	      NS_PROJECT_TALK => false,
	             NS_IMAGE => true,
	        NS_IMAGE_TALK => false,
	         NS_MEDIAWIKI => false,
	    NS_MEDIAWIKI_TALK => false,
	          NS_TEMPLATE => false,
	     NS_TEMPLATE_TALK => false,
	              NS_HELP => true,
	         NS_HELP_TALK => false,
	          NS_CATEGORY => true,
	     NS_CATEGORY_TALK => false,
	     SMW_NS_PROPERTY  => true,
	SMW_NS_PROPERTY_TALK  => false,
	          SMW_NS_TYPE => true,
	     SMW_NS_TYPE_TALK => false
);
##



// some default settings which usually need no modification

###
# Use another storage backend for Semantic MediaWiki. Use SMW_STORE_TESTING
# to run tests without modifying your database at all.
##
define('SMW_STORE_MWDB',1); // uses the MediaWiki database, needs initialisation via Special:SMWAdmin.
define('SMW_STORE_TESTING',2); // dummy store for testing
define('SMW_STORE_RAP',3); // layers RAP between the MW db, needs initialisation via Special:SMWAdmin.
$smwgDefaultStore = SMW_STORE_MWDB;
##

###
# Setting this to true allows to translate all the labels within
# the browser GIVEN that they have interwiki links.
##
$smwgTranslate = false;

###
# -- FEATURE IS DISABLED --
# If you want to import ontologies, you need to install RAP,
# a free RDF API for PHP, see
#     http://www.wiwiss.fu-berlin.de/suhl/bizer/rdfapi/
# The following is the path to your installation of RAP
# (the directory where you extracted the files to) as seen
# from your local filesystem. Note that ontology import is
# highly experimental at the moment, and may not do what you
# extect.
##
//$smwgRAPPath = $smwgIP . '/libs/rdfapi-php';
//$smwgRAPPath = '/another/example/path/rdfapi-php';
##


