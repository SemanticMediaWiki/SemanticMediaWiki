<?php

namespace SMW;

/**
 * SemanticMediaWiki default settings
 *
 */

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
# This is the path to your installation of Semantic MediaWiki as seen on your
# local filesystem. Used against some PHP file path issues.
# If needed, you can also change this path in LocalSettings.php after including
# this file.
##
$GLOBALS['smwgIP'] = dirname( __FILE__ ) . '/';
##

###
# Use another storage backend for Semantic MediaWiki. The default is suitable
# for most uses of SMW.
##
$GLOBALS['smwgDefaultStore'] = "SMWSQLStore3";
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
#
# The default graph is similar to a database name in relational databases. It
# can be set to any URI (e.g. the main page uri of your wiki with
# "#graph" appended). Leaving the default graph URI empty only works if the
# store is configure to use some default default graph or if it generally
# supports this. Different wikis should normally use different default graphs
# unless there is a good reason to share one graph.
##
$GLOBALS['smwgSparqlDatabase'] = 'SMWSparqlDatabase';
$GLOBALS['smwgSparqlQueryEndpoint'] = 'http://localhost:8080/sparql/';
$GLOBALS['smwgSparqlUpdateEndpoint'] = 'http://localhost:8080/update/';
$GLOBALS['smwgSparqlDataEndpoint'] = 'http://localhost:8080/data/';
$GLOBALS['smwgSparqlDefaultGraph'] = '';
##

##
# SparqlDBConnectionProvider
#
# Identifies a database connector that ought to be used together with the
# SPARQLStore
#
# List of standard connectors ($smwgSparqlDatabase will have no effect)
# - 'fuseki'
# - 'virtuoso'
# - '4store'
# - 'sesame'
# - 'generic'
#
# With 2.0 it is suggested to assign the necessary connector to
# $smwgSparqlDatabaseConnector in order to avoid arbitrary class assignments in
# $smwgSparqlDatabase (which can change in future releases without further notice).
#
# In case $smwgSparqlDatabaseConnector = 'custom' is maintained, $smwgSparqlDatabase
# is expected to contain a custom class connector where $smwgSparqlDatabase is only
# to be sued for when a custom database connector is necessary.
#
# $smwgSparqlDatabaseConnector = 'custom' is set as legacy configuration to allow for
# existing (prior 2.0) customizing to work without changes.
#
# @since 2.0
##
$GLOBALS['smwgSparqlDatabaseConnector'] = 'custom';

###
# Setting this option to true before including this file to enable the old
# Type: namespace that SMW used up to version 1.5.*. This should only be
# done to make the pages of this namespace temporarily accessible in order to
# move their content to other pages. If the namespace is not registered, then
# existing pages in this namespace cannot be found in the wiki.
##
if ( !isset( $GLOBALS['smwgHistoricTypeNamespace'] ) ) {
	$GLOBALS['smwgHistoricTypeNamespace'] = false;
}
##

###
# If you already have custom namespaces on your site, insert
#    $GLOBALS['smwgNamespaceIndex'] = ???;
# into your LocalSettings.php *before* including this file. The number ??? must
# be the smallest even namespace number that is not in use yet. However, it
# must not be smaller than 100.
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
$GLOBALS['smwgNamespacesWithSemanticLinks'] = array(
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
);

###
# This setting allows you to select in which cases you want to have a factbox
# appear below an article. Note that the Magic Words __SHOWFACTBOX__ and
# __HIDEFACTBOX__ can be used to control Factbox display for individual pages.
# Other options for this setting include:
##
// $GLOBALS['smwgShowFactbox'] = SMW_FACTBOX_NONEMPTY; # show only those factboxes that have some content
// $GLOBALS['smwgShowFactbox'] = SMW_FACTBOX_SPECIAL # show only if special properties were set
$GLOBALS['smwgShowFactbox'] = SMW_FACTBOX_HIDDEN; # hide always
// $GLOBALS['smwgShowFactbox'] = SMW_FACTBOX_SHOWN;  # show always, buggy and not recommended
##

###
# Same as $smwgShowFactbox but for edit mode and same possible values.
##
$GLOBALS['smwgShowFactboxEdit'] = SMW_FACTBOX_NONEMPTY;
##

###
# Should the toolbox of each content page show a link to browse the properties
# of that page using Special:Browse? This is a useful way to access properties
# and it is somewhat more subtle than showing a Factbox on every page.
##
$GLOBALS['smwgToolboxBrowseLink'] = true;
##

###
# Should warnings be displayed in wikitexts right after the problematic input?
# This affects only semantic annotations, not warnings that are displayed by
# inline queries or other features.
##
$GLOBALS['smwgInlineErrors'] = true;
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
$GLOBALS['smwgUseCategoryHierarchy'] = true;
##

###
# Should category pages that use some [[Category:Foo]] statement be treated as
# elements of the category Foo? If disabled, then it is not possible to make
# category pages elements of other categories. See also the above setting
# $smwgUseCategoryHierarchy.
##
$GLOBALS['smwgCategoriesAsInstances'] = true;
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
$GLOBALS['smwgLinksInValues'] = false;
##

###
# Settings for recurring events, created with the #set_recurring_event parser
# function: the default number of instances defined, if no end date is set;
# and the maximum number that can be defined, regardless of end date.
##
$GLOBALS['smwgDefaultNumRecurringEvents'] = 100;
$GLOBALS['smwgMaxNumRecurringEvents'] = 500;
##

###
# Should the browse view for incoming links show the incoming links via its
# inverses, or shall they be displayed on the other side?
##
$GLOBALS['smwgBrowseShowInverse'] = false;
##

###
# Should the browse view always show the incoming links as well, and more of
# the incoming values?
##
$GLOBALS['smwgBrowseShowAll'] = true;
##

###
# Should the search by property special page display nearby results when there
# are only a few results with the exact value? Switch this off if this page has
# performance problems.
#
# @since 2.1 enabled default types, to disable the functionality either set the
# variable to array() or false
##
$GLOBALS['smwgSearchByPropertyFuzzy'] = array( '_num', '_txt', '_dat' );
##

###
# Number results shown in the listings on pages in the namespaces Property,
# Type, and Concept. If a value of 0 is given, the respective listings are
# hidden completely.
##
$GLOBALS['smwgTypePagingLimit'] = 200;    // same number as for categories
$GLOBALS['smwgConceptPagingLimit'] = 200; // same number as for categories
$GLOBALS['smwgPropertyPagingLimit'] = 25; // use smaller value since property lists need more space
##

###
# How many values should at most be displayed for a page on the Property page?
##
$GLOBALS['smwgMaxPropertyValues'] = 3; // if large values are desired, consider reducing $smwgPropertyPagingLimit for better performance
##

###
# Settings for inline queries ({{#ask:...}}) and for semantic queries in
# general. This can especially be used to prevent overly high server-load due
# to complex queries. The following settings affect all queries, wherever they
# occur.
##
$GLOBALS['smwgQEnabled'] = true;   // (De)activates all query related features and interfaces
$GLOBALS['smwgQMaxLimit'] = 10000; // Max number of results *ever* retrieved, even when using special query pages.
$GLOBALS['smwgIgnoreQueryErrors'] = true; // Should queries be executed even if some errors were detected?
                               // A hint that points out errors is shown in any case.

$GLOBALS['smwgQSubcategoryDepth'] = 10;  // Restrict level of sub-category inclusion (steps within category hierarchy)
$GLOBALS['smwgQSubpropertyDepth'] = 10;  // Restrict level of sub-property inclusion (steps within property hierarchy)
                              // (Use 0 to disable hierarchy-inferencing in queries)
$GLOBALS['smwgQEqualitySupport'] = SMW_EQ_SOME; // Evaluate #redirects as equality between page names, with possible
                                     // performance-relevant restrictions depending on the storage engine
  // $GLOBALS['smwgQEqualitySupport'] = SMW_EQ_FULL; // Evaluate #redirects as equality between page names in all cases
  // $GLOBALS['smwgQEqualitySupport'] = SMW_EQ_NONE; // Never evaluate #redirects as equality between page names
$GLOBALS['smwgQSortingSupport']     = true; // (De)activate sorting of results.
$GLOBALS['smwgQRandSortingSupport'] = true; // (De)activate random sorting of results.
$GLOBALS['smwgQDefaultNamespaces'] = null; // Which namespaces should be searched by default?
                                // (value NULL switches off default restrictions on searching -- this is faster)
                                // Example with namespaces: $GLOBALS['smwgQDefaultNamespaces'] = array(NS_MAIN, NS_IMAGE);

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
$GLOBALS['smwgQComparators'] = '<|>|!~|!|~|≤|≥|<<|>>';

###
# Sets whether the > and < comparators should be strict or not. If they are strict,
# values that are equal will not be accepted.
##
$GLOBALS['smwStrictComparators'] = false;
##

###
# Further settings for queries. The following settings affect inline queries
# and querying special pages. Essentially they should mirror the kind of
# queries that should immediately be answered by the wiki, using whatever
# computations are needed.
##
$GLOBALS['smwgQMaxSize'] = 12; // Maximal number of conditions in queries, use format=debug for example sizes
$GLOBALS['smwgQMaxDepth'] = 4; // Maximal property depth of queries, e.g. [[rel::<q>[[rel2::Test]]</q>]] has depth 2

// The below setting defines which query features should be available by default.
// Examples:
// only cateory intersections: $GLOBALS['smwgQFeatures'] = SMW_CATEGORY_QUERY | SMW_CONJUNCTION_QUERY;
// only single concepts:       $GLOBALS['smwgQFeatures'] = SMW_CONCEPT_QUERY;
// anything but disjunctions:  $GLOBALS['smwgQFeatures'] = SMW_ANY_QUERY & ~SMW_DISJUNCTION_QUERY;
// The default is to support all basic features.
$GLOBALS['smwgQFeatures'] = SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_CONCEPT_QUERY |
                 SMW_NAMESPACE_QUERY | SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY;

### Settings about printout of (especially inline) queries:
$GLOBALS['smwgQDefaultLimit'] = 50;      // Default number of rows returned in a query. Can be increased with limit=num in #ask
$GLOBALS['smwgQMaxInlineLimit'] = 500;   // Max number of rows ever printed in a single inline query on a single page.
$GLOBALS['smwgQUpperbound'] = 5000;      // Max number of rows ever printed in a single inline query on a single page.
$GLOBALS['smwgQPrintoutLimit']  = 100;   // Max number of supported printouts (added columns in result table, ?-statements)
$GLOBALS['smwgQDefaultLinking'] = 'all'; // Default linking behavior. Can be one of "none", "subject" (first column), "all".


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
$GLOBALS['smwgQConceptCaching'] = CONCEPT_CACHE_HARD; // Which concepts should be displayed only if available from cache?
       // CONCEPT_CACHE_ALL   -- show concept elements anywhere only if they are cached
       // CONCEPT_CACHE_HARD  -- show without cache if concept is not harder than permitted inline queries
       // CONCEPT_CACHE_NONE  -- show all concepts even without any cache
       // In any cases, caches will always be used if available.
$GLOBALS['smwgQConceptMaxSize'] = 20; // Same as $smwgQMaxSize, but for concepts
$GLOBALS['smwgQConceptMaxDepth'] = 8; // Same as $smwgQMaxDepth, but for concepts

// Same as $smwgQFeatures but for concepts
$GLOBALS['smwgQConceptFeatures'] = SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_NAMESPACE_QUERY |
                        SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY | SMW_CONCEPT_QUERY;

// Cache life time in minutes. If a concept cache exists but is older than
// this, SMW tries to recompute it, and will only use the cache if this is not
// allowed due to settings above:
$GLOBALS['smwgQConceptCacheLifetime'] = 24 * 60;


### Predefined result formats for queries
# Array of available formats for formatting queries. Can be redefined in
# the settings to disallow certain formats or to register extension formats.
# To disable a format, do "unset($smwgResultFormats['template']);" Disabled
# formats will be treated like if the format parameter had been omitted. The
# formats 'table' and 'list' are defaults that cannot be disabled. The format
# 'broadtable' should not be disabled either in order not to break Special:ask.
##
$GLOBALS['smwgResultFormats'] = array(
	'table'      => 'SMW\TableResultPrinter',
	'list'       => 'SMW\ListResultPrinter',
	'ol'         => 'SMW\ListResultPrinter',
	'ul'         => 'SMW\ListResultPrinter',
	'broadtable' => 'SMW\TableResultPrinter',
	'category'   => 'SMW\CategoryResultPrinter',
	'embedded'   => 'SMW\EmbeddedResultPrinter',
	'template'   => 'SMW\ListResultPrinter',
	'count'      => 'SMW\ListResultPrinter',
	'debug'      => 'SMW\ListResultPrinter',
	'feed'       => 'SMW\FeedResultPrinter',
	'csv'        => 'SMW\CsvResultPrinter',
	'dsv'        => 'SMW\DsvResultPrinter',
	'json'       => 'SMW\JsonResultPrinter',
	'rdf'        => 'SMW\RdfResultPrinter'
);
##

### Predefined aliases for result formats
# Array of available aliases for result formats. Can be redefined in
# the settings to disallow certain aliases or to register extension aliases.
# To disable an alias, do "unset($smwgResultAliases['alias']);" Disabled
# aliases will be treated like if the alias parameter had been omitted.
##
$GLOBALS['smwgResultAliases'] = array( 'feed' => array( 'rss' ) );
##

### Predefined sources for queries
# Array of available sources for answering queries. Can be redefined in
# the settings to register new sources (usually an extension will do so
# on installation). Unknown source will be rerouted to the local wiki.
# Note that the basic installation comes with no additional source besides
# the local source (which in turn cannot be disabled or set explicitly).
# Set a new store like this: $smwgQuerySources['freebase'] = "SMWFreebaseStore";
##
$GLOBALS['smwgQuerySources'] = array(
//	'local'      => '',
);
##

### Default property type
# Undefined properties (those without pages or whose pages have no "has type"
# statement) will be assumed to be of this type. This is an internal type id.
# See the file languages/SMW_LanguageXX.php to find what IDs to use for
# datatpyes in your language. The default corresponds to "Type:Page".
##
$GLOBALS['smwgPDefaultType'] = '_wpg';
##

###
# Settings for OWL/RDF export
##
$GLOBALS['smwgAllowRecursiveExport'] = false; // can normal users request recursive export?
$GLOBALS['smwgExportBacklinks'] = true; // should backlinks be included by default?
// global $smwgNamespace;                     // The Namespace of exported URIs.
// $GLOBALS['smwgNamespace'] = "http://example.org/id/"; // Will be set automatically if
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
$GLOBALS['smwgMaxNonExpNumber'] = 1000000000000000;
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
$GLOBALS['smwgEnableUpdateJobs'] = true;
##

### List of enabled special page properties.
# Modification date (_MDAT) is enabled by default for backward compatibility.
# Extend array to enable other properties:
#     $smwgPageSpecialProperties[] = '_CDAT';
# Or:
#     array_merge( $smwgPageSpecialProperties, array( '_CDAT' ) );
# Or rewrite entire array:
#     $GLOBALS['smwgPageSpecialProperties'] = array( '_MDAT', '_CDAT' );
# However, DO NOT use `+=' operator! This DOES NOT work:
#     $smwgPageSpecialProperties += array( '_MDAT' );
##
$GLOBALS['smwgPageSpecialProperties'] = array( '_MDAT' );

###
# Properties (usually given as internal ids or DB key versions of property
# titles) that are relevant for declaring the behavior of a property P on a
# property page in the sense that changing their values requires that all
# pages that use P must be processed again. For example, if _PVAL (allowed
# values) for a property change, then pages must be processed again. This
# setting is not normally changed by users but by extensions that add new
# types that have their own additional declaration properties.
##
$GLOBALS['smwgDeclarationProperties'] = array( '_PVAL', '_LIST' );
##

// some default settings which usually need no modification

###
# -- FEATURE IS DISABLED --
# Setting this to true allows to translate all the labels within
# the browser GIVEN that they have interwiki links.
##
$GLOBALS['smwgTranslate'] = false;

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
// $GLOBALS['smwgRAPPath'] = $smwgIP . 'libs/rdfapi-php';
// $GLOBALS['smwgRAPPath'] = '/another/example/path/rdfapi-php';
##

###
# If the following is set to true, it is possible to initiate the repairing
# or updating of all wiki data using the interface on Special:SMWAdmin.
##
$GLOBALS['smwgAdminRefreshStore'] = true;
##

###
# Sets whether or not the 'printouts' textarea should have autocompletion
# on property names.
##
$GLOBALS['smwgAutocompleteInSpecialAsk'] = true;
##

###
# Sets whether or not to refresh the pages of which semantic data is stored.
# Introduced in SMW 1.5.6
##
$GLOBALS['smwgAutoRefreshSubject'] = true;
##

###
# Sets Semantic MediaWiki object cache and is used to track temporary
# changes in SMW
#
# @see http://www.mediawiki.org/wiki/$wgMainCacheType
#
# @since 1.9
##
$GLOBALS['smwgCacheType'] = CACHE_ANYTHING;
##

###
# An array containing cache related settings used within Semantic MediaWiki
# and requires $smwgCacheType be set otherwise caching will have no effect.
#
# - smwgWantedPropertiesCache Enable to serve wanted properties from cache
# - smwgWantedPropertiesCacheExpiry Number of seconds before the cache expires
#
# - smwgUnusedPropertiesCache Enable to serve unused properties from cache
# - smwgUnusedPropertiesCacheExpiry Number of seconds before the cache expires
#
# - smwgPropertiesCache Enable to serve properties from cache
# - smwgPropertiesCacheExpiry Number of seconds before the cache expires
#
# - smwgStatisticsCache Enable to serve statistics from cache
# - smwgStatisticsCacheExpiry Number of seconds before the cache expires
#
# @since 1.9
##
$GLOBALS['smwgCacheUsage'] = array(
	'smwgWantedPropertiesCache' => true,
	'smwgWantedPropertiesCacheExpiry' => 3600,
	'smwgUnusedPropertiesCache' => true,
	'smwgUnusedPropertiesCacheExpiry' => 3600,
	'smwgPropertiesCache' => true,
	'smwgPropertiesCacheExpiry' => 3600,
	'smwgStatisticsCache' => true,
	'smwgStatisticsCacheExpiry' => 3600,
);

###
# Sets whether or not to refresh semantic data in the store when a page is
# manually purged
#
# @since 1.9
#
# @requires  $smwgCacheType be set
# @default true
##
$GLOBALS['smwgAutoRefreshOnPurge'] = true;
##

###
# Sets whether or not to refresh semantic data in the store when a page was
# moved
#
# @since 1.9
#
# @requires  $smwgCacheType be set
# @default true
##
$GLOBALS['smwgAutoRefreshOnPageMove'] = true;
##

##
# These are fixed properties, i.e. user defined properties having a
# dedicated table for them. Entries in this array have the following format:
#
# 		property_key => property_type.
#
# The 'property_key' is the title of the property (with underscores instead
# of _ and capital first letter).
# The 'property_type' denotes the type of the property and has to be one of the following:
#		SMWDataItem::TYPE_BLOB
#		SMWDataItem::TYPE_URI
#		SMWDataItem::TYPE_WIKIPAGE
#		SMWDataItem::TYPE_NUMBER
#		SMWDataItem::TYPE_TIME
#		SMWDataItem::TYPE_BOOLEAN
#		SMWDataItem::TYPE_CONTAINER
#		SMWDataItem::TYPE_GEO
#		SMWDataItem::TYPE_CONCEPT
#		SMWDataItem::TYPE_PROPERTY
#
# A run of setup using SMWAdmin is needed to create these tables. If an already used property is assigned a new table all old data for this property will
# become inaccessible for SMW. This can be repaired by either migrating it to the new table (repair data) or will eventually be updated on page edits.
#
# Example: If you have a property named 'Age' which is of type 'Number' then add in LocalSettings:
#
# 	$GLOBALS['smwgFixedProperties'] = array(
#		'Age' => SMWDataItem::TYPE_NUMBER
#  	);
#
# @see http://semantic-mediawiki.org/wiki/Fixed_properties
#
# @since 1.9
#
# @var array
##
$GLOBALS['smwgFixedProperties'] = array();

###
# Sets a threshold value for when a property is being highlighted as "hardly
# begin used" on Special:Properties
#
# @since 1.9
#
# default = 5
##
$GLOBALS['smwgPropertyLowUsageThreshold'] = 5;
##

###
# Hide properties where the usage count is zero on Special:Properties
#
# @since 1.9
#
# default = true (legacy behaviour)
##
$GLOBALS['smwgPropertyZeroCountDisplay'] = true;

###
# Sets whether or not a factbox content should be stored in cache. This will
# considerable improve page response time as non-changed page content will
# not cause re-parsing of factbox content and instead is served directly from
# cache while only a new revision will trigger to re-parse the factbox.
#
# If smwgFactboxUseCache is set false (equals legacy behaviour) then every page
# request will bind the factbox to be re-parsed.
#
# @since 1.9
#
# @requires $smwgCacheType be set
# @default true
##
$GLOBALS['smwgFactboxUseCache'] = true;
##

###
# Sets whether or not a cached factbox should be invalidated on an action=purge
# event
#
# If set false the factbox cache will be only reset after a new page revision
# but if set true each purge request (no new page revision) will invalidate
# the factbox cache
#
# @since 1.9
#
# @requires $smwgCacheType be set
# @default true
##
$GLOBALS['smwgFactboxCacheRefreshOnPurge'] = true;
##

###
# This option enables to omit categories (marked with __HIDDENCAT__) from
# the annotation process.
#
# If a category is updated of either being hidden or visible, pages need to
# be refreshed to ensure that the StoreUpdater can make use of the changed
# environment.
#
# @since 1.9
# @default true (true = legacy behaviour, false = not to show hidden categories)
##
$GLOBALS['smwgShowHiddenCategories'] = true;
##

###
# QueryProfiler related setting to enable/disable specific monitorable profile
# data
#
# @note If these settings are changed, please ensure to run update.php
#
# - smwgQueryDurationEnabled to record query duration (the time
# between the query result selection and output its)
#
# @since 1.9
##
$GLOBALS['smwgQueryProfiler'] = array(
	'smwgQueryDurationEnabled' => false,
);
##

###
# Enables SMW specific annotation and content processing for listed SpecialPages
#
# @since 1.9
##
$GLOBALS['smwgEnabledSpecialPage'] = array( 'Ask' );
##

###
# Page / subject deletion (action=delete)
#
# In case of 'smwgDeleteSubjectWithAssociatesRefresh' => TRUE the deletion
# process will initiate a refresh/update process of associative entities.
#
# It is suggested that the dispatch process of those entities are being carried
# out as deferred job 'smwgDeleteSubjectAsDeferredJob' => TRUE and the to minimize
# any performance degradation that can occur when a large pool of associates is
# assigned to the deleted subject.
#
# When 'smwgDeleteSubjectAsDeferredJob' => TRUE, MW 1.19/1.20 will return a
# 'Array to string conversion in JobQueue' because of an outdated JobQueue class
# it is therefore advised to update to a newer MW version or not to use this
# feature.
#
# The subject is always removed directly (without delay) and if selected the update
# of associative entities will always be carried out through a background job (as long as
# smwgEnableUpdateJobs is set true).
#
# Legacy behaviour
# -'smwgDeleteSubjectAsDeferredJob' => false
# -'smwgDeleteSubjectWithAssociatesRefresh' => false
#
# @since 1.9.0.1
##
$GLOBALS['smwgOnDeleteAction'] = array(
	'smwgDeleteSubjectAsDeferredJob' => false,
	'smwgDeleteSubjectWithAssociatesRefresh' => false
);
##

###
# Search engine to fall back to in case SMWSearch is used as custom search
# engine but is unable to interpret the search term as an SMW query
#
# Leave as null to select the default search engine for the selected database
# type (e.g. SearchMySQL, SearchPostgres or SearchOracle), or set to a class
# name to override to a custom search engine.
#
# @since 2.1
##
$GLOBALS['smwgFallbackSearchType'] = null;
##

###
# If enabled it will display help information on the edit page to support users
# unfamiliar with SMW when extending page content.
#
# @since 2.1
##
$GLOBALS['smwgEnabledEditPageHelp'] = true;
##

###
# SMW_DIFF_UPDATE (default) - As the default option it will compute the `diff`
# of an existing data set and only initiate an update on selected tables
#
# SMW_REPLACEMENT_UPDATE - This option is meant to be used to safeguard data
# integrity and force a complete replacement of a data set during an update.
# It comes with an additional cost of a DB write during the update process.
#
# SMW_TRX_UPDATE - This option will add each update process to its own transaction
# and ensures that a transaction is being committed before a new transaction is
# started
#
# e.g. SMW_DIFF_UPDATE | SMW_TRX_UPDATE
#
# @since 2.1
##
$GLOBALS['smwgUFeatures'] = SMW_DIFF_UPDATE;
##

###
#
# Log settings
#
# - `smwgLogEventTypes` make event types loggable and viewable in Special:Log
#
# - `sqlstore-query-execution` (default = false) to log events during query execution
# of the SQLStore
#
# @since 2.1
##
$GLOBALS['smwgLogEventTypes'] = array(
	'sqlstore-query-execution' => false
);
##
