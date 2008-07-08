<?php

if ( !defined( 'MEDIAWIKI' ) ) {
  die( "This file is part of the Semantic MediaWiki extension. It is not a valid entry point.\n" );
}

#################################################################
#    CHANGING THE CONFIGURATION FOR SEMANTIC MEDIAWIKI          #
#################################################################
# Do not change this file directly, but copy custom settings    #
# into your LocalSettings.php. Most settings should be make     #
# between including this file and the call to enableSemantics().#
# Exceptions that need to be set before are documented below.   #
#################################################################

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
# SMW has changed a lot since version 0.1 and some additional settings are
# needed to preserve old functions on sites that have been using beta versions
# of SMW (any version prior to 1.0). Sites still using obsolete SMW beta features 
# should set the following to "true". All others can safely keep the default here.
#
# Setting this option to true has the following effect:
# * The obsolete namespace "Relation" will still be created (maybe some sites have
#   content there).
# * Statements like [[property::*]] in queries will be interpreted as printout
#   statements (like ?property in the current #ask query syntax).
# This option must be set before including this file, or otherwise the old Relation
# namespaces will not be available.
##
if (!isset($smwgSMWBetaCompatible)) {
	$smwgSMWBetaCompatible = false;
}
##

###
# If you already have custom namespaces on your site, insert
# $smwgNamespaceIndex = ???;
# into your LocalSettings.php *before* including this file.
# The number ??? must be the smallest even namespace number
# that is not in use yet. However, it must not be smaller
# than 100.
##
smwfInitNamespaces();

###
# This setting allows you to select in which cases you want to have a factbox
# appear below an article. The default setting is "SMW_FACTBOX_NONEMPTY"
# which shows only those factboxes that have some content. Note that the Magic
# Words __SHOWFACTBOX__ and __HIDEFACTBOX__ can be used to control Factbox 
# display for individual pages. Other options for this setting include:
##
$smwgShowFactbox = SMW_FACTBOX_NONEMPTY;
//$smwgShowFactbox = SMW_FACTBOX_SPECIAL # show only if special properties were set
//$smwgShowFactbox = SMW_FACTBOX_HIDDEN; # hide always
//$smwgShowFactbox = SMW_FACTBOX_SHOWN;  # show always, buggy and not recommended
##

###
# Same as $smwgShowFactbox but for edit mode and same possible values.
##
$smwgShowFactboxEdit = SMW_FACTBOX_NONEMPTY;
##

###
# Should warnings be displayed in wikitexts right after the problematic
# input? This affects only semantic annotations, not warnings that are 
# displayed by inline queries or other features.
##
$smwgInlineErrors = true;
##

###
# Number results shown in the listings on pages of properties (attributes or
# relations) and types.
##
$smwgTypePagingLimit = 200;    // same number as for categories
$smwgPropertyPagingLimit = 25; // use smaller value since property lists need more space
##

###
# Settings for inline queries ({{#ask:...}}) and for semantic queries in general.
# Especially meant to prevent overly high server-load by complex queries.
##
$smwgQEnabled = true;   // (De)activates all query related features and interfaces
$smwgQMaxSize = 12;     // Maximal number of conditions in queries, use format=debug for example sizes
$smwgQMaxDepth = 4;     // Maximal property depth of queries, e.g. [[rel::<q>[[rel2::Test]]</q>]] has depth 2
$smwgQMaxLimit = 10000; // Max number of results ever retrieved, even when using special query pages.

// The below setting defines which query features should be available by default.
// Examples:
// only cateory intersections: $smwgQFeatures = SMW_CATEGORY_QUERY | SMW_CONJUNCTION_QUERY;
// only single concepts:       $smwgQFeatures = SMW_CONCEPT_QUERY;
// anything but disjunctions:  $smwgQFeatures = SMW_ANY_QUERY & ~SMW_DISJUNCTION_QUERY;
// The default is to support all basic features.
$smwgQFeatures = SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_CONCEPT_QUERY |
                 SMW_NAMESPACE_QUERY | SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY;
// Same as $smwgQFeatures but for concept pages, may be used for allowing complex queries only in "Concept:"
// (note: using concepts in concepts is currently not supported)
$smwgQConceptFeatures = SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_NAMESPACE_QUERY |
                        SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY;

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

### Settings about printout of (especially inline) queries:
$smwgQDefaultLimit = 50;      // Default number of rows returned in a query. Can be increased with limit=num in #ask
$smwgQMaxInlineLimit = 500;   // Max number of rows ever printed in a single inline query on a single page.
$smwgQPrintoutLimit  = 100;   // Max number of supported printouts (added columns in result table, ?-statements)
$smwgQDefaultLinking = 'all'; // Default linking behaviour. Can be one of "none", "subject" (first column), "all".

### Default property type
# Undefined properties (those without pages or whose pages have no "has type" statement) will
# be assumed to be of this type. This is an internal type id. See the file languages/SMW_LanguageXX.php
# to find what IDs to use for datatpyes in your language. The default corresponds to "Type:Page".
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
# SMW defers some tasks until after a page was edited by using the MediaWiki job
# queueing system (see http://www.mediawiki.org/wiki/Manual:Job_queue). For example,
# when the type of a property is changed, all affected pages will be scheduled for
# (later) update. If a wiki generates too many jobs in this way (Special:Statistics
# and "showJobs.php" can be used to check that), the following setting can be used
# to disable jobs. Note that this will cause some parts of the semantic data to get 
# out of date, so that manual modifications or the use of SMW_refreshData.php might
# be needed.
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
$smwgDefaultStore = "SMWSQLStore";
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


