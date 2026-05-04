<?php

use SMW\SQLStore\EntityStore\EntityIdManager;

/**
 * DO NOT EDIT!
 *
 * The following default settings are to be used by the extension itself,
 * please modify settings in the LocalSettings file.
 *
 * Most settings should be made in LocalSettings.php after the call to
 * wfLoadExtension( 'SemanticMediaWiki' ).
 *
 * @codeCoverageIgnore
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the Semantic MediaWiki extension. It is not a valid entry point.\n" );
}

return ( static function (): array {
	SemanticMediaWiki::setupDefines();
	$smwgIP = dirname( __DIR__ ) . '/';
	return [

		# ##
		# This is the path to your installation of Semantic MediaWiki as seen on your
		# local filesystem. Used against some PHP file path issues.
		#
		# @since 1.0
		##
		'smwgIP' => $smwgIP,
		#
		# @since 2.5
		##
		'smwgExtraneousLanguageFileDir' => $smwgIP . '/i18n/extra',
		'smwgServicesFileDir' => $smwgIP . '/src/Services',
		'smwgMaintenanceDir' => $smwgIP . '/maintenance',
		'smwgDir' => $smwgIP,
		# #

		###
		# Configuration directory
		# @see #3506
		#
		# The maintained directory needs to be writable in order for configuration
		# information to be stored persistently and be accessible for Semantic
		# MediaWiki throughout its operation.
		#
		# You may assign the same directory as in `wgUploadDirectory` (e.g
		# $smwgConfigFileDir = $wgUploadDirectory;) or select an entire different
		# location. The default location is the Semantic MediaWiki extension root.
		#
		# During its operation it may contain:
		#  - `.smw.json`
		#  - `.smw.maintenance.json`
		#
		# @since 3.0
		##
		'smwgConfigFileDir' => $smwgIP,
		# #

		###
		# Content import
		#
		# Controls the content import directory and version that is expected to be
		# imported during the setup process.
		#
		# For all legitimate files in `smwgImportFileDirs`, the import is initiated
		# if the `smwgImportReqVersion` compares with the declared version in the file.
		#
		# In case `smwgImportReqVersion` is maintained with `false` then the import
		# is going to be disabled.
		#
		# @since 2.5
		##
		'smwgImportFileDirs' => [ 'smw' => $smwgIP . '/data/import' ],
		# #

		###
		# Local connection configurations
		#
		# Allows to modify connection characteristics for providers that are used by
		# Semantic MediaWiki.
		#
		# Changes to these settings should ONLY be made by trained professionals to
		# avoid unexpected or unanticipated results when using connection handlers.
		#
		# Available DB index as provided by MediaWiki:
		#
		# - DB_REPLICA (1.27.4+)
		# - DB_PRIMARY
		#
		# @since 2.5.3
		##
		'smwgLocalConnectionConf' => [
			'mw.db' => [
				'read'  => DB_REPLICA,
				'write' => DB_PRIMARY
			],
			'mw.db.queryengine' => [
				'read'  => DB_REPLICA,
				'write' => DB_PRIMARY
			]
		],
		# #

		###
		# Configure SPARQL database connection for Semantic MediaWiki. This is used
		# when SPARQL-based features are enabled, e.g. when using SPARQLStore as
		# the $smwgDefaultStore.
		#
		# The default class GenericRepositoryConnector works with many databases that support
		# SPARQL and SPARQL Update. Three different endpoints (service URLs) are given
		# - query (reading queries like SELECT)
		# - update (SPARQL Update queries), and
		# - data (SPARQL HTTP Protocol for Graph Management).
		#
		# The query endpoint is necessary, but the update and data endpoints can be
		# omitted if not supported.
		#
		# This will lead to reduced functionality (e.g. the SPARQLStore will not
		# work if Update is not available). The data endpoint is always optional, but
		# in some SPARQL databases this method is more efficient than update.
		#
		# @since 1.6
		##
		'smwgSparqlEndpoint' => [
			'query'  => 'http://localhost:8080/sparql/',
			'update' => 'http://localhost:8080/update/',
			'data'   => 'http://localhost:8080/data/'
		],
		# #

		##
		# Sparql query features that are expected to be supported by the repository:
		#
		# - SMW_SPARQL_QF_NONE does not support any features (as required by SPARQL 1.1)
		# - SMW_SPARQL_QF_REDI to support finding redirects using inverse property paths,
		#   can only be used for repositories with full SPARQL 1.1 support (e.g. Fuseki,
		#   Sesame)
		# - SMW_SPARQL_QF_SUBP to resolve subproperties
		# - SMW_SPARQL_QF_SUBC to resolve subcategories
		#
		# - SMW_SPARQL_QF_COLLATION allows to add support for the sorting collation as
		#   maintained in $smwgEntityCollation.
		#
		# - SMW_SPARQL_QF_NOCASE to support case insensitive pattern matches
		#
		# Please check with your repository provider whether SPARQL 1.1 is fully
		# supported or not, and if not SMW_SPARQL_QF_NONE should be set.
		#
		# @since 2.3
		##
		'smwgSparqlQFeatures' => SMW_SPARQL_QF_REDI | SMW_SPARQL_QF_SUBP | SMW_SPARQL_QF_SUBC,
		# #

		##
		# SPARQL respository specific features
		#
		# - SMW_SPARQL_NONE does not support any features
		#
		# - SMW_SPARQL_CONNECTION_PING to support the verifcation that a connection
		#   can be established and allows for an uninterruppted update and query
		#   process
		#
		# @since 3.2
		##
		'smwgSparqlRepositoryFeatures' => SMW_SPARQL_NONE,
		# #

		###
		# If you already have custom namespaces on your site, insert
		#    	'smwgNamespaceIndex' => ???,
		# into your LocalSettings.php *before* including this file. The number ??? must
		# be the smallest even namespace number that is not in use yet. However, it
		# must not be smaller than 100.
		#
		# @since 1.6
		##
		# 'smwgNamespaceIndex' => 100,
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
		#
		# @since 0.7
		##
		'smwgNamespacesWithSemanticLinks' => [
			NS_MAIN => true,
			NS_TALK => false,
			NS_USER => true,
			NS_USER_TALK => false,
			NS_PROJECT => true,
			NS_PROJECT_TALK => false,
			NS_FILE => true,
			NS_FILE_TALK => false,
			NS_MEDIAWIKI => false,
			NS_MEDIAWIKI_TALK => false,
			NS_TEMPLATE => false,
			NS_TEMPLATE_TALK => false,
			NS_HELP => true,
			NS_HELP_TALK => false,
			NS_CATEGORY => true,
			NS_CATEGORY_TALK => false,
		],
		# #

		###
		# Specifies features supported by the in-page factbox
		#
		# - SMW_FACTBOX_CACHE to use the main cache to avoid reparsing the content on
		#   each page view (replaced smwgFactboxUseCache)
		#
		# - SMW_FACTBOX_PURGE_REFRESH to refresh the faxtbox content on the purge
		#   event (replaced smwgFactboxCacheRefreshOnPurge)
		#
		# - SMW_FACTBOX_DISPLAY_SUBOBJECT displays subobject references
		#
		# - SMW_FACTBOX_DISPLAY_ATTACHMENT displays attachment list
		#
		# @since 3.0
		##
		'smwgFactboxFeatures' => SMW_FACTBOX_CACHE | SMW_FACTBOX_PURGE_REFRESH | SMW_FACTBOX_DISPLAY_SUBOBJECT | SMW_FACTBOX_DISPLAY_ATTACHMENT,

		# ##
		# This setting allows you to select in which cases you want to have a factbox
		# appear below an article and includes the following options:
		#
		# - SMW_FACTBOX_NONEMPTY show only those factboxes that have some content
		# - SMW_FACTBOX_SPECIAL show only if special properties were set
		# - SMW_FACTBOX_HIDDEN hide always
		# - SMW_FACTBOX_SHOWN  show always
		#
		# @note  The Magic Words __SHOWFACTBOX__ and __HIDEFACTBOX__ can be used to
		# control Factbox display for individual pages.
		#
		# @since 0.7
		##
		'smwgShowFactbox' => SMW_FACTBOX_HIDDEN,
		# #

		###
		# Same as $smwgShowFactbox but for the edit mode with same possible values.
		#
		# @since 1.0
		##
		'smwgShowFactboxEdit' => SMW_FACTBOX_NONEMPTY,
		# #

		###
		#
		# - SMW_CAT_NONE
		#
		# - SMW_CAT_REDIRECT: resolves redirects and errors in connection with categories
		#
		# - SMW_CAT_INSTANCE: Should category pages that use some [[Category:Foo]]
		#   statement be treated as elements of the category Foo? If disabled, then
		#   it is not possible to make category pages elements of other categories.
		#   See also SMW_CAT_HIERARCHY. (was $smwgCategoriesAsInstances)
		#
		# - SMW_CAT_HIERARCHY: Should a subcategory be considered a hierarchy element
		#   in the annotation process? If set to true, subcategories will always be
		#   interpreted as subclasses and automatically annotated with
		#   `Subcategory of`. (was $smwgUseCategoryHierarchy)
		#
		# @since 3.0
		##
		'smwgCategoryFeatures' => SMW_CAT_REDIRECT | SMW_CAT_INSTANCE | SMW_CAT_HIERARCHY,
		# #

		###
		# Special:Browse related settings
		#
		# - SMW_BROWSE_NONE
		#
		# - SMW_BROWSE_TLINK: Should the toolbox of each content page show a link
		#   to browse the properties of that page using Special:Browse? This is a
		#   useful way to access properties and it is somewhat more subtle than
		#   showing a Factbox on every page. (was $smwgToolboxBrowseLink)
		#
		# - SMW_BROWSE_SHOW_INVERSE: Should the browse view for incoming links show
		#   the incoming links via its inverses, or shall they be displayed on the
		#   other side? (was $smwgBrowseShowInverse)
		#
		# - SMW_BROWSE_SHOW_INCOMING: Should the browse view always show the incoming links
		#   as well, and more of the incoming values? (was $smwgBrowseShowAll)
		#
		# - SMW_BROWSE_SHOW_GROUP: Should the browse view create group sections for
		#   properties that belong to the same property group?
		#
		# - SMW_BROWSE_SHOW_SORTKEY: Should the sortkey be displayed?
		#
		# - SMW_BROWSE_USE_API: Whether the browse display is to be generated using
		#   an API request or not. (was $smwgBrowseByApi)
		#
		# @since 3.0
		##
		'smwgBrowseFeatures' => SMW_BROWSE_TLINK | SMW_BROWSE_SHOW_INCOMING | SMW_BROWSE_SHOW_GROUP | SMW_BROWSE_USE_API,
		# #

		###
		# Number of results shown in the listings on pages in the Property and Concept
		# namespaces as well as other services that require a limit.
		#
		# If a value of 0 is given, the respective listings are hidden completely.
		#
		# - `type` used for `Special:Types` (was $smwgTypePagingLimit)
		# - `errorlist` used for `Special:ProcessingErrorList`
		# - `concept` (was $smwgConceptPagingLimit)
		# - `property` (was $smwgPropertyPagingLimit)
		#
		# Special:Browse
		# - `valuelist.outgoingt` outgoing value list count
		# - `valuelist.incoming` incoming value list count
		#
		# @since 3.0
		##
		'smwgPagingLimit' => [
			'type' => 50,
			'concept' => 250,
			'property' => 20,
			'errorlist' => 20,

			// Special:Browse
			'browse' => [
				'valuelist.outgoing' => 30,
				'valuelist.incoming' => 20,
			]
		],
		# #

		###
		# Property page list limits
		#
		# 'subproperty' limit the query request on subproperties
		# 'redirect' limit the query request on redirects
		# 'error' limit the query request on improper assignments
		#
		# `false` as value assignment will disable the display of a selected list
		#
		# @since 3.0
		##
		'smwgPropertyListLimit' => [
			'subproperty' => 25,
			'redirect' => 25,
			'error' => 10
		],
		# #

		##
		# Evaluate #redirects
		#
		# - SMW_EQ_NONE: Never evaluate #redirects as equality between page names
		#
		# - SMW_EQ_SOME: Evaluate #redirects as equality between page names, with
		#   possible performance-relevant restrictions depending on the storage
		#   engine
		#
		# - SMW_EQ_FULL: Evaluate #redirects as equality between page names in all
		#   cases
		#
		# @since 1.0
		# @default: SMW_EQ_SOME
		##
		'smwgQEqualitySupport' => SMW_EQ_SOME,
		# #

		###
		# Sort features
		#
		# - SMW_QSORT_NONE
		#
		# - SMW_QSORT: General sort support for query results (was
		#   $smwgQSortingSupport)
		#
		# - SMW_QSORT_RANDOM: Random sorting support for query results (was
		#   $smwgQRandSortingSupport)
		#
		# - SMW_QSORT_UNCONDITIONAL: Allows an unconditional sort of results even if
		#   the property doesn't exists as part of the result set (#2823). The option
		#   isn't implemented for the SPARQLStore and the ElasticStore requires
		#   the `sort.property.must.exists` to be diabled to reflect the same sorting
		#   characteristics as with this setting enabled.
		#
		# @since 3.0
		##
		'smwgQSortFeatures' => SMW_QSORT | SMW_QSORT_RANDOM,
		# #

		###
		# Sets whether the > and < comparators should be strict or not. If they are strict,
		# values that are equal will not be accepted.
		#
		# @since 1.5.3
		##
		'smwStrictComparators' => false,

		# ##
		# The below setting defines which query features should be available by
		# default.
		#
		# Examples:
		# only cateory intersections: 	'smwgQFeatures' => SMW_CATEGORY_QUERY | SMW_CONJUNCTION_QUERY,
		# only single concepts:       	'smwgQFeatures' => SMW_CONCEPT_QUERY,
		# anything but disjunctions:  	'smwgQFeatures' => SMW_ANY_QUERY & ~SMW_DISJUNCTION_QUERY,
		# The default is to support all basic features.
		#
		# @since 1.2
		##
		'smwgQFeatures' => SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_CONCEPT_QUERY | SMW_NAMESPACE_QUERY | SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY,
		# #

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
		'smwgQConceptCaching' => CONCEPT_CACHE_HARD, // Which concepts should be displayed only if available from cache?
		// CONCEPT_CACHE_ALL   -- show concept elements anywhere only if they are cached
		// CONCEPT_CACHE_HARD  -- show without cache if concept is not harder than permitted inline queries
		// CONCEPT_CACHE_NONE  -- show all concepts even without any cache
		// In any cases, caches will always be used if available.

		// Same as $smwgQFeatures but for concepts
		'smwgQConceptFeatures' => SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_NAMESPACE_QUERY |
		SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY | SMW_CONCEPT_QUERY,

		# #

		##
		# Predefined result formats for queries
		#
		# Array of available formats for formatting queries. Can be redefined in
		# the settings to disallow certain formats or to register extension formats.
		# To disable a format, do "unset($smwgResultFormats['template'])," Disabled
		# formats will be treated like if the format parameter had been omitted. The
		# formats 'table' and 'list' are defaults that cannot be disabled. The format
		# 'broadtable' should not be disabled either in order not to break Special:ask.
		##
		'smwgResultFormats' => [
			'table'      => 'SMW\Query\ResultPrinters\TableResultPrinter',
			'broadtable' => 'SMW\Query\ResultPrinters\TableResultPrinter',
			'list'       => 'SMW\Query\ResultPrinters\ListResultPrinter',
			'plainlist'  => 'SMW\Query\ResultPrinters\ListResultPrinter',
			'ol'         => 'SMW\Query\ResultPrinters\ListResultPrinter',
			'ul'         => 'SMW\Query\ResultPrinters\ListResultPrinter',
			'category'   => 'SMW\Query\ResultPrinters\CategoryResultPrinter',
			'embedded'   => 'SMW\Query\ResultPrinters\EmbeddedResultPrinter',
			'template'   => 'SMW\Query\ResultPrinters\ListResultPrinter',
			'count'      => 'SMW\Query\ResultPrinters\NullResultPrinter',
			'debug'      => 'SMW\Query\ResultPrinters\NullResultPrinter',
			'feed'       => 'SMW\Query\ResultPrinters\FeedExportPrinter',
			'csv'        => 'SMW\Query\ResultPrinters\CsvFileExportPrinter',
			'templatefile' => 'SMW\Query\ResultPrinters\TemplateFileExportPrinter',
			'dsv'        => 'SMW\Query\ResultPrinters\DsvResultPrinter',
			'json'       => 'SMW\Query\ResultPrinters\JsonResultPrinter',
			'rdf'        => 'SMW\Query\ResultPrinters\RdfResultPrinter'
		],
		# #

		##
		# Predefined aliases for result formats
		#
		# Array of available aliases for result formats. Can be redefined in
		# the settings to disallow certain aliases or to register extension aliases.
		# To disable an alias, do "unset($smwgResultAliases['alias'])," Disabled
		# aliases will be treated like if the alias parameter had been omitted.
		#
		# @since 1.8
		##
		'smwgResultAliases' => [
			'feed' => [ 'rss' ],
			'templatefile' => [ 'template file' ],
			'plainlist' => [ 'plain' ]
		],
		# #

		# #
		# Result printer features
		#
		# - SMW_RF_NONE
		# - SMW_RF_TEMPLATE_OUTSEP, #2022 (use the sep parameter as outer separator)
		#
		# @since 2.3
		##
		'smwgResultFormatsFeatures' => SMW_RF_TEMPLATE_OUTSEP,
		# #

		###
		# Handling of `RemoteRequest` features
		#
		# - SMW_REMOTE_REQ_SEND_RESPONSE allows Special:Ask to respond to remote requests in
		# combination with $smwgQuerySources and the `RemoteRequest`.
		#
		# - SMW_REMOTE_REQ_SHOW_NOTE shows a note for each remote requests so users are aware
		# that results retrieved from an external source.
		#
		# If `$smwgQuerySources` contains no entries then a remote request to a source
		# is not supported and only sources that are available through the setting
		# can be selected as remote source.
		#
		# @since 3.0
		# @default: SMW_REMOTE_REQ_SEND_RESPONSE | SMW_REMOTE_REQ_SHOW_NOTE
		##
		'smwgRemoteReqFeatures' => SMW_REMOTE_REQ_SEND_RESPONSE | SMW_REMOTE_REQ_SHOW_NOTE,
		# #

		###
		# -- FEATURE IS DISABLED --
		# If you want to import ontologies, you need to install RAP,
		# a free RDF API for PHP, see
		#     http://wifo5-03.informatik.uni-mannheim.de/bizer/rdfapi/index.html
		# The following is the path to your installation of RAP
		# (the directory where you extracted the files to) as seen
		# from your local filesystem. Note that ontology import is
		# highly experimental at the moment, and may not do what you
		# extect.
		#
		# @since 1.0
		##
		// 	'smwgRAPPath' => $smwgIP . 'libs/rdfapi-php',
		// 	'smwgRAPPath' => '/another/example/path/rdfapi-php',
		##

		###
		# List of Special:SemanticMediaWiki (or Special:SMWAdmin) features
		# https://www.semantic-mediawiki.org/wiki/Help:$smwgAdminFeatures
		#
		# - SMW_ADM_REFRESH: to initiate the repairing or updating of all wiki data
		# - SMW_ADM_SETUP: Allows running database installation and upgrade
		# - SMW_ADM_DISPOSAL: Allows access to the "Object ID lookup and disposal"
		#   feature and the "Outdated entities disposal"
		# - SMW_ADM_PSTATS: Allows updating property statistics
		# - SMW_ADM_FULLT: Allows rebuilding the fulltext search index
		# - SMW_ADM_MAINTENANCE_SCRIPT_DOCS: Show maintenance scripts documentation tab
		# - SMW_ADM_SHOW_OVERVIEW: Show the Overview tab
		#
		#   Maintenance alerts
		#
		# - SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN: Alerts when table optimization is
		#   overdue
		#
		# @since 2.5
		##
		'smwgAdminFeatures' =>
		SMW_ADM_REFRESH | SMW_ADM_SETUP | SMW_ADM_DISPOSAL | SMW_ADM_PSTATS | SMW_ADM_FULLT |
		SMW_ADM_MAINTENANCE_SCRIPT_DOCS | SMW_ADM_SHOW_OVERVIEW | SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN,
		# #

		###
		# Semantic MediaWiki uses various cache instances and types to improve access
		# and re-access to objects. `smwgMainCacheType` identifies the "main" type
		# to be used for a persitent storage to a vendor (SQL, memcache, redis etc.)
		# specific solution.
		#
		# `CACHE_ANYTHING` refers to settings available in `$wgMessageCacheType` or
		# `$wgParserCacheType` if they are set.
		#
		# @see https://www.semantic-mediawiki.org/wiki/Help:Caching
		# @see https://www.mediawiki.org/wiki/$wgMainCacheType
		#
		# @since 3.0
		# @default CACHE_ANYTHING
		##
		'smwgMainCacheType' => CACHE_ANYTHING,
		# #

		###
		# CacheTTL settings
		#
		# Defines time to live for in Semantic MediaWiki used cache instances and
		# requires $smwgMainCacheType to be set otherwise related settings will have
		# no effect.
		#
		# - special.wantedproperties TTL (in sec, or false to disable it) for caching
		#   the lookup on wanted property usage
		#
		# - special.unusedproperties TTL (in sec, or false to disable it) for caching
		#   the lookup on unused property usage
		#
		# - special.properties TTL (in sec, or false to disable it) for caching the
		#   lookup on property usage
		#
		# - special.statistics TTL (in sec, or false to disable it) for caching the
		#   lookup on statistics
		#
		# - api.browse TTL (in sec, or false to disable it) for the API browse module
		#   as general cache
		#
		# - api.browse.pvalue TTL (in sec, or false to disable it) for the API browse
		#   pvalue module when requesting property values
		#
		# - api.browse.psubject TTL (in sec, or false to disable it) for the API browse
		#   psubject module when requesting property subjects
		#
		# - api.task TTL (in sec, or false to disable it) for the API task module
		#
		# @since 1.9
		##
		'smwgCacheUsage' => [
			'special.wantedproperties' => 3600,
			'special.unusedproperties' => 3600,
			'special.properties' => 3600,
			'special.statistics' => 3600,
			'table.statistics' => 3600,
			'api.browse' => 3600,
			'api.browse.pvalue' => 3600,
			'api.browse.psubject' => 3600,
			'api.task'  => 3600,
			'api.table.statistics'  => 3600
		],
		# #

		###
		# Regulates task specific settings for the post-edit process.
		#
		# The main objective is to defer secondary updates until after the GET request
		# has been finalized so that resource requirements are part of an API request
		# (and not a GET) and hereby ensures that a client remains responsive
		# independent of the update workload.
		#
		# `run-jobs` specifies jobs that should be executed on a post-edit to run in a
		# timely manner independent of a users job scheduler environment. The number
		# indicates the expected number of jobs to be executed per request.
		#
		# `purge-page`
		#   `on-outdated-query-dependency` actively does a page purge via the API
		#   so that not only the parser cache is refreshed but also ensures that any
		#   newly annotation values (such as annotations depending on some query input)
		#   are stored and recomputed.
		#
		# @experimental
		#
		# `check-query` The display of query results and the storage of entities that
		# make up the results of a query are two distinct processes. The display
		# normally happens before the storage due to how the MW parser works meaning
		# that a query can only display the most recent results after a page has
		# been processed and rendered while the storage is being deferred (or in case
		# of an external store is influenced by the network lag).
		#
		# The `check-query` uses the `post-edit` event to run registered queries and
		# if necessary reloads the page (hereby refreshes the results) in case the
		# result is different by comparing the `result_hash` from before and after.
		# To determine the query state, the `post-edit` has to invoke the API (as
		# background task) which has to probe the query and to only run the query once
		# for the page that embeds the query, it is strongly recommended that this
		# option is only enabled together with:
		#   - the query cache (@see $smwgQueryResultCacheType) and
		#   - the query links store (@see $smwgEnabledQueryDependencyLinksStore)
		#
		# @since 3.0
		##
		'smwgPostEditUpdate' => [
			'check-query' => false,
			'run-jobs' => [
				'smw.fulltextSearchTableUpdate' => 1
			],
			'purge-page' => [
				'on-outdated-query-dependency' => true
			]
		],
		# #

		###
		# Features related to text and annotion parsing
		#
		# - SMW_PARSER_NONE
		#
		# - SMW_PARSER_STRICT: The default interpretation (strict) is to find a single
		#   triple such as [[property::value:partOfTheValue::alsoPartOfTheValue]] where
		#   in case the strict mode is disabled multiple properties can be assigned
		#   using a [[property1::property2::value]] notation but may cause value
		#   strings to be interpret unanticipated in case of additional colons.
		#
		# - SMW_PARSER_UNSTRIP: Support decoding (unstripping) of hidden text elements
		#   (e.g. `<nowiki>` as in `[[Has description::<nowiki>{{#ask: HasStripMarkers
		#   }}</nowiki>]]` etc.) within an annotation value (can only be stored together
		#   with a `_txt` type property).
		#
		# - SMW_PARSER_INL_ERROR: Should warnings be displayed in wikitexts right after
		#   the problematic input? This affects only semantic annotations, not warnings
		#   that are displayed by inline queries or other features. (was $smwgInlineErrors)
		#
		# - SMW_PARSER_HID_CATS: Switch to omit hidden categories (marked with
		#   __HIDDENCAT__) from the annotation process. Changing the setting requires
		#   to run a full rebuild to ensure hidden categories are discarded during
		#   the parsing process. (was $smwgShowHiddenCategories 1.9)
		#
		# - SMW_PARSER_LINV: Support parsing of "links in values" for annotations like
		#   [[SomeProperty::Foo [[link]] in [[Bar::AnotherValue]]]] (was $smwgLinksInValues
		#   with SMW_LINV_OBFU, SMW_LINV_PCRE is no longer available)
		#
		# @since 3.0
		##
		'smwgParserFeatures' => SMW_PARSER_STRICT | SMW_PARSER_INL_ERROR | SMW_PARSER_HID_CATS,
		# #

		##
		# Features or restrictions for specific DataValue types
		#
		# - SMW_DV_NONE
		#
		# - SMW_DV_PROV_REDI (PropertyValue) If a property is redirected to a different
		# target (Foo -> Bar) then follow it by default in order to allow query results
		# to be displayed equivalent for both queries without having to adjust
		# (or change) a query. This flag is mainly provided to restore backwards
		# compatibility where behaviour is not expected to be altered, nevertheless it is
		# recommended that the setting is enabled to improve user friendliness in terms
		# of query execution.
		#
		# - SMW_DV_MLTV_LCODE (MonolingualTextValue) is to require a language code in order
		# for a DV to be completed otherwise a MLTV can operate without a language code
		#
		# - SMW_DV_PVAP (Allows pattern) to allow regular expression pattern matching
		# when `Allows pattern` property is assigned to user-defined property
		#
		# - SMW_DV_WPV_DTITLE (WikiPageValue) is to allow requesting a lookup for a display
		# title and if present will be used as caption for the invoked subject
		#
		# - SMW_DV_PROV_DTITLE (PropertyValue) in combination with SMW_DV_WPV_DTITLE, If
		# enabled it will attempt to resolve a property label by matching it against a
		# possible assigned property "Display title of" value. For example, property
		# "Foo" has "Display title of" "hasFoolishFoo" where "hasFoolishFoo" is being
		# resolved as "Foo" when creating annotations. Currently, this uses an
		# uncached lookup and therefore is disabled by default to avoid a possible
		# performance impact (which has not been established or analyzed).
		#
		# - SMW_DV_PVUC (Uniqueness constraint) to specify that a property can only
		# assign a value that is unique in its literal representation (the state of
		# uniqueness for a value is established by the fact that it is assigned before
		# any other value of the same representation to a property).
		#
		# - SMW_DV_TIMEV_CM (TimeValue) to indicate the CalendarModel if is not a
		# CM_GREGORIAN
		#
		# - SMW_DV_NUMV_USPACE (Number/QuantityValue) to preserve spaces within
		# unit labels
		#
		# - SMW_DV_PPLB to support the use of preferred property labels
		#
		# - SMW_DV_PROV_LHNT (PropertyValue) to output a <sup>p</sup> hint marker on
		# properties that use a preferred label
		#
		# - SMW_DV_WPV_PIPETRICK WikiPageValue use a full pipe trick when rendering
		# its caption
		#
		# @since 2.4
		##
		'smwgDVFeatures' => SMW_DV_PROV_REDI | SMW_DV_MLTV_LCODE | SMW_DV_PVAP | SMW_DV_WPV_DTITLE | SMW_DV_TIMEV_CM | SMW_DV_PPLB | SMW_DV_PROV_LHNT,
		# #

		##
		# Fulltext search table options
		#
		# This setting directly influences how a ft table is created therefore change
		# the content with caution.
		#
		# - MySQL version 5.5 or later with only MyISAM and InnoDB storage engines
		# to support full-text search (according to sources)
		#
		# - MariaDB full-text indexes can be used only with MyISAM and Aria tables,
		# from MariaDB 10.0.5 with InnoDB tables and from MariaDB 10.0.15
		# with Mroonga tables (according to sources)
		#
		# - SQLite FTS3 has been available since version 3.5, FTS4 were added with
		# version 3.7.4, and FTS5 is available with version 3.9.0 (according to
		# sources), The setting allows to specify extra arguments after the module
		# engine such as array( 'FTS4', 'tokenize=porter' ).
		#
		# It is possible to extend the option description (MySQL 5.7+)  with
		# 'mysql' => array( 'ENGINE=MyISAM, DEFAULT CHARSET=utf8', 'WITH PARSER ngram' )
		#
		# @since 2.5
		##
		'smwgFulltextSearchTableOptions' => [
			'mysql'  => [ 'ENGINE=MyISAM, DEFAULT CHARSET=utf8' ],
			'sqlite' => [ 'FTS4' ]
		],
		# #

		##
		# List of indexable DataTypes
		#
		# - SMW_FT_BLOB property values of type Blob (Text)
		# - SMW_FT_URI property values of type URI
		# - SMW_FT_WIKIPAGE property values of type Page
		#
		# SMW_FT_WIKIPAGE has not been added as default value as no performance
		# impact analysis is available as to how indexing and search performance would
		# be affected by a wiki with a large pool of pages (10K+) or extended page
		# type value assignments on a full-text index.
		#
		# Enabling SMW_FT_WIKIPAGE will support the same search features (case
		# insensitivity, phrase matching etc.) as available for Text or URI values
		# when searches are executed using the ~/!~.
		#
		# @since 2.5
		# @default: SMW_FT_BLOB | SMW_FT_URI
		##
		'smwgFulltextSearchIndexableDataTypes' => SMW_FT_BLOB | SMW_FT_URI,
		# #

		###
		# Support to store a computed subject list that were fetched from the QueryEngine
		# (not the string result generated from a result printer) and improve general
		# page-loading time for articles that contain embedded queries and decrease
		# server load on query requests.
		#
		# It is recommended that `smwgEnabledQueryDependencyLinksStore` is enabled
		# to make use of automatic query results cache eviction.
		#
		# Enabling this option will render queries with a new ID to optimize queries
		# that result in the same signature (fingerprint) to lower the rate of cache
		# fragmentation.
		#
		# @since 2.5 (experimental)
		# @default: CACHE_NONE (== feature is disabled)
		##
		'smwgQueryResultCacheType' => CACHE_NONE,
		# #

		##
		# Per-pool entry limits for the in-memory caches SMW uses to look up
		# entity IDs during a single request.
		#
		# These caches let SMW avoid duplicate database queries for the same
		# titles and IDs while a page renders. On large or unusually rich pages,
		# the default sizes can fill up and force SMW to re-query entities it
		# already saw earlier in the same render. Raising a limit keeps more
		# entries resident at the cost of additional memory.
		#
		# To tune a single pool, override only that key — pools you don't list
		# keep their defaults:
		#
		#   $smwgEntityCacheSizes['entity.id'] = 5000;
		#
		# To assess whether tuning is needed, monitor the
		# `mediawiki.SemanticMediaWiki.inmemory_cache_hits_total` and
		# `mediawiki.SemanticMediaWiki.inmemory_cache_misses_total` metrics
		# emitted via MediaWiki's StatsFactory (Prometheus exporters
		# typically normalize the dots to underscores). These require
		# `$wgStatsTarget` and `$wgStatsFormat` to be configured (see
		# MediaWiki's stats documentation); from there metrics flow to a
		# StatsD endpoint, which can in turn be relayed to Prometheus through
		# standard tooling such as `statsd_exporter`. A consistently low hit
		# ratio on a specific pool indicates it would benefit from a higher
		# limit.
		#
		# Pools:
		# - entity.id           — title -> SMW ID
		# - entity.sort         — title -> sortkey
		# - entity.lookup       — SMW ID -> WikiPage data item
		# - propertytable.hash  — which property tables hold data for each entity
		# - warmup.byid         — IDs already prefetched in this request
		# - sequence.map        — SMW ID -> property sequence map
		# - redirect.source.lookup / redirect.target.lookup — redirect resolution
		# - count.map           — SMW ID -> auxiliary count map
		#
		# @since 7.0.0
		##
		'smwgEntityCacheSizes' => EntityIdManager::DEFAULT_CACHE_SIZES,
		# #

		##
		# Experimental settings
		#
		# Features enabled are considered stable but for any unforeseen behaviour, the
		# feature can be disabled to return to a previous working state avoiding
		# the need for hot-patching a system.
		#
		# After a certain in-production period, features will be moved permanently
		# to the desired target state and hereby automatically retires the related
		# setting.
		#
		# - SMW_QUERYRESULT_PREFETCH to use the prefetch method to retrieve row
		# related items for a `QueryResult`.
		#
		# - SMW_SHOWPARSER_USE_CURTAILMENT to use a short cut and circumventing the
		# `QueryEngine` and directly access the DB since `#show` will always only
		# request an output for one particular entity.
		#
		# @since 3.0
		##
		'smwgExperimentalFeatures' => SMW_QUERYRESULT_PREFETCH | SMW_SHOWPARSER_USE_CURTAILMENT,
		# #

		##
		# Special:Ask form submit method
		#
		# - SMW_SASK_SUBMIT_POST uses `post` as submit method, allows to jump
		#   directly to the search result but will not produce any copyable URL
		#   string (use the result bookmark button instead)
		#
		# - SMW_SASK_SUBMIT_GET uses `get` as submit method and was the default
		#   until 2.5, is not able to jump the search result directly after a submit
		#
		# - SMW_SASK_SUBMIT_GET_REDIRECT uses `get` as submit method and provides
		#   the means to directly jump to the search result after a submit but
		#   requires an extra HTTP request to follow a redirect
		#
		# @since 3.0
		# @default SMW_SASK_SUBMIT_POST
		##
		'smwgSpecialAskFormSubmitMethod' => SMW_SASK_SUBMIT_POST,
		# #

		##
		# Lookup and display of constraint errors
		#
		# A convenience function to provided users with a help to quickly identify
		# which constraints violation are currently exists for a viewed subject.
		#
		# - `SMW_CONSTRAINT_ERR_CHECK_NONE` disables the check and display via the
		#    page indicator
		# - `SMW_CONSTRAINT_ERR_CHECK_MAIN` will only check the main subject
		# - `SMW_CONSTRAINT_ERR_CHECK_ALL` will check the main subject and all
		#    subobjects attached to the main subject
		#
		# The constraint error lookup is cached therefore no negative performance
		# impact is expected when viewing a page repeatedly.
		#
		# @since 3.1
		# @default SMW_CONSTRAINT_ERR_CHECK_ALL
		##
		'smwgCheckForConstraintErrors' => SMW_CONSTRAINT_ERR_CHECK_ALL,
		# #

		##
		# THE FOLLOWING SETTINGS AND SUPPORT FUNCTIONS ARE EXPERIMENTAL!
		#
		# Please make you read the Readme.md (see the Elastic folder) file first
		# before enabling the ElasticStore and its settings!
		##

		##
		# ElasticStore settings
		#
		# Supported options and settings required by the ElasticStore.
		#
		# This setting provides documentation and default values for the ElasticStore
		# and its use in an ES cluster.
		#
		# @since 3.0
		##
		'smwgElasticsearchConfig' => [
			'index_def' => [
				// The complete name will be auto-generated from
				// "smw-...-" + WikiMap::getCurrentWikiId() to avoid indicies among
				// different wikis being corrupted when sharing an ES instance.
				'data' => $smwgIP . '/data/elastic/smw-data-standard.json',
				'lookup' => $smwgIP . '/data/elastic/smw-lookup.json'
			],
			'connection' => [
				'quick_ping' => true,

				// Number of times the client tries to reconnect before throwing an
				// exception
				'retries' => 2,

				// Controls how long curl should wait for the entire request to finish
				'timeout' => 30,

				// Controls how long curl should wait for the "connect" phase to finish
				'connect_timeout' => 30
			],
			'settings' => [
				'data' => [
					// Setting names match those from ES, any misspelling or
					// incorrect setting will cause an error in ES
					'index.mapping.total_fields.limit' => 9000,
					'index.max_result_window' => 50000
				]
			],
			'indexer' => [

				// Allows to index unstructured, unprocessed raw text from a revision
				'raw.text' => false,

				// Experimental feature to investigate the use of the ingest pipline
				// to index uploaded files and make that content available to
				// QueryEngine during a wide proximity search (e.g. [[in:fox jumps]])
				// Requires https://www.elastic.co/guide/en/elasticsearch/plugins/master/ingest-attachment.html
				'experimental.file.ingest' => false,

				// If the replication encounters an `illegal_argument_exception` from
				// the ES cluster, rethrow it as exception to ensure it doesn't get
				// unnoticed as it is likely to require intervention due to issues
				// like "Limit of total fields ... has been exceeded" etc.
				'throw.exception.on.illegal.argument.error' => true,

				// Number of max. retries before the recovery job will resign from
				// trying any more attempts to update the ES cluster. This is to
				// prevent jobs from invoking themselves indefinitely.
				'job.recovery.retries' => 5,

				// Number of max. retries before the file ingest
				'job.file.ingest.retries' => 3,

				// Compare and report the status of the replication on a page view
				// about entities hereby allowing users to immediately comprehend a
				// possible discrepancy between the stored on-wiki data and the data
				// replicated to Elasticsearch.
				'monitor.entity.replication' => true,
				'monitor.entity.replication.cache_lifetime' => 3600,

				// DataItems (Blob, Uri, Page etc.) are transformed in exactly the
				// same way as done by the SQLStore `DataItemHandler` before being
				// added to the storage layer (includes transformations like
				// htmlspecialchars_decode, rawurldecode etc.) to ensure that data
				// have a test compatibility with the SQLStore.
				'data.sqlstore_compatibility' => true
			],
			'query' => [

				// If for some reason no connection to the ES cluster could be
				// established, use the SQLStore QueryEngine as fallback.
				'fallback.no_connection' => false,

				// @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/_profiling_queries.html
				'profiling' => false,

				// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-validate.html
				// Part of the `format=debug` to validate potentially expensive queries
				// without executing them.
				'debug.explain' => true,

				// During the debug output, display details about which description was
				// when resolved in connection with a query
				'debug.description.log' => true,

				// #3698
				// Restrict the length of an individual input value to avoid a potential
				// "... "java.lang.IllegalArgumentException:input automaton is too
				// large: 1001 ..."
				'maximum.value.length' => 500,

				// When `!...` is used make sure that the condition is only applied
				// on entities where the property exists together with the negated
				// value condition otherwise the ! condition becomes unrestricted
				'must_not.property.exists' => true,

				// When sorting on a particular property, enforce that the property
				// exists as an assignment to a selected entity. The default setting
				// matches the SQLStore behaviour. (See also #2823)
				'sort.property.must.exists' => true,

				// Sort by score (aka relevancy)
				//
				// Defines the name of the sortkey (as covention) that is used to sort
				// results by scores computed by ES (based on Term Frequency, Inverse
				// Document Frequency etc.).
				//
				// Output results with the highest score first:
				//
				// {{#ask: [[Category:Foo]]
				//  |sort=es.score
				//  |order=desc
				// }}
				//
				// @see https://www.compose.com/articles/how-scoring-works-in-elasticsearch/
				'score.sortfield' => 'es.score',

				// The `+` and `-` symbols will be interpret as boolean operators so that
				// things like `+foo, -bar` mean `+` (this term must be present) and
				// `-`(this term must not be present). If they need to be part of
				// the search string itself then it is required to escape them like
				// `\+`.
				'query_string.boolean.operators' => true,

				// ES works different with text elements compared to the SQL interface
				// (and its DSL query logic) therefore we try to modify some
				// common scenarios and alter strings (and boolean operators) to pass
				// most use cases from the SQLStore integration test suite and hereby
				// allows to be compatible with the SMW SQL answering behaviour.
				//
				// ES has reserved characters `+ - = && || > < ! ( ) { } [ ] ^ " ~ *
				// ? : \ /` and this mode will automatically encode them which of course
				// limits the ability to use them as part of the native boolean operators
				// within the query expression.
				//
				// In case the mode is disabled, the user has to make sure to follow
				// the rules set forth by ES in connection with the query_string
				// parser.
				//
				// @see https://www.elastic.co/guide/en/elasticsearch/reference/6.1/query-dsl-query-string-query.html
				'compat.mode' => true,

				// Size (limit) of the subquery construct which is executed as separated
				// search request.
				'subquery.size' => 10000,

				// Sorting and scoring of subqueries is generally not necessary therefore
				// we use the `constant_score` filter context to executed queries which
				// helps to elevate the use of the filter caching.
				//
				// @see https://www.elastic.co/guide/en/elasticsearch/guide/current/filter-caching.html
				'subquery.constant.score' => true,

				// Defines the threshold for a result size as to when those should be
				// stored in a special lookup index to facilitate the ES terms lookup
				// feature (which requires to write and refresh the specific index
				// element ad-hoc before it can be used by the "source" query).
				//
				// The more often subqueries are used (or reused) the lower the threshold
				// should be set as it directly impacts performance.
				//
				// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html
				'subquery.terms.lookup.result.size.index.write.threshold' => 200,

				// Intermediary optimization to allow for a subquery (executed as part
				// of a "source" query and uses the special lookup index) to be reused
				// (aka cached) for any succeeding query executions for the time frame
				// set and hereby avoids fetching subquery results on repeating
				// requests especially when the source query changes its limit and
				// offset parameters frequently.
				//
				// Only subqueries with `subquery.terms.lookup.result.size.index.write.threshold`
				// will be available to make use of this index cache.
				//
				// The cache is static and will not apply any eviction strategy which
				// means that in case of additional values that would normally invalidate
				// a subquery result, results remain static for the time frame set.
				//
				// Caching becomes crucial when a subqery terms lookup contains 1000+
				// of matching documents as we avoid executing the subquery and if
				// available directly resuse the terms lookup index.
				//
				// @default 1h
				'subquery.terms.lookup.cache.lifetime' => 60 * 60,

				// A concept as (aka dynamix category) is defined using a query and
				// will return a list of matchable IDs therefore to improve performance
				// during the lookup of those entities use the lookup index to retrieve
				// precomputed results.
				'concept.terms.lookup' => true,

				// Threshold when to store and use the index lookup
				'concept.terms.lookup.result.size.index.write.threshold' => 10,

				// Threshold when to store and use the index lookup
				// It is similar to `$smwgQConceptCacheLifetime`
				//
				// @default 1h
				'concept.terms.lookup.cache.lifetime' => 60 * 60,

				// In case search terms contains CJK terms, remove `*` prefix/affix
				// from a search request in an effort to best match single characters
				// that created as part of the standard analyzer. Use a phrase match
				// instead of a wildcard proximity.
				//
				// This setting may be disabled when using a different index definition
				// (e.g. ICU).
				'cjk.best.effort.proximity.match' => true,

				// Specifies that a wide proximity search (e.g. [[~~Foo bar]] or
				// [[in:Foo bar]]) is executed as a match_phrase search meaning that
				// that all elements of the query string need to be present in the
				// order of the query
				'wide.proximity.as.match_phrase' => true,

				// Fields to be used for a wide proximity search with some being
				// boosted to weight higher in relevance. For example, when `Foo` is
				// part of the title it relevance is boosted by 8 (i.e. count more
				// towards the relevance score) as when it would only appear in one
				// of the text fields.
				//
				// It means that [[in:Foo]] where titles match `Foo` will be
				// assigned a 5 times higher score and therefore appear higher in a
				// list when sorted by relevancy.
				'wide.proximity.fields' => [
					'subject.title^8',
					'text_copy^5',
					'text_raw',
					'attachment.title^3',
					'attachment.content'
				],

				// By default, the URI fields are considered case sensitive similar to
				// what RFC 3986 " ... the scheme and host are case-insensitive and
				// therefore should be normalized to lowercase ... the other generic
				// syntax components are assumed to be case-sensitive unless ",
				// RFC 2616 " ...  comparing two URIs to decide if they match or
				// not, a client SHOULD use a case-sensitive octet-by-octet
				// comparison of the entire URIs ..."
				//
				// The setting applies to the EQ, LIKE, and NLIKE match.
				'uri.field.case.insensitive' => false,

				// By default, equality searches (e.g. [[Has text:Foo]], [Has text:foo]])
				// are case senstive but as in case of the SMW_FIELDT_CHAR_NOCASE,
				// the search can be altered to find case insensitive matches as well.
				//
				// The default setting matches the SQLStore standard behaviour and
				// uses the faster ES `term` search instead of a `match` variant
				// which would become necessary for any analyzed field.
				//
				// @see https://www.elastic.co/guide/en/elasticsearch/guide/current/term-vs-full-text.html
				'text.field.case.insensitive.eq.match' => false,

				// [[~Foo/Bar/*]] vs. [[~FOO/bar/*]]
				// [[Has page::~Foo bar/bar/*]]
				'page.field.case.insensitive.proximity.match' => true,

				// Allows to retrieve text fragments from ES for query request and
				// depending on the type selected can require more query time.
				//
				// Available types are `plain`, `unified`, and `fvh`. The `fvh` type
				// requires text fields to have the `term_vector` with `with_positions_offsets`
				// enabled.
				//
				// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-highlighting.html#plain-highlighter
				// @see https://www.elastic.co/guide/en/elasticsearch/reference/current/term-vector.html
				'highlight.fragment' => [ 'number' => 1, 'size' => 250, 'type' => false ]
			]
		],
		# #

	];
} )();
