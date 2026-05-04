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
		# Sets whether the > and < comparators should be strict or not. If they are strict,
		# values that are equal will not be accepted.
		#
		# @since 1.5.3
		##
		'smwStrictComparators' => false,

		# ##
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
