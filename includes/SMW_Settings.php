<?php
/**
 * @file
 * @ingroup SMW
 */

#################################################################
#    CHANGING THE CONFIGURATION FOR SEMANTIC MEDIAWIKI          #
#################################################################
# Do not change this file directly, but copy custom settings    #
# into your LocalSettings.php. Most settings should be make     #
# between including this file and the call to enableSemantics().#
# Exceptions that need to be set before are documented below.   #
#################################################################

if ( !defined( 'MEDIAWIKI' ) ) {
  die( "This file is part of the Semantic MediaWiki extension. It is not a valid entry point.\n" );
}

###
# This is the path to your installation of Semantic MediaWiki as seen from the
# web. Change it if required ($wgScriptPath is the path to the base directory
# of your wiki). No final slash.
##
$smwgScriptPath = $wgScriptPath . '/extensions/SemanticMediaWiki';
##

###
# This is the path to your installation of Semantic MediaWiki as seen on your
# local filesystem. Used against some PHP file path issues.
##
$smwgIP = $IP . '/extensions/SemanticMediaWiki';
##

// load global functions
require_once('SMW_GlobalFunctions.php');

###
# SMW has changed a lot since version 0.1 and some additional settings are
# needed to preserve old functions on sites that have been using beta versions
# of SMW (any version prior to 1.0). Sites still using obsolete SMW beta
# features should set the following to "true". All others can safely keep the
# default here.
#
# Setting this option to true has the following effect:
# * The obsolete namespace "Relation" will still be created (maybe some sites
#   have content there).
# * Statements like [[property::*]] in queries will be interpreted as printout
#   statements (like ?property in the current #ask query syntax).
# This option must be set before including this file, or otherwise the old
# Relation namespaces will not be available.
##
if (!isset($smwgSMWBetaCompatible)) {
	$smwgSMWBetaCompatible = false;
}
##

###
# If you already have custom namespaces on your site, insert
#    $smwgNamespaceIndex = ???;
# into your LocalSettings.php *before* including this file. The number ??? must
# be the smallest even namespace number that is not in use yet. However, it
# must not be smaller than 100.
##
smwfInitNamespaces();

###
# This setting allows you to select in which cases you want to have a factbox
# appear below an article. Note that the Magic Words __SHOWFACTBOX__ and 
# __HIDEFACTBOX__ can be used to control Factbox display for individual pages. 
# Other options for this setting include:
##
//$smwgShowFactbox = SMW_FACTBOX_NONEMPTY; # show only those factboxes that have some content
//$smwgShowFactbox = SMW_FACTBOX_SPECIAL # show only if special properties were set
$smwgShowFactbox = SMW_FACTBOX_HIDDEN; # hide always
//$smwgShowFactbox = SMW_FACTBOX_SHOWN;  # show always, buggy and not recommended
##

###
# Same as $smwgShowFactbox but for edit mode and same possible values.
##
$smwgShowFactboxEdit = SMW_FACTBOX_NONEMPTY;
##

###
# Should the toolbox of each content page show a link to browse the properties
# of that page using Special:Browse? This is a useful way to access properties
# and it is somewhat more subtle than showing a Factbox on every page.
##
$smwgToolboxBrowseLink = true;
##

###
# Should warnings be displayed in wikitexts right after the problematic input?
# This affects only semantic annotations, not warnings that are displayed by
# inline queries or other features.
##
$smwgInlineErrors = true;
##

###
# Should SMW accept inputs like [[property::Some [[link]] in value]]? If
# enabled, this may lead to PHP crashes (!) when very long texts are used as
# values. This is due to limitations in the library PCRE that PHP uses for
# pattern matching. The provoked PHP crashes will prevent requests from being
# completed -- usually clients will receive server errors ("invalid response")
# or be offered to download "index.php". It might be okay to enable this if 
# such problems are not observed in your wiki.
##
$smwgLinksInValues = false;
##

###
# Should the browse view for incoming links show the incoming links via its
# inverses, or shall they be displayed on the other side?
##
$smwgBrowseShowInverse = false;

###
# Should the browse view always show the incoming links as well, and more of
# the incoming values?
##
$smwgBrowseShowAll = true;

###
# Should the search by property special page dipslay nearby results when there
# are only few results with the exact value? Switch this off if this page has
# performance problems.
##
$smwgSearchByPropertyFuzzy = true;

###
# Number results shown in the listings on pages in the namespaces Property,
# Type, and Concept.
##
$smwgTypePagingLimit = 200;    // same number as for categories
$smwgConceptPagingLimit = 200; // same number as for categories
$smwgPropertyPagingLimit = 25; // use smaller value since property lists need more space
##

###
# Settings for inline queries ({{#ask:...}}) and for semantic queries in
# general. This can especially  be used to prevent overly high server-load by
# complex queries. The following settings affect all queries, wherever they
# occur.
##
$smwgQEnabled = true;   // (De)activates all query related features and interfaces
$smwgQMaxLimit = 10000; // Max number of results ever retrieved, even when using special query pages.

$smwgQSubcategoryDepth = 10;  // Restrict level of sub-category inclusion (steps within category hierarchy)
$smwgQSubpropertyDepth = 10;  // Restrict level of sub-property inclusion (steps within property hierarchy)
                              // (Use 0 to disable hierarchy-inferencing in queries)
$smwgQEqualitySupport = SMW_EQ_SOME; // Evaluate #redirects as equality between page names, with possible
                                     // performance-relevant restrictions depending on the storage engine
  //$smwgQEqualitySupport = SMW_EQ_FULL; // Evaluate #redirects as equality between page names in all cases
  //$smwgQEqualitySupport = SMW_EQ_NONE; // Never evaluate #redirects as equality between page names
$smwgQSortingSupport    = true; // (De)activate sorting of results.
$smwgQDefaultNamespaces = NULL; // Which namespaces should be searched by default?
                                // (value NULL switches off default restrictions on searching -- this is faster)
                                // Example with namespaces: $smwgQDefaultNamespaces = array(NS_MAIN, NS_IMAGE);
$smwgQComparators = '<|>|!'; // List of comparator characters supported by queries, separated by '|'
                             // Available entries: < (smaller than), < (greater than), ! (unequal to),
                             //                    ~ (pattern with '*' as wildcard, only for Type:String)
                             // If unsupported comparators are used, they are treated as part of the queried value

###
# Further settings for queries. The following settings affect inline queries
# and querying special pages. Essentially they should mirror the kind of
# queries that should immediately be answered by the wiki, using whatever
# computations are needed.
##
$smwgQMaxSize = 12; // Maximal number of conditions in queries, use format=debug for example sizes
$smwgQMaxDepth = 4; // Maximal property depth of queries, e.g. [[rel::<q>[[rel2::Test]]</q>]] has depth 2

// The below setting defines which query features should be available by default.
// Examples:
// only cateory intersections: $smwgQFeatures = SMW_CATEGORY_QUERY | SMW_CONJUNCTION_QUERY;
// only single concepts:       $smwgQFeatures = SMW_CONCEPT_QUERY;
// anything but disjunctions:  $smwgQFeatures = SMW_ANY_QUERY & ~SMW_DISJUNCTION_QUERY;
// The default is to support all basic features.
$smwgQFeatures = SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_CONCEPT_QUERY |
                 SMW_NAMESPACE_QUERY | SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY;

### Settings about printout of (especially inline) queries:
$smwgQDefaultLimit = 50;      // Default number of rows returned in a query. Can be increased with limit=num in #ask
$smwgQMaxInlineLimit = 500;   // Max number of rows ever printed in a single inline query on a single page.
$smwgQPrintoutLimit  = 100;   // Max number of supported printouts (added columns in result table, ?-statements)
$smwgQDefaultLinking = 'all'; // Default linking behaviour. Can be one of "none", "subject" (first column), "all".


###
# Further settings for queries. The following settings affect queries that are
# part of concept pages. These are usually chosen to be les restricted than
# inline queries, since there are two other means for controling their use:
# (1) Concept queries that would not be allowed as normal queries will not be
# executed directly, but can use pre-computed results instead. This is the
# default.
# (2) The whole Concept: namespace can be restricted (using some suitable
# MediaWiki extension) to an experienced user group that may create more
# complex queries responably. Other users can employ thus defined concepts in
# their queries.
##
$smwgQConceptCaching = CONCEPT_CACHE_HARD; // Which concepts should be displayed only if available from cache?
       // CONCEPT_CACHE_ALL   -- show concept elements anywhere only if cached
       // CONCEPT_CACHE_HARD  -- show without cache if concept is not harder than permitted inline queries
       // CONCEPT_CACHE_NONE  -- show all concepts without any cache
       // In any cases, caches will always be used if available.
$smwgQConceptMaxSize = 20; // Same as $smwgQMaxSize, but for concepts
$smwgQConceptMaxDepth = 8; // Same as $smwgQMaxDepth, but for concepts

// Same as $smwgQFeatures but for concepts (note: using concepts in concepts is currently not supported!)
$smwgQConceptFeatures = SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_NAMESPACE_QUERY |
                        SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY;

// Cache life time in minutes. If a concept cache exists but is older than
// this, SMW tries to recompute it, and will only use the cache if this is not
// allowed due to settings above:
$smwgQConceptCacheLifetime = 24*60;


### Predefined result formats for queries
# Array of available formats for formatting queries. Can be redefined in
# the settings to disallow certain formats or to register extension formats.
# To disable a format, do "unset($smwgResultFormats['template']);" Disabled
# formats will be treated like if the format parameter had been omitted. The
# formats 'table' and 'list' are defaults that cannot be disabled. The format
# 'broadtable' should not be disabled either in order not to break Special:ask.
##
$smwgResultFormats = array(
	'table'      => 'SMWTableResultPrinter',
	'list'       => 'SMWListResultPrinter',
	'ol'         => 'SMWListResultPrinter',
	'ul'         => 'SMWListResultPrinter',
	'broadtable' => 'SMWTableResultPrinter',
	'embedded'   => 'SMWEmbeddedResultPrinter',
	'timeline'   => 'SMWTimelineResultPrinter',
	'eventline'  => 'SMWTimelineResultPrinter',
	'template'   => 'SMWTemplateResultPrinter',
	'count'      => 'SMWListResultPrinter',
	'debug'      => 'SMWListResultPrinter',
	'rss'        => 'SMWRSSResultPrinter',
	'icalendar'  => 'SMWiCalendarResultPrinter',
	'vcard'      => 'SMWvCardResultPrinter',
	'csv'        => 'SMWCsvResultPrinter'
);
##

### Default property type
# Undefined properties (those without pages or whose pages have no "has type"
# statement) will be assumed to be of this type. This is an internal type id.
# See the file languages/SMW_LanguageXX.php to find what IDs to use for
# datatpyes in your language. The default corresponds to "Type:Page".
##
$smwgPDefaultType = '_wpg';
##

###
# Settings for RSS export
##
$smwgRSSEnabled = true;  // use to switch off RSS (it's not worse than querying Special:Ask, but attracts more users)
$smwgRSSWithPages = true; // Should RSS feeds deliver whole pages or just link to them?
##

###
# Settings for OWL/RDF export
##
$smwgAllowRecursiveExport = false; // can normal users request recursive export?
$smwgExportBacklinks = true; // should backlinks be included by default?
$smwgOWLFullExport = false; // decides, if the RDF export will export, by default,
                            // OWL Full or rather nice OWL DL.
                            // Can be overriden in the RDF export class.
// global $smwgNamespace;                     // The Namespace of exported URIs.
// $smwgNamespace = "http://example.org/id/"; // Will be set automatically if 
// nothing is given, but in order to make pretty URIs you will need to set this
// to something nice and adapt your Apache configuration appropriately. This is
// done, e.g., on semanticweb.org, where URIs are of the form 
// http://semanticweb.org/id/FOAF
##

###
# SMW defers some tasks until after a page was edited by using the MediaWiki
# job queueing system (see http://www.mediawiki.org/wiki/Manual:Job_queue).
# For example, when the type of a property is changed, all affected pages will
# be scheduled for (later) update. If a wiki generates too many jobs in this
# way (Special:Statistics and "showJobs.php" can be used to check that), the
# following setting can be used to disable jobs. Note that this will cause some
# parts of the semantic data to get out of date, so that manual modifications
# or the use of SMW_refreshData.php might be needed.
##
$smwgEnableUpdateJobs = true;
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
	     SMW_NS_TYPE_TALK => false,
	       SMW_NS_CONCEPT => true,
	  SMW_NS_CONCEPT_TALK => false,
);
##



// some default settings which usually need no modification

###
# Use another storage backend for Semantic MediaWiki. Use SMW_STORE_TESTING
# to run tests without modifying your database at all.
##
$smwgDefaultStore = "SMWSQLStore2";
##
## The following is for backwards compatibility of LocalSettings.php only
 define('SMW_STORE_MWDB',"SMWSQLStore"); // uses the MediaWiki database, needs initialisation via Special:SMWAdmin.
 define('SMW_STORE_TESTING',"SMWTestStore"); // dummy store for testing
 define('SMW_STORE_RAP',"SMWRAPStore"); // layers RAP between the MW db, needs initialisation via Special:SMWAdmin.
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


