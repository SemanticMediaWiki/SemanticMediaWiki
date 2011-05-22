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
$smwgScriptPath = ( 
	( version_compare( $wgVersion, '1.16', '>=' ) && isset( $wgExtensionAssetsPath ) && $wgExtensionAssetsPath )
	? $wgExtensionAssetsPath : $wgScriptPath . '/extensions'
	) . '/SemanticMediaWiki';
##

###
# This is the path to your installation of Semantic MediaWiki as seen on your
# local filesystem. Used against some PHP file path issues.
# If needed, you can also change this path in LocalSettings.php after including
# this file.
##
$smwgIP = dirname( __FILE__ ) . '/';
##

###
# Use another storage backend for Semantic MediaWiki. The default is suitable
# for most uses of SMW.
##
$smwgDefaultStore = "SMWSQLStore2";
##

###
# Configure SPARQL database connection for Semantic MediaWiki. This is used
# when SPARQL-based features are enabled, e.g. when using SMWSparqlStore as
# the $smwgDefaultStore.
#
# The default class SMWSparqlDatabase works with many databases that support
# SPARQL and SPARQL Update. Three different endpoints (service URLs) are given
# for query (reading queries like SELECT), update (SPARQL Update queries), and
# data (SPARQL HTTP Protocol for Graph Management). The query endpoint is
# necessary, but the update and data endpoints can be omitted if not supported.
# This will lead to reduced functionality (e.g. the SMWSparqlStore will not
# work if Update is not available). The data endpoint is always optional, but
# in some SPARQL databases this method is more efficient than update.
##
$smwgSparqlDatabase = 'SMWSparqlDatabase';
$smwgSparqlQueryEndpoint = 'http://localhost:8080/sparql/';
$smwgSparqlUpdateEndpoint = 'http://localhost:8080/update/';
$smwgSparqlDataEndpoint = 'http://localhost:8080/data/';
##

// load global constants and setup functions
require_once( 'includes/SMW_Setup.php' );

###
# Setting this option to true before including this file to enable the old
# Type: namespace that SMW used up to version 1.5.*. This should only be
# done to make the pages of this namespace temporarily accessible in order to
# move their content to other pages. If the namespace is not registered, then
# existing pages in this namespace cannot be found in the wiki.
##
if ( !isset( $smwgHistoricTypeNamespace ) ) {
	$smwgHistoricTypeNamespace = false;
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
// $smwgShowFactbox = SMW_FACTBOX_NONEMPTY; # show only those factboxes that have some content
// $smwgShowFactbox = SMW_FACTBOX_SPECIAL # show only if special properties were set
$smwgShowFactbox = SMW_FACTBOX_HIDDEN; # hide always
// $smwgShowFactbox = SMW_FACTBOX_SHOWN;  # show always, buggy and not recommended
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
# Should SMW consider MediaWiki's subcategory hierarchy in querying? If set to
# true, subcategories will always be interpreted like subclasses. For example,
# if A is a subcategory of B then a query for all elements of B will also yield
# all elements of A. If this setting is disabled, then subclass relationships
# can still be given explicitly by using the property "subcategory of" on some
# category page. Only if the setting is false will such annotations be shown in
# the factbox (if enabled).
##
$smwgUseCategoryHierarchy = true;
##

###
# Should category pages that use some [[Category:Foo]] statement be treated as
# elements of the category Foo? If disabled, then it is not possible to make
# category pages elements of other categories. See also the above setting
# $smwgUseCategoryHierarchy.
##
$smwgCategoriesAsInstances = true;
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
# Settings for recurring events, created with the #set_recurring_event parser
# function: the default number of instances defined, if no end date is set;
# and the maximum number that can be defined, regardless of end date.
##
$smwgDefaultNumRecurringEvents = 100;
$smwgMaxNumRecurringEvents = 500;
##

###
# Should the browse view for incoming links show the incoming links via its
# inverses, or shall they be displayed on the other side?
##
$smwgBrowseShowInverse = false;
##

###
# Should the browse view always show the incoming links as well, and more of
# the incoming values?
##
$smwgBrowseShowAll = true;
##

###
# Should the search by property special page display nearby results when there
# are only a few results with the exact value? Switch this off if this page has
# performance problems.
##
$smwgSearchByPropertyFuzzy = true;
##

###
# Number results shown in the listings on pages in the namespaces Property,
# Type, and Concept. If a value of 0 is given, the respective listings are
# hidden completely.
##
$smwgTypePagingLimit = 200;    // same number as for categories
$smwgConceptPagingLimit = 200; // same number as for categories
$smwgPropertyPagingLimit = 25; // use smaller value since property lists need more space
##

###
# How many values should at most be displayed for a page on the Property page?
##
$smwgMaxPropertyValues = 3; // if large values are desired, consider reducing $smwgPropertyPagingLimit for better performance
##

###
# Settings for inline queries ({{#ask:...}}) and for semantic queries in
# general. This can especially be used to prevent overly high server-load due
# to complex queries. The following settings affect all queries, wherever they
# occur.
##
$smwgQEnabled = true;   // (De)activates all query related features and interfaces
$smwgQMaxLimit = 10000; // Max number of results *ever* retrieved, even when using special query pages.
$smwgIgnoreQueryErrors = true; // Should queries be executed even if some errors were detected?
                               // A hint that points out errors is shown in any case.

$smwgQSubcategoryDepth = 10;  // Restrict level of sub-category inclusion (steps within category hierarchy)
$smwgQSubpropertyDepth = 10;  // Restrict level of sub-property inclusion (steps within property hierarchy)
                              // (Use 0 to disable hierarchy-inferencing in queries)
$smwgQEqualitySupport = SMW_EQ_SOME; // Evaluate #redirects as equality between page names, with possible
                                     // performance-relevant restrictions depending on the storage engine
  // $smwgQEqualitySupport = SMW_EQ_FULL; // Evaluate #redirects as equality between page names in all cases
  // $smwgQEqualitySupport = SMW_EQ_NONE; // Never evaluate #redirects as equality between page names
$smwgQSortingSupport     = true; // (De)activate sorting of results.
$smwgQRandSortingSupport = true; // (De)activate random sorting of results.
$smwgQDefaultNamespaces = null; // Which namespaces should be searched by default?
                                // (value NULL switches off default restrictions on searching -- this is faster)
                                // Example with namespaces: $smwgQDefaultNamespaces = array(NS_MAIN, NS_IMAGE);

/**
 * List of comparator characters supported by queries, separated by '|', for use in a regex.
 *
 * Available entries:
 * 	< (smaller than) if $smwStrictComparators is false, it's actually smaller than or equal to
 * 	> (greater than) if $smwStrictComparators is false, it's actually bigger than or equal to
 * 	! (unequal to)
 * 	~ (pattern with '*' as wildcard, only for Type:String)
 * 	!~ (not a pattern with '*' as wildcard, only for Type:String, need to be placed before ! and ~ to work correctly)
 * 	≤ (smaller than or equal to)
 * 	≥ (greater than or equal to)
 *
 * If unsupported comparators are used, they are treated as part of the queried value
 *
 * @var string
 */
$smwgQComparators = '<|>|!~|!|~|≤|≥|<<|>>';

###
# Sets whether the > and < comparators should be strict or not. If they are strict,
# values that are equal will not be accepted.
##
$smwStrictComparators = false;
##

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
       // CONCEPT_CACHE_ALL   -- show concept elements anywhere only if they are cached
       // CONCEPT_CACHE_HARD  -- show without cache if concept is not harder than permitted inline queries
       // CONCEPT_CACHE_NONE  -- show all concepts even without any cache
       // In any cases, caches will always be used if available.
$smwgQConceptMaxSize = 20; // Same as $smwgQMaxSize, but for concepts
$smwgQConceptMaxDepth = 8; // Same as $smwgQMaxDepth, but for concepts

// Same as $smwgQFeatures but for concepts (note: using concepts in concepts is currently not supported!)
$smwgQConceptFeatures = SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_NAMESPACE_QUERY |
                        SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY;

// Cache life time in minutes. If a concept cache exists but is older than
// this, SMW tries to recompute it, and will only use the cache if this is not
// allowed due to settings above:
$smwgQConceptCacheLifetime = 24 * 60;


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
	'category'   => 'SMWCategoryResultPrinter',
	'embedded'   => 'SMWEmbeddedResultPrinter',
	'template'   => 'SMWListResultPrinter',
	'count'      => 'SMWListResultPrinter',
	'debug'      => 'SMWListResultPrinter',
	'rss'        => 'SMWRSSResultPrinter',
	'csv'        => 'SMWCsvResultPrinter',
	'dsv'        => 'SMWDSVResultPrinter',
	'json'       => 'SMWJSONResultPrinter',
	'rdf'        => 'SMWRDFResultPrinter'
);
##

### Predefined aliases for result formats
# Array of available aliases for result formats. Can be redefined in
# the settings to disallow certain aliases or to register extension aliases.
# To disable an alias, do "unset($smwgResultAliases['alias']);" Disabled
# aliases will be treated like if the alias parameter had been omitted.
##
$smwgResultAliases = array();
##

### Predefined sources for queries
# Array of available sources for answering queries. Can be redefined in
# the settings to register new sources (usually an extension will do so
# on installation). Unknown source will be rerouted to the local wiki.
# Note that the basic installation comes with no additional source besides
# the local source (which in turn cannot be disabled or set explicitly).
# Set a new store like this: $smwgQuerySources['freebase'] = "SMWFreebaseStore";
##
$smwgQuerySources = array(
//	'local'      => '',
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
// global $smwgNamespace;                     // The Namespace of exported URIs.
// $smwgNamespace = "http://example.org/id/"; // Will be set automatically if
// nothing is given, but in order to make pretty URIs you will need to set this
// to something nice and adapt your Apache configuration appropriately. This is
// done, e.g., on semanticweb.org, where URIs are of the form
// http://semanticweb.org/id/FOAF
##

###
# The maximal number that SMW will normally display without using scientific exp
# notation. The deafult is rather large since some users have problems understanding
# exponents. Scineitfic applications may prefer a smaller value for concise display.
##
$smwgMaxNonExpNumber = 1000000000000000;
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

###
# Properties (usually given as internal ids or DB key versions of property
# titles) that are relevant for declaring the behaviour of a property P on a
# property page in the sense that changing their values requires that all
# pages that use P must be processed again. For example, if _PVAL (allowed
# values) for a property change, then pages must be processed again. This
# setting is not normally changed by users but by extensions that add new
# types that have their own additional declaration properties.
##
$smwgDeclarationProperties = array( '_PVAL', '_LIST' );
##

// some default settings which usually need no modification

###
# -- FEATURE IS DISABLED --
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
// $smwgRAPPath = $smwgIP . 'libs/rdfapi-php';
// $smwgRAPPath = '/another/example/path/rdfapi-php';
##

###
# If the following is set to true, it is possible to initiate the repairing
# or updating of all wiki data using the interface on Special:SMWAdmin.
##
$smwgAdminRefreshStore = true;
##

###
# Sets whether or not the 'printouts' textarea should have autocompletion
# on property names.
##
$smwgAutocompleteInSpecialAsk = true;
##

###
# Sets whether or not to refresh the pages of which semantic data is stored.
# Introduced in SMW 1.5.6
##
$smwgAutoRefreshSubject = true;
##
