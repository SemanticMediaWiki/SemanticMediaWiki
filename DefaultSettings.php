<?php

/**
 * DO NOT EDIT!
 *
 * The following default settings are to be used by the extension itself,
 * please modify settings in the LocalSettings file.
 *
 * Most settings should be make  between including this file and the call
 * to enableSemantics(). Exceptions that need to be set before are
 * documented below.
 *
 * @codeCoverageIgnore
 */
if ( !defined( 'MEDIAWIKI' ) ) {
  die( "This file is part of the Semantic MediaWiki extension. It is not a valid entry point.\n" );
}

return [

	###
	# This is the path to your installation of Semantic MediaWiki as seen on your
	# local filesystem. Used against some PHP file path issues.
	#
	# @since 1.0
	##
	'smwgIP' => __DIR__ . '/',
	#
	# @since 2.5
	##
	'smwgExtraneousLanguageFileDir' => __DIR__ . '/i18n/extra',
	'smwgServicesFileDir' => __DIR__ . '/src/Services',
	'smwgResourceLoaderDefFiles' => [ 'smw' => __DIR__ . '/res/Resources.php' ],
	'smwgMaintenanceDir' => __DIR__ . '/maintenance',
	'smwgTemplateDir' => __DIR__ . '/data/template',
	##

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
	# @since 3.0
	##
	'smwgConfigFileDir' => __DIR__,
	##

	###
	# Upgrade key
	#
	# This key verifies that a correct upgrade (update.php/setupStore.php) path
	# was selected and hereby ensures a consistent DB setup.
	#
	# Whenever a DB table change occurs, modify the key value (e.g. `smw:20...`)
	# to reflect the requirement for the client to follow the processes as
	# outlined in the installation manual.
	#
	# Once the installer is run, the `.smw.json` will be updated and no longer
	# cause any exception.
	#
	# @since 3.0
	##
	'smwgUpgradeKey' => 'smw:2019-01-19',
	##

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
	'smwgImportFileDirs' => [ 'default' => __DIR__ . '/data/import' ],
	'smwgImportReqVersion' => 1,
	##

	###
	# Semantic MediaWiki's operational state
	#
	# It is expected that enableSemantics() is used to enable SMW otherwise it is
	# disabled by default. disableSemantics() will also set the state to disabled.
	#
	# @since 2.4
	##
	'smwgSemanticsEnabled' => false,
	##

	###
	# CompatibilityMode is to force SMW to work with other extensions that may impact
	# performance in an unanticipated way or may contain potential incompatibilities.
	#
	# @since 2.4
	##
	'smwgEnabledCompatibilityMode' => false,
	##

	###
	# Use another storage backend for Semantic MediaWiki. The default is suitable
	# for most uses of SMW.
	#
	# @since 0.7
	##
	'smwgDefaultStore' => "SMWSQLStore3",
	##

	##
	# Debug logger role
	#
	# A role (developer, user, production) defines the detail of information
	# (granularity) that are expected to be logged. Roles include:
	#
	# - `developer` outputs any loggable event produced by SMW
	# - `user` outputs certain events deemed important
	# - `production` outputs a minimal set of events produced by SMW
	#
	# Logging only happens in case `$wgDebugLogFile` or `$wgDebugLogGroups`
	# are actively maintained.
	#
	# @see https://www.mediawiki.org/wiki/Manual:How_to_debug#Logging
	#
	# @since 3.0
	# @default production
	##
	'smwgDefaultLoggerRole' => 'production',
	##

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
	# - DB_SLAVE or DB_REPLICA (1.28+)
	# - DB_MASTER
	#
	# @since 2.5.3
	##
	'smwgLocalConnectionConf' => [
		'mw.db' => [
			'read'  => DB_SLAVE,
			'write' => DB_MASTER
		],
		'mw.db.queryengine' => [
			'read'  => DB_SLAVE,
			'write' => DB_MASTER
		]
	],
	##

	###
	# Configure SPARQL database connection for Semantic MediaWiki. This is used
	# when SPARQL-based features are enabled, e.g. when using SMWSparqlStore as
	# the $smwgDefaultStore.
	#
	# The default class SMWSparqlDatabase works with many databases that support
	# SPARQL and SPARQL Update. Three different endpoints (service URLs) are given
	# - query (reading queries like SELECT)
	# - update (SPARQL Update queries), and
	# - data (SPARQL HTTP Protocol for Graph Management).
	#
	# The query endpoint is necessary, but the update and data endpoints can be
	# omitted if not supported.
	#
	# This will lead to reduced functionality (e.g. the SMWSparqlStore will not
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
	##

	###
	#
	# The default graph is similar to a database name in relational databases. It
	# can be set to any URI (e.g. the main page uri of your wiki with
	# "	#graph" appended). Leaving the default graph URI empty only works if the
	# store is configure to use some default default graph or if it generally
	# supports this. Different wikis should normally use different default graphs
	# unless there is a good reason to share one graph.
	#
	# @since 1.7
	##
	'smwgSparqlDefaultGraph' => '',
	##

	##
	# Sparql repository connector
	#
	# Identifies a pre-deployed repository connector that is ought to be used together
	# with the SPARQLStore.
	#
	# List of standard connectors ($smwgSparqlCustomConnector will have no effect):
	# - '4store'
	# - 'blazegraph'
	# - 'fuseki'
	# - 'sesame'
	# - 'virtuoso'
	#
	# In case `$smwgSparqlRepositoryConnector` is maintained with 'custom',
	# the `$smwgSparqlCustomConnector` is expected to contain a custom class
	# implementing the ncessary interface (see `SMWSparqlDatabase`).
	#
	# `$smwgSparqlCustomConnector` is only used for the definition of a custom
	# connector.
	#
	# @since 2.0
	# @default default, meaning that the default (aka generic) connector is used
	##
	'smwgSparqlRepositoryConnector' => 'default',
	##

	##
	# Sparql cutstom connector
	#
	# In case `$smwgSparqlRepositoryConnector` is maintained with 'custom',
	# the `$smwgSparqlCustomConnector` is expected to contain a custom class
	# implementing the ncessary interface (see `SMWSparqlDatabase`).
	#
	# `$smwgSparqlCustomConnector` is only used for the definition of a custom
	# connector.
	#
	# @since 2.0
	##
	'smwgSparqlCustomConnector' => 'SMWSparqlDatabase',
	##

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
	#   maintained in $smwgEntityCollation. It is not enabled by default as the
	#   `uca-*` collation generates a UTF-8 string that contains unrecognized
	#   UTF codepoints that may not be understood by the back-end hence the
	#   Collator prevents and armors those unrecognized characters by replacing
	#   them with a ? to avoid a cURL communication failure but of course this
	#   means that not all elements of the sort string can be transfered to the
	#   back-end and can therefore cause a sorting distortion for close matches
	#   as in case of for example "Ennis, Ennis Hill, Ennis Jones, Ennis-Hill,
	#   Ennis-London"
	#
	# - SMW_SPARQL_QF_NOCASE to support case insensitive pattern matches
	#
	# Please check with your repository provider whether SPARQL 1.1 is fully
	# supported or not, and if not SMW_SPARQL_QF_NONE should be set.
	#
	# @since 2.3
	##
	'smwgSparqlQFeatures' => SMW_SPARQL_QF_REDI | SMW_SPARQL_QF_SUBP | SMW_SPARQL_QF_SUBC,
	##

	##
	# @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/1306
	#
	# Setting to explicitly force a CURLOPT_HTTP_VERSION for the endpoint communication
	# and should not be changed unless an error as in 	#1306 was encountered.
	#
	# @see http://curl.haxx.se/libcurl/c/CURLOPT_HTTP_VERSION.html reads "... libcurl
	# to use the specific HTTP versions. This is not sensible to do unless you have
	# a good reason.""
	#
	# @since 2.3
	# @default false === means to use the default as determined by cURL
	##
	'smwgSparqlRepositoryConnectorForcedHttpVersion' => false,
	##

	##
	# Property replication exemption list
	#
	# Listed properties will be exempted from the replication process for a
	# registered SPARQL repository.
	#
	# @since 2.5
	# @default array
	##
	'smwgSparqlReplicationPropertyExemptionList' => [],
	##

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
	##

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

	###
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
	##

	###
	# Same as $smwgShowFactbox but for the edit mode with same possible values.
	#
	# @since 1.0
	##
	'smwgShowFactboxEdit' => SMW_FACTBOX_NONEMPTY,
	##

	###
	# Compact infolink support
	#
	# Special:Browse, Special:Ask, and Special:SearchByProperty links can contain
	# arbitrary text elements and therefore become difficult to transfer when its
	# length exceeds a certain character length.
	#
	# The experimental feature of a compact link will be encoded and compressed to
	# ensure that it can be handled more easily when referring to it as an URL
	# representation.
	#
	# It is not expected to be used as a short-url service, yet in some instances
	# the generate URL can be comparatively shorter than the plain URL.
	#
	# The generated link has no security relevance therefore is is not
	# cryptographically hashed or secure and should not be seen as such, it is
	# foremost to "compact" an URL address.
	#
	# @since 3.0
	# @default true
	##
	'smwgCompactLinkSupport' => false,
	##

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
	##

	###
	# Settings for recurring events, created with the 	#set_recurring_event parser
	# function: the default number of instances defined, if no end date is set,
	# and the maximum number that can be defined, regardless of end date.
	#
	# @since 1.4.3
	##
	'smwgDefaultNumRecurringEvents' => 100,
	'smwgMaxNumRecurringEvents' => 500,
	##

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
	##

	###
	# Should the search by property special page display nearby results when there
	# are only a few results with the exact value? Switch this off if this page has
	# performance problems.
	#
	# @since 2.1 enabled default types, to disable the functionality either set the
	# variable to array() or false
	##
	'smwgSearchByPropertyFuzzy' => [ '_num', '_txt', '_dat', '_mlt_rec' ],
	##

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
	##

	###
	# Property page to limit the query request for individual values
	#
	# How many values should at most be displayed for a page on the  Property
	# page and if large values are desired, consider reducing
	# $smwgPropertyPagingLimit for better performance.
	#
	# @since 1.3
	##
	'smwgMaxPropertyValues' => 3,
	##

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
	##

	###
	# Settings for inline queries ({{#ask:...}}) and for semantic queries in
	# general. This can especially be used to prevent overly high server-load due
	# to complex queries. The following settings affect all queries, wherever they
	# occur.
	#
	# @since 1.0
	##
	'smwgQEnabled' => true,   // (De)activates all query related features and interfaces
	'smwgQMaxLimit' => 10000, // Max number of results *ever* retrieved, even when using special query pages.
	#
	# @since 1.5
	##
	'smwgIgnoreQueryErrors' => true, // Should queries be executed even if some errors were detected?
						// A hint that points out errors is shown in any case.
	##
	#
	# @since 1.0
	##
	'smwgQSubcategoryDepth' => 10, // Restrict level of sub-category inclusion (steps within category hierarchy)
	'smwgQSubpropertyDepth' => 10, // Restrict level of sub-property inclusion (steps within property hierarchy)
					// (Use 0 to disable hierarchy-inferencing in queries)
	'smwgQEqualitySupport' => SMW_EQ_SOME, // Evaluate #redirects as equality between page names, with possible
						// performance-relevant restrictions depending on the storage engine
	// 'smwgQEqualitySupport' => SMW_EQ_FULL, // Evaluate #redirects as equality between page names in all cases
	// 'smwgQEqualitySupport' => SMW_EQ_NONE, // Never evaluate #redirects as equality between page names
	'smwgQDefaultNamespaces' => null, // Which namespaces should be searched by default?
						// (value NULL switches off default restrictions on searching -- this is faster)
						// Example with namespaces: 'smwgQDefaultNamespaces' => array(NS_MAIN, NS_FILE)

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
	# @since 3.0
	##
	'smwgQSortFeatures' => SMW_QSORT | SMW_QSORT_RANDOM,
	##

	###
	# List of comparator characters
	#
	# Comparators supported by queries with available entries being:
	#
	#  < (smaller than) if $smwStrictComparators is false, it's actually smaller
	#    than or equal to
	#  > (greater than) if $smwStrictComparators is false, it's actually bigger
	#    than or equal to
	#  ! (unequal to)
	#  ~ (pattern with '*' as wildcard)
	#  !~ (not a pattern with '*' as wildcard, only for Type:String, need to be
	#    placed before ! and ~ to work correctly)
	#  ≤ (smaller than or equal to)
	#  ≥ (greater than or equal to)
	#
	# Extra compartors that in case of an enabled full-text index uses the primary
	# LIKE/NLIKE match operation with operators being:
	#
	#  like: to express LIKE use
	#  nlike: to express NLIKE use
	#
	# If unsupported comparators are used, they are treated as part of the
	# queried value.
	#
	# @since 1.0
	##
	'smwgQComparators' => '<|>|!~|!|~|≤|≥|<<|>>|~=|like:|nlike:|in:|not:|phrase:',
	##

	###
	# Sets whether the > and < comparators should be strict or not. If they are strict,
	# values that are equal will not be accepted.
	#
	# @since 1.5.3
	##
	'smwStrictComparators' => false,

	// To be used starting with 3.x (due to misspelling)
	'smwgQStrictComparators' => false,
	##

	###
	# Further settings for queries. The following settings affect inline queries
	# and querying special pages. Essentially they should mirror the kind of
	# queries that should immediately be answered by the wiki, using whatever
	# computations are needed.
	#
	# @since 1.0
	##
	'smwgQMaxSize' => 16, // Maximal number of conditions in queries, use format=debug for example sizes
	'smwgQMaxDepth' => 4, // Maximal property depth of queries, e.g. [[rel::<q>[[rel2::Test]]</q>]] has depth 2
	##

	###
	# Expensive threshold
	#
	# The threshold defined in seconds denotes the ceiling as to when a #ask or
	# #show call is classified as expensive and will count towards the
	# $smwgQExpensiveExecutionLimit setting.
	#
	# @since 3.0
	# @default 10
	##
	'smwgQExpensiveThreshold' => 10,
	##

	###
	# Limit of expensive #ask/#show functions
	#
	# The limit will count all classified #ask/#show parser functions and restricts
	# further use on pages that exceed that limit.
	#
	# @since 3.0
	# @default false (== no limit)
	##
	'smwgQExpensiveExecutionLimit' => false,
	##

	###
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
	##

	###
	# Filter duplicate query segments
	#
	# Experimental feature that allows to filter duplicate query segments from the
	# query build process to eliminate computational effort for segments that
	# represent that same query signature.
	#
	# @since 2.5
	# @default: false
	##
	'smwgQFilterDuplicates' => false,
	##

	###
	# Settings about printout of (especially inline) queries:
	#
	# @since 1.0
	##
	'smwgQDefaultLimit' => 50,      // Default number of rows returned in a query. Can be increased with limit=num in #ask
	'smwgQMaxInlineLimit' => 500,   // Max number of rows ever printed in a single inline query on a single page.
	'smwgQPrintoutLimit'  => 100,   // Max number of supported printouts (added columns in result table, ?-statements)
	'smwgQDefaultLinking' => 'all', // Default linking behavior. Can be one of "none", "subject" (first column), "all".
	#
	# @since 2.1
	##
	'smwgQUpperbound' => 5000,      // Max number of rows ever printed in a single inline query on a single page with an offset.
	##

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
	'smwgQConceptMaxSize' => 20, // Same as $smwgQMaxSize, but for concepts
	'smwgQConceptMaxDepth' => 8, // Same as $smwgQMaxDepth, but for concepts

	// Same as $smwgQFeatures but for concepts
	'smwgQConceptFeatures' => SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_NAMESPACE_QUERY |
								SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY | SMW_CONCEPT_QUERY,

	// Cache life time in minutes. If a concept cache exists but is older than
	// this, SMW tries to recompute it, and will only use the cache if this is not
	// allowed due to settings above:
	'smwgQConceptCacheLifetime' => 24 * 60,
	##

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
	##

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
	##

	##
	# Result printer features
	#
	# - SMW_RF_NONE
	# - SMW_RF_TEMPLATE_OUTSEP, #2022 (use the sep parameter as outer separator)
	#
	# @since 2.3
	##
	'smwgResultFormatsFeatures' => SMW_RF_TEMPLATE_OUTSEP,
	##

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
	##

	###
	#
	# Predefined list of sources that can return query results
	#
	# Array of available sources for answering queries. Can be redefined in
	# the settings to register new sources (usually an extension will do so
	# on installation). Unknown source will be rerouted to the local wiki.
	# Note that the basic installation comes with no additional source besides
	# the local source (which in turn cannot be disabled or set explicitly).
	#
	# A query class handler is required to implement the `QueryEngine` interface
	# and if it needs to be aware of the store, it should also implement the
	# `StoreAware` interface.
	#
	# @since 1.4.3
	##
	'smwgQuerySources' => [
	//	'local'      => '',
	//	'mw-wiki-foo' => [ '\SMW\Query\RemoteRequest', 'url' => 'http://example.org/wiki/index.php' ],
	],
	##

	### Default property type
	# Undefined properties (those without pages or whose pages have no "has type"
	# statement) will be assumed to be of this type. This is an internal type id.
	# See the file languages/SMW_LanguageXX.php to find what IDs to use for
	# datatpyes in your language. The default corresponds to "Type:Page".
	#
	# @since 1.1.2
	##
	'smwgPDefaultType' => '_wpg',
	##

	###
	# The maximal number that SMW will normally display without using scientific exp
	# notation. The deafult is rather large since some users have problems understanding
	# exponents. Scineitfic applications may prefer a smaller value for concise display.
	#
	# @since 1.4.3
	##
	'smwgMaxNonExpNumber' => 1000000000000000,
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
	#
	# @since 1.1.2
	##
	'smwgEnableUpdateJobs' => true,
	##

	###
	# JobQueue watchlist
	#
	# This setting allows to display a personal bar link that shows the queue
	# sizes for listed jobs. The information presented is fetched from the
	# MediaWiki API and might be slightly inaccurate but should allow to make
	# assumptions as to where the system needs attention.
	#
	# @see https://www.mediawiki.org/wiki/Manual:Job_queue#Special:Statistics
	#
	# To make this feature available, assign a simple list to the setting as in:
	#
	# $GLOBALS['smwgJobQueueWatchlist'] = [
	#	'smw.update',
	#	'smw.parserCachePurge',
	#	'smw.fulltextSearchTableUpdate',
	#	'smw.changePropagationUpdate'
	# ]
	#
	# Information are not displayed unless a user enables the setting in his or
	# her preference setting.
	#
	# @since 3.0
	# @default disabled (empty array)
	##
	'smwgJobQueueWatchlist' => [],
	##

	###
	# List of enabled special page properties.
	#
	# - `_MDAT` Modification date is enabled by default for backward compatibility.
	# - `_TRANS` Add annotations (language, source etc. ) when a page is
	#   indentified as translation page (as done by the Translation extension)
	# - `_ATTCH_LINK` tracks embedded files and images
	#
	#  Extend array to enable other properties:
	#     $smwgPageSpecialProperties[ => '_CDAT',
	# Or:
	#     array_merge( $smwgPageSpecialProperties, array( '_CDAT' ) ),
	# Or rewrite entire array:
	#     	'smwgPageSpecialProperties' => array( '_MDAT', '_CDAT' ),
	#
	# However, DO NOT use `+=' operator! This DOES NOT work:
	#     $smwgPageSpecialProperties += array( '_MDAT' ),
	#
	# @since 1.7
	##
	'smwgPageSpecialProperties' => [ '_MDAT' ],
	##

	###
	# Change propagation watchlist
	#
	# Properties (usually given as internal ids or DB key versions of property
	# titles) that are relevant for declaring the behavior of a property P on a
	# property page in the sense that changing their values requires that all
	# pages that use P must be processed again.
	#
	# For example, if _PVAL (allowed values) for a property change, then pages
	# must be processed again. This setting is not normally changed by users but
	# by extensions that add new types that have their own additional declaration
	# properties.
	#
	# @since 1.5
	##
	'smwgChangePropagationWatchlist' => [
		'_PVAL', '_LIST', '_PVAP', '_PVUC', '_PDESC', '_PPLB', '_PREC', '_PDESC',
		'_SUBP', '_SUBC', '_PVALI'
	],
	##

	##
	# Change propagation protection
	#
	# An administrative intervention to disable the protection for an active change
	# propagation.
	#
	# @since 3.0
	# @default true
	##
	'smwgChangePropagationProtection' => true,
	##

	###
	# By default, DataTypes (Date, URL etc.) are registered with a corresponding
	# property of the same name to match the expected semantics. Yet, users can
	# decide to change the behaviour by exempting listed DataTypes from the property
	# registration process.
	#
	# @since 2.5
	##
	'smwgDataTypePropertyExemptionList' => [
		'Record',
		'Reference',
		'Keyword'
	],
	##

	##
	# Default output formatter
	#
	# Users who want to alter the default output for a specific type can do so by
	# setting a specify default formatter.
	#
	# The expected form is:
	#
	# [ <_typeID> => '<Formatter>' ] OR
	# [ <typeName> => '<Formatter>' ] OR
	# [ <propertyName> => '<Formatter>' ]
	#
	# Only valid formatters will be considered for an individual type, no
	# errors or exceptions are raised in case of an improper formatter.
	#
	# The formatter is applied to values displayed on special pages
	# as well.
	#
	# @since 3.0
	# @default: []
	##
	'smwgDefaultOutputFormatters' => [
		// '_dat' => 'LOCL',
		// 'Boolean' => 'tick',
	],
	##

	// some default settings which usually need no modification

	###
	# -- FEATURE IS DISABLED --
	# Setting this to true allows to translate all the labels within
	# the browser GIVEN that they have interwiki links.
	#
	# @since 0.7
	##
	'smwgTranslate' => false,
	##

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
	#
	# - SMW_ADM_REFRESH: to initiate the repairing or updating of all wiki data
	# - SMW_ADM_SETUP: restrict to "Database installation and upgrade"
	# - SMW_ADM_DISPOSAL: restrict access to the "Object ID lookup and disposal"
	#   feature and the "Outdated entities disposal"
	# - SMW_ADM_PSTATS: Property statistics update
	# - SMW_ADM_FULLT:
	#
	# @since 2.5
	##
	'smwgAdminFeatures' => SMW_ADM_REFRESH | SMW_ADM_SETUP | SMW_ADM_DISPOSAL | SMW_ADM_PSTATS | SMW_ADM_FULLT,
	##

	###
	# Sets whether or not to refresh the pages of which semantic data is stored.
	#
	# @since 1.5.6
	##
	'smwgAutoRefreshSubject' => true,
	##

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
	# @see http://www.mediawiki.org/wiki/$wgMainCacheType
	#
	# @since 3.0
	# @default CACHE_ANYTHING
	##
	'smwgMainCacheType' => CACHE_ANYTHING,
	##

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
		'api.browse' => 3600,
		'api.browse.pvalue' => 3600,
		'api.browse.psubject' => 3600,
		'api.task'  => 3600
	],
	##

	###
	# Sets whether or not to refresh semantic data in the store when a page is
	# manually purged
	#
	# @since 1.9
	#
	# @requires  $smwgMainCacheType be set
	# @default true
	##
	'smwgAutoRefreshOnPurge' => true,
	##

	###
	# Sets whether or not to refresh semantic data in the store when a page was
	# moved
	#
	# @since 1.9
	#
	# @requires  $smwgMainCacheType be set
	# @default true
	##
	'smwgAutoRefreshOnPageMove' => true,
	##

	##
	# List of user-defined fixed properties
	#
	# Listed properties are managed by its own fixed table (instad of a
	# shared one) to allow for sharding large datasets with value assignments.
	#
	# The type definition is talen from the property page `[[Has type::...]]` and
	# by default (if no type is defined) then the `smwgPDefaultType` is returned.
	#
	# Any change to the property type requires to run the `setupStore.php` script
	# or `Special:SMWAdmin` table update.
	#
	# 'smwgFixedProperties' => array(
	#		'Age',
	#		'Has population'
	# ),
	#
	# @see https://semantic-mediawiki.org/wiki/Fixed_properties
	# @since 1.9
	#
	# @default array()
	##
	'smwgFixedProperties' => [],

	###
	# Sets a threshold value for when a property is being highlighted as "hardly
	# begin used" on Special:Properties
	#
	# @since 1.9
	#
	# default = 5
	##
	'smwgPropertyLowUsageThreshold' => 5,
	##

	###
	# Hide properties where the usage count is zero on Special:Properties
	#
	# @since 1.9
	#
	# default = true (legacy behaviour)
	##
	'smwgPropertyZeroCountDisplay' => true,
	##

	###
	# QueryProfiler related settings
	#
	# @note If these settings are changed, please ensure to run update.php/rebuildData.php
	#
	# - smwgQueryProfiler can be set false to disable its functionality but it
	# may impact secondary processes that rely on profile information to be
	# available (Notification system etc.)
	#
	# - SMW_QPRFL_DUR to record query duration (the time
	# between the query result selection and output its)
	#
	# - SMW_QPRFL_PARAMS to record query parameters that are necessary
	# for allowing to generate a query result using a background job
	#
	# $smwgQueryProfiler = SMW_QPRFL_DUR | SMW_QPRFL_PARAMS;
	#
	# @since 1.9
	# @default true
	##
	'smwgQueryProfiler' => true,
	##

	###
	# Enables SMW specific annotation and content processing for listed SpecialPages
	#
	# @since 1.9
	##
	'smwgEnabledSpecialPage' => [ 'Ask' ],
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
	'smwgFallbackSearchType' => null,
	##

	###
	# If enabled it will display help information on the edit page to support users
	# unfamiliar with SMW when extending page content.
	#
	# @since 2.1
	##
	'smwgEnabledEditPageHelp' => true,
	##

	###
	# Various MediaWiki update operations in MW 1.26+ started to use DeferredUpdates
	# and to ensure that the Store update follows in queue of updates made to a page
	# this setting should be enabled by default for MW 1.26 onwards.
	#
	# It will improve page responsiveness for purge and move action significantly.
	#
	# @since 2.4
	##
	'smwgEnabledDeferredUpdate' => true,
	##

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
		]
	],
	##

	###
	# Query dependency and parser cache invalidation
	#
	# If enabled it will store dependencies for queries allowing it to purge
	# the ParserCache on subjects with embedded queries that contain altered entities.
	#
	# @since 2.3 (experimental)
	# @default false
	##
	'smwgEnabledQueryDependencyLinksStore' => false,
	##

	###
	# Relates to `smwgEnabledQueryDependencyLinksStore` and defines property keys
	# to be excluded from the dependency detection.
	#
	# For example, to avoid a purge process being triggered for each altered subobject
	# '_SOBJ' is excluded from the processing but it will not exclude any properties
	# defined by a subobject (given that it is not part of an extended exclusion list).
	#
	# `_MDAT` is excluded to avoid a purge on each page edit with a `Modification date`
	# change that would otherwise trigger a dependency update.
	#
	# '_ASKDU' changes to the duration of a query should not trigger an update of
	# possible query dependencies (as this has no bearing on the result list).
	#
	# @since 2.3 (experimental)
	##
	'smwgQueryDependencyPropertyExemptionList' => [
		'_MDAT', '_SOBJ', '_ASKDU', '_ASKDE', '_ASKSI', '_ASKFO', '_ASKST'
	],
	##

	###
	# Settings for OWL/RDF export
	#
	# Whether or not "normal" users can request an recursive export.
	#
	# @since 0.7
	# @default = false
	##
	'smwgAllowRecursiveExport' => false,
	##

	###
	# Settings for OWL/RDF export
	#
	# Whether or not backlinks should be included by default.
	#
	# @since 0.7
	# @default = true
	##
	'smwgExportBacklinks' => true,
	##

	###
	# OWL/RDF export namespace for URIs/IRIs
	#
	# Will be set automatically if nothing is given, but in order to make pretty
	# URIs you will need to set this to something nice and adapt your Apache
	# configuration appropriately.
	#
	# @see https://www.semantic-mediawiki.org/wiki/Help:$smwgNamespace
	# @see https://www.semantic-mediawiki.org/wiki/Help:EnableSemantics
	# @see https://www.semantic-mediawiki.org/wiki/Help:Pretty_URIs
	#
	# @since 1.0
	# @default = ''
	##
	// 	'smwgNamespace' => "http://example.org/id/",
	##

	###
	# The setting is introduced the keep backwards compatibility with existing Rdf/Turtle
	# exports. The `aux` marker is expected only used to be used for selected properties
	# to generate a helper value and not for any other predefined property.
	#
	# Any property that does not explicitly require an auxiliary value (such `_dat`/
	# `_geo` type values) now uses its native as condition descriptor (`Has_subobject`
	# instead of `Has_subobject-23aux`)
	#
	# For SPARQL repository users that don't want to run an a  `rebuildData.php`,
	# the setting has to be TRUE.
	#
	# This BC setting is planned to vanish with 3.x.
	#
	# @since 2.3
	##
	'smwgExportBCAuxiliaryUse' => false,
	##

	##
	# The preferred form is to use canonical identifiers (Category:, Property:)
	# instead of localized names to ensure that RDF/Query statements are language
	# agnostic and do work even after the site/content language changes.
	#
	# This BC setting is planned to vanish with 3.x.
	#
	# @since 2.3
	##
	'smwgExportBCNonCanonicalFormUse' => false,
	##

	##
	# Export resources using IRIs
	#
	# Instead of ASCII encoded URI's, allow resources to be exported as IRI's (RFC
	# 3987).
	#
	# @see https://www.w3.org/TR/rdf11-concepts/#section-IRIs
	#
	# @since 2.5
	# @default true
	##
	'smwgExportResourcesAsIri' => true,
	##

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
	##

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
	# @since 2.4
	##
	'smwgDVFeatures' => SMW_DV_PROV_REDI | SMW_DV_MLTV_LCODE | SMW_DV_PVAP | SMW_DV_WPV_DTITLE | SMW_DV_TIMEV_CM | SMW_DV_PPLB | SMW_DV_PROV_LHNT,
	##

	##
	# Fulltext search support
	#
	# If enabled, it will store text elements using a separate table in order for
	# the SQL back-end to use the special fulltext index operations provided by
	# the SQL engine.
	#
	# - Tested with MySQL/MariaDB
	# - Tested with SQLite
	#
	# @since 2.5
	# @default: false
	##
	'smwgEnabledFulltextSearch' => false,
	##

	##
	# Throttle index updates
	#
	# The objective is to postpone an update by relying on a deferred process that
	# runs the index update decoupled from the storage back-end update.
	#
	#
	# If a user wants to push updates to the updater immediately then this setting needs
	# to be disabled but by disabling this setting update lag may increase due to having
	# the process being executed synchronously to the wikipage update.
	#
	# @since 2.5
	# @default: true
	##
	'smwgFulltextDeferredUpdate' => true,
	##

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
	##

	##
	# Exempted properties
	#
	# List of property keys for which index and fulltext match operations are
	# exempted because there are either insignificant, mostly represent single
	# terms, or contain other characteristics that make them non preferable when
	# searching via the fulltext index.
	#
	# Listed properties will use the standard LIKE/NLIKE match operation when used
	# in connection with the ~/!~ expression.
	#
	# @since 2.5
	##
	'smwgFulltextSearchPropertyExemptionList' => [
		'_ASKFO', '_ASKST', '_ASKPA','_IMPO', '_LCODE', '_UNIT', '_CONV',
		'_TYPE', '_ERRT', '_INST', '_ASK', '_SOBJ', '_PVAL', '_PVALI',
		'_REDI', '_CHGPRO'
	],
	##

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
	##

	##
	# Describes the minimum word/token length to help to decide whether MATCH or LIKE
	# operators are to be used for a condition statement.
	#
	# For MySQL it is expected it corresponds to either innodb_ft_min_token_size or
	# ft_min_word_len
	#
	# @since 2.5
	##
	'smwgFulltextSearchMinTokenSize' => 3,
	##

	##
	# To detect a possible language candidate from an indexable text element.
	#
	# TextCatLanguageDetector, a large list of languages does have a detrimental
	# influence on the performance when trying to detect a language from a free text.
	#
	# Stopwords are only applied after language detection has been enabled.
	#
	# @see https://github.com/wikimedia/wikimedia-textcat
	#
	# @since 2.5
	# @default empty list (language detection is disabled by default)
	##
	'smwgFulltextLanguageDetection' => [
	//	'TextCatLanguageDetector' => array( 'en', 'de', 'fr', 'es', 'ja', 'zh' )
	//	'CdbNGramLanguageDetector' => array( 'en', 'de', 'fr', 'es', 'ja', 'zh' )
	],
	##

	##
	# MySQL's "Global Transaction Identifier" will create issues when executing
	# queries that rely on temporary tables, according to the documentation "... the
	# operations listed here cannot be used ... CREATE TEMPORARY TABLE statements
	# inside transactions".
	#
	# MySQL Global transaction identifier is a unique transaction ID assigned to
	# every transaction that happens in the MySQL database.
	#
	# Issue is encountered when @@GLOBAL.ENFORCE_GTID_CONSISTENCY = 1
	#
	# @see https://dev.mysql.com/doc/refman/5.6/en/replication-options-gtids.html
	# @see https://support.software.dell.com/kb/184275
	#
	# This setting (if enabled) will force an auto commit operation for temporary
	# tables to avoid the described limitation.
	#
	# @since 2.5
	# @default false
	##
	'smwgQTemporaryTablesAutoCommitMode' => false,
	##

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
	##

	###
	# Specifies the lifetime of embedded query and their results fetched from a
	# QueryEngine for when `smwgQueryResultCacheType` is enabled.
	#
	# @since 2.5
	##
	'smwgQueryResultCacheLifetime' => 60 * 60 * 24 * 7, // a week
	##

	###
	# Specifies the lifetime of non-embedded queries (Special:Ask, API etc.) and their
	# results that are fetched from a QueryEngine for when `smwgQueryResultCacheType` is
	# enabled.
	#
	# This setting can also be used to minimize a possible DoS vector by preventing
	# an advisory to make unlimited query requests from either Special:Ask or the
	# API that may lock the DB due to complex query answering and instead being
	# rerouted to the cache once a result has been computed.
	#
	# @note Non-embedded queries cannot not be tracked using the `QueryDependencyLinksStore`
	# (subject is being missing that would identify the entity) therefore
	# an auto-purge mechanism as in case of an embedded entity is not possible hence
	# the lifetime should be carefully selected to provide the necessary means for a
	# user and the application.
	#
	# 0/false as setting to disable caching of non-embedded queries.
	#
	# @since 2.5
	##
	'smwgQueryResultNonEmbeddedCacheLifetime' => 60 * 10, // 10 min
	##

	###
	# Enables the manual refresh for embedded queries when the action=purge event is
	# triggered.
	#
	# @since 2.5
	##
	'smwgQueryResultCacheRefreshOnPurge' => true,
	##

	##
	# Property create protection
	#
	# If enabled, users are able to annotate property values using existing properties
	# but new properties can only be created by those with the assigned "authority"
	# (aka user right).
	#
	# Changes to a property specification requires the permission as well.
	#
	# @since 3.0
	# @default false
	##
	'smwgCreateProtectionRight' => false,
	##

	###
	# Page edit protection
	#
	# To prevent accidental changes of content especially to those of property
	# definitions, this setting allows with the help of the `Is edit protected`
	# property to prevent editing on pages that have annotate the property with
	# `true`.
	#
	# Once the property is set, only users with the listed user right are able
	# to edit and/or revoke the restriction on the selected page.
	#
	# `smw-pageedit` has been deployed as extra right to be distinct from existing
	# edit protections
	#
	# To enable this functionality either assign `smw-pageedit` or any other
	# right to the variable to activate an edit protection.
	#
	# @since 2.5
	# @default false
	##
	'smwgEditProtectionRight' => false,
	##

	##
	# Similarity lookup exemption property
	#
	# The listed property is used to exclude a property from the similarity
	# lookup in case the comparing property contains an annotation value with the
	# exemption property.
	#
	# For example, the property `Governance level` may define
	# [[owl:differentFrom::Governance level of]] which would result in a suppressed
	# similarity lookup for both `Governance level` and `Governance level of`
	# property when compared to each other.
	#
	# @since 2.5
	##
	'smwgSimilarityLookupExemptionProperty' => 'owl:differentFrom',
	##

	##
	# Property label invalid characters
	#
	# Listed characters are categorized as invalid for a property label and will
	# result in an error.
	#
	# @see #1568, #1638, #3134
	#
	# @since 2.5
	##
	'smwgPropertyInvalidCharacterList' => [
		// Common characters
		'[', ']' , '|' , '<' , '>', '{', '}', '+', '–', '%', "\r", "\n",
		'?', '*', '!'
	],
	##

	##
	# Properties classified as retired/no longer in use
	#
	# Listed properties will be removed from the entity table hereby avoids
	# references or display of those classified as retired.
	#
	# The system normally leaves properties untouched (once created) but this
	# setting allows them to be marked as retired and eventually removed from
	# the system.
	#
	# @since 3.1
	##
	'smwgPropertyRetiredList' => [

		// No longer valid predefined property prefixes
		'_SF_', '_SD_'
	],
	##

	##
	# Reserved property names
	#
	# Listed names are reserved as they may interfere with Semantic MediaWiki or
	# MediaWiki itself.
	#
	# Removing default names from the list is not recommended (extending the list
	# is supported and may be used to customize use cases for an individual wiki).
	#
	# The list can contain simple names or identifiers that start with
	# `smw-property-reserved-` to link to a translatable representation that
	# matches a string in a content language.
	#
	# @see #2835, #2840
	#
	# @since 3.0
	##
	'smwgPropertyReservedNameList' => [ 'Category', 'smw-property-reserved-category' ],
	##

	##
	# Entity specific collation
	#
	# This should correspond to the $wgCategoryCollation setting (also in regards
	# to selected argument values), yet it is kept separate to have a better
	# control over changes in regards to the collation, sorting, and display of
	# values.
	#
	# This setting is "global" and applies to any entity that is maintained for
	# a wiki. In being global means that it cannot be selective (use one collation
	# for one query and use another collation for a different query) because the
	# field (smw_sort) contains a computed representation of the sort value.
	#
	# ANY change to this setting requires to run the `updateEntityCollation.php`
	# maintenance script.
	#
	# @since 3.0
	# @default identity (as legacy setting)
	##
	'smwgEntityCollation' => 'identity',
	##

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
	# @since 3.0
	##
	'smwgExperimentalFeatures' => false,
	##

	##
	# SQLStore specific field type features
	#
	# SMW_FIELDT_NONE
	#
	# SMW_FIELDT_CHAR_NOCASE - Modifies selected search fields to use a case
	# insensitive collation and may require additional extension (e.g. Postgres
	# requires `citext`) on non MySQL related systems therefore it is disabled
	# by default.
	#
	# Furthermore, no extensive analysis has been performed on how the switch
	# from VARBINARY to a collated VARCHAR field type affects the search
	# performance.
	#
	# If enabled, the setting will replace selected `FieldType::FIELD_TITLE`
	# types with `FieldType::TYPE_CHAR_NOCASE`.
	#
	# `FieldType::TYPE_CHAR_NOCASE` has been defined as:
	#
	# - MySQL: VARCHAR(255) CHARSET utf8 COLLATE utf8_general_ci
	# - Postgres: citext NOT NULL
	# - SQLite: VARCHAR(255) NOT NULL COLLATE NOCASE but according to [0] this may
	#   not work and need a special solution as hinted in [0]
	#
	# [0] https://techblog.dorogin.com/case-insensitive-like-in-sqlite-504f594dcdc3
	#
	# SMW_FIELDT_CHAR_LONG - Extends the size to 300 chars for text pattern
	# match (DIBlob and DIUri) fields.
	#
	# 300 has been selected to be able to build an index prefix with the available
	# default setting of MySQL/MariaDB which restricts the prefix length to 767
	# bytes for InnoDB tables [1]. The index length can be lifted [2] to up to
	# 3072 bytes for InnoDB tables that use the DYNAMIC or COMPRESSED row format but
	# that requires custom intervention.
	#
	# [1] https://dev.mysql.com/doc/refman/5.7/en/innodb-restrictions.html
	# [2] https://dev.mysql.com/doc/refman/5.7/en/innodb-parameters.html#sysvar_innodb_large_prefix
	#
	# By default, the SQLStore has restricted the DIBlob and DIUri fields to a
	# 72 chars search depth in exchange for index size and performance.
	# Extending fields to 300 allows to run LIKE/NLIKE matching on a larger text
	# body without relying on a full-text index but an increased index size could
	# potentially carry a performance penalty when the index cannot be kept in
	# memory.
	#
	# No analysis has been performed on how performance is impacted. Selecting
	# this option requires to run `rebuildData.php` to adjust the field content
	# to the new length.
	#
	# SMW_FIELDT_CHAR_NOCASE | SMW_FIELDT_CHAR_LONG can be combined to build a
	# case insensitive long field type.
	#
	# @since 3.0
	# @default false
	##
	'smwgFieldTypeFeatures' => false,
	##

	##
	# Subobject content hash !! BC setting ONLY !!
	#
	# Normalized content hash is enabled by default to ensure that a content
	# declaration like:
	#
	# {{#subobject:
	# |Has text=Foo,Bar|+sep=,
	# }}
	#
	# yields the same hash as:
	#
	# {{#subobject:
	# |Has text=Bar,Foo|+sep=,
	# }}
	#
	# The setting is only provided to allow for a temporary backwards compatibility
	# and will be removed with 3.1 at which point the functionality is enabled
	# without any constraint.
	#
	# @since 3.0
	# @default true
	##
	'smwgUseComparableContentHash' => true,
	##

	##
	# List of supported schemes for a URI typed property
	#
	# @see https://www.iana.org/assignments/uri-schemes/uri-schemes.xhtml
	# @see https://www.w3.org/wiki/UriSchemes
	#
	# @since 3.0
	##
	'smwgURITypeSchemeList' => [
		'http', 'https', 'mailto', 'tel', 'ftp', 'sftp', 'news', 'file', 'urn',
		'telnet', 'ldap', 'gopher', 'ssh', 'git', 'irc', 'ircs'
	],
	##

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
	##

	##
	# Enable/disable <section> ... </section> support
	#
	# @since 3.0
	# @default true
	##
	'smwgSupportSectionTag' => true,
	##

	##
	# Subproperty type inheritance
	#
	# This setting enforces a type inheritance between a parent property and its
	# subproperties.
	#
	# @since 3.1
	# @default false
	##
	'smwgMandatorySubpropertyParentTypeInheritance' => false,
	##

	##
	# Find and remove remnant entities
	#
	# So called remnant entities or ghosts (i.e. assignments in tables without a
	# corresponding entry in a `smw_proptable_hash` field) should rarely happen
	# but can and to enable the updater to re-balance the content of the
	# `smw_proptable_hash` field (by checking and removing any ghosts in tables
	# currently not in use for a particular subject). This setting can be enabled
	# to force the updater/differ to make additional inquiries during an update
	# to find and remove remnants that have no assignments in a table for a
	# selected subject.
	#
	# The impact (in terms of performance) on the updater is unknown since each
	# update is expected to run additional queries therefore the setting is
	# set on purge only by default.
	#
	# - `purge` enables the check to happen only during a purge action which
	#    limits a possible performance impact to a single subject request hereby
	#    avoids impacting regular updates
	# - `true` as setting will carry out the check on every update
	# - `false` will disable the check all together
	#
	# @see #3849#issuecomment-477605049
	#
	# @since 3.1
	# @default 'purge'
	##
	'smwgCheckForRemnantEntities' => 'purge',
	##

	##
	# THE FOLLOWING SETTINGS AND SUPPORT FUNCTIONS ARE EXPERIMENTAL!
	#
	# Please make you read the Readme.md (see the Elastic folder) file first
	# before enabling the ElasticStore and its settings!
	##

	##
	# Schema types
	#
	# The mapping defines the relation between a specific type, group and
	# a possible interpreter which validates the expected syntax.
	#
	# Each type will have its own interpretation about elements and how to
	# define and enact requirements.
	#
	# @since 3.0
	##
	'smwgSchemaTypes' => [
		'LINK_FORMAT_SCHEMA' => [
			'validation_schema' => __DIR__ . '/data/schema/link-format-schema.v1.json',
			'group' => SMW_SCHEMA_GROUP_FORMAT,
			'type_description' => 'smw-schema-description-link-format-schema',
			// '__factory' => [ 'SMW\Schema\SchemaFactory', 'newTest' ]
		],
		'SEARCH_FORM_SCHEMA' => [
			'validation_schema' => __DIR__ . '/data/schema/search-form-schema.v1.json',
			'group' => SMW_SCHEMA_GROUP_SEARCH_FORM,
			'type_description' => 'smw-schema-description-search-form-schema'
		],
		'PROPERTY_GROUP_SCHEMA' => [
			'validation_schema' => __DIR__ . '/data/schema/property-group-schema.v1.json',
			'group' => SMW_SCHEMA_GROUP_PROPERTY,
			'type_description' => 'smw-schema-description-property-group-schema'
		],
		'PROPERTY_CONSTRAINT_SCHEMA' => [
			'validation_schema' => __DIR__ . '/data/schema/property-constraint-schema.v1.json',
			'group' => SMW_SCHEMA_GROUP_PROPERTY,
			'type_description' => 'smw-schema-description-property-constraint-schema',
			'change_propagation' => [ '_CONSTRAINT_SCHEMA' ]
		]
	],
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
			// The complete name will be auto-generated from "smw-...-" + wfWikiID()
			// to avoid indicies among different wikis being corrupted when sharing
			// an ES instance.
			'data' => __DIR__ . '/data/elastic/smw-data-standard.json',
			'lookup' => __DIR__ . '/data/elastic/smw-lookup.json'
		],
		'connection' =>[
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
			'monitor.entity.replication.cache.lifetime' => 3660,
		],
		'query' => [

			// If for some reason no connection to the ES cluster could be
			// established, use the SQLStore QueryEngine as fallback.
			'fallback.no.connection' => false,

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
	##

	##
	# ElasticStore profile
	#
	# Maintaining various settings using an array notation in `LocalSettinga.php`
	# can become challenging hence we provide a JSON profile that can be assigned
	# instead. Settings will be merged together with the default
	# `smwgElasticsearchConfig` where a profile will override any previous value
	# assignments.
	#
	# @since 3.0
	##
	'smwgElasticsearchProfile' => false, // __DIR__ . '/data/elastic/default-profile.json',

	##
	# ElasticStore connection settings
	#
	# @since 3.0
	##
	'smwgElasticsearchEndpoints' => [
		// [ 'host' => '127.0.0.1', 'port' => 9200, 'scheme' => 'http' ],
		// [ 'host' => '127.0.0.1', 'port' => 9200, 'scheme' => 'http', 'user' => 'username', 'pass' => 'password!#$?*abc' ]
		// 'localhost:9200'
	]

];
