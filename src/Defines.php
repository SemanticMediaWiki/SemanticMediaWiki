<?php

/**
 * Constants relevant to Semantic MediaWiki.
 *
 * This file is loaded via Composer's autoload mechanism (autoload.files in
 * composer.json), which runs before LocalSettings.php. This ensures constants
 * are available when users reference them in configuration, e.g.:
 *
 *     $smwgShowFactbox = SMW_FACTBOX_NONEMPTY;
 *
 * Without early loading, these constants would only be defined during the
 * extension callback (which fires after LocalSettings.php), causing
 * "Undefined constant" fatal errors.
 *
 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6579
 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/6580
 */

if ( defined( 'SMW_SPECIAL_SEARCHTYPE' ) ) {
	return;
}

// Search type
define( 'SMW_SPECIAL_SEARCHTYPE', 'SMWSearch' );

// Exporter/OWL serializer
define( 'SMW_SERIALIZER_DECL_CLASS', 1 );
define( 'SMW_SERIALIZER_DECL_OPROP', 2 );
define( 'SMW_SERIALIZER_DECL_APROP', 4 );

// ExtensionSchemaUpdates hook marker
define( 'SMW_EXTENSION_SCHEMA_UPDATER', 'smw/extension/schema/updater' );

// ResultPrinter header display
define( 'SMW_HEADERS_SHOW', 2 );
define( 'SMW_HEADERS_PLAIN', 1 );
// Used to be "false" hence use "0" to support extensions that still assume this.
define( 'SMW_HEADERS_HIDE', 0 );

// Output modes
define( 'SMW_OUTPUT_HTML', 1 );
define( 'SMW_OUTPUT_WIKI', 2 );
define( 'SMW_OUTPUT_FILE', 3 );
define( 'SMW_OUTPUT_RAW', 4 );

// Factbox display
define( 'SMW_FACTBOX_HIDDEN', 1 );
define( 'SMW_FACTBOX_SPECIAL', 2 );
define( 'SMW_FACTBOX_NONEMPTY', 3 );
define( 'SMW_FACTBOX_SHOWN', 5 );
define( 'SMW_FACTBOX_CACHE', 16 );
define( 'SMW_FACTBOX_PURGE_REFRESH', 32 );
define( 'SMW_FACTBOX_DISPLAY_SUBOBJECT', 64 );
define( 'SMW_FACTBOX_DISPLAY_ATTACHMENT', 128 );

// Equality reasoning
define( 'SMW_EQ_NONE', 1 );
define( 'SMW_EQ_SOME', 2 );
define( 'SMW_EQ_FULL', 4 );

// Internal entity types
define( 'SMW_SUBENTITY_MONOLINGUAL', '_ML' );
define( 'SMW_SUBENTITY_REFERENCE', '_REF' );
define( 'SMW_SUBENTITY_QUERY', '_QUERY' );
define( 'SMW_SUBENTITY_ERROR', '_ERR' );

// Query description flags
define( 'SMW_PROPERTY_QUERY', 1 );
define( 'SMW_CATEGORY_QUERY', 2 );
define( 'SMW_CONCEPT_QUERY', 4 );
define( 'SMW_NAMESPACE_QUERY', 8 );
define( 'SMW_CONJUNCTION_QUERY', 16 );
define( 'SMW_DISJUNCTION_QUERY', 32 );
define( 'SMW_ANY_QUERY', 0xFFFFFFFF );

// Concept caching
define( 'CONCEPT_CACHE_ALL', 4 );
define( 'CONCEPT_CACHE_HARD', 1 );
define( 'CONCEPT_CACHE_NONE', 0 );

// Datavalue comparators
define( 'SMW_CMP_EQ', 1 );
define( 'SMW_CMP_LEQ', 2 );
define( 'SMW_CMP_GEQ', 3 );
define( 'SMW_CMP_NEQ', 4 );
define( 'SMW_CMP_LIKE', 5 );
define( 'SMW_CMP_NLKE', 6 );
define( 'SMW_CMP_LESS', 7 );
define( 'SMW_CMP_GRTR', 8 );
define( 'SMW_CMP_PRIM_LIKE', 20 );
define( 'SMW_CMP_PRIM_NLKE', 21 );
define( 'SMW_CMP_IN', 22 );
define( 'SMW_CMP_PHRASE', 23 );
define( 'SMW_CMP_NOT', 24 );

// Date formats (binary encoding of nine bits: 3 positions x 3 interpretations)
define( 'SMW_MDY', 785 );
define( 'SMW_DMY', 673 );
define( 'SMW_YMD', 610 );
define( 'SMW_YDM', 596 );
define( 'SMW_MY', 97 );
define( 'SMW_YM', 76 );
define( 'SMW_Y', 9 );
define( 'SMW_YEAR', 1 );
define( 'SMW_DAY', 2 );
define( 'SMW_MONTH', 4 );
define( 'SMW_DAY_MONTH_YEAR', 7 );
define( 'SMW_DAY_YEAR', 3 );

// Date/time precision
define( 'SMW_PREC_Y', 0 );
define( 'SMW_PREC_YM', 1 );
define( 'SMW_PREC_YMD', 2 );
define( 'SMW_PREC_YMDT', 3 );
define( 'SMW_PREC_YMDTZ', 4 );

// SPARQL query features
define( 'SMW_SPARQL_QF_NONE', 0 );
define( 'SMW_SPARQL_QF_REDI', 2 );
define( 'SMW_SPARQL_QF_SUBP', 4 );
define( 'SMW_SPARQL_QF_SUBC', 8 );
define( 'SMW_SPARQL_QF_COLLATION', 16 );
define( 'SMW_SPARQL_QF_NOCASE', 32 );

// SPARQL repository features
define( 'SMW_SPARQL_NONE', 0 );
define( 'SMW_SPARQL_CONNECTION_PING', 2 );

// Deprecated since 3.0
define( 'SMW_HTTP_DEFERRED_ASYNC', true );
define( 'SMW_HTTP_DEFERRED_SYNC_JOB', 4 );
define( 'SMW_HTTP_DEFERRED_LAZY_JOB', 8 );

// DataValue features
define( 'SMW_DV_NONE', 0 );
define( 'SMW_DV_PROV_REDI', 2 );
define( 'SMW_DV_MLTV_LCODE', 4 );
define( 'SMW_DV_NUMV_USPACE', 8 );
define( 'SMW_DV_PVAP', 16 );
define( 'SMW_DV_WPV_DTITLE', 32 );
define( 'SMW_DV_PROV_DTITLE', 64 );
define( 'SMW_DV_PVUC', 128 );
define( 'SMW_DV_TIMEV_CM', 256 );
define( 'SMW_DV_PPLB', 512 );
define( 'SMW_DV_PROV_LHNT', 1024 );
define( 'SMW_DV_WPV_PIPETRICK', 2048 );

// Fulltext types
define( 'SMW_FT_NONE', 0 );
define( 'SMW_FT_BLOB', 2 );
define( 'SMW_FT_URI', 4 );
define( 'SMW_FT_WIKIPAGE', 8 );

// Admin features
define( 'SMW_ADM_NONE', 0 );
define( 'SMW_ADM_REFRESH', 2 );
define( 'SMW_ADM_DISPOSAL', 4 );
define( 'SMW_ADM_SETUP', 8 );
define( 'SMW_ADM_PSTATS', 16 );
define( 'SMW_ADM_FULLT', 32 );
define( 'SMW_ADM_MAINTENANCE_SCRIPT_DOCS', 64 );
define( 'SMW_ADM_SHOW_OVERVIEW', 128 );
define( 'SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN', 2048 );

// ResultPrinter features
define( 'SMW_RF_NONE', 0 );
define( 'SMW_RF_TEMPLATE_OUTSEP', 2 );

// Experimental features
define( 'SMW_QUERYRESULT_PREFETCH', 2 );
define( 'SMW_SHOWPARSER_USE_CURTAILMENT', 4 );

// Field type features
define( 'SMW_FIELDT_NONE', 0 );
define( 'SMW_FIELDT_CHAR_NOCASE', 2 );
define( 'SMW_FIELDT_CHAR_LONG', 4 );

// Query profiler
define( 'SMW_QPRFL_NONE', 0 );
define( 'SMW_QPRFL_PARAMS', 2 );
define( 'SMW_QPRFL_DUR', 4 );

// Browse features
define( 'SMW_BROWSE_NONE', 0 );
define( 'SMW_BROWSE_TLINK', 2 );
define( 'SMW_BROWSE_SHOW_INVERSE', 4 );
define( 'SMW_BROWSE_SHOW_INCOMING', 8 );
define( 'SMW_BROWSE_SHOW_GROUP', 16 );
define( 'SMW_BROWSE_SHOW_SORTKEY', 32 );
define( 'SMW_BROWSE_USE_API', 64 );

// Parser features
define( 'SMW_PARSER_NONE', 0 );
define( 'SMW_PARSER_STRICT', 2 );
define( 'SMW_PARSER_UNSTRIP', 4 );
define( 'SMW_PARSER_INL_ERROR', 8 );
define( 'SMW_PARSER_HID_CATS', 16 );
define( 'SMW_PARSER_LINV', 32 );
define( 'SMW_PARSER_LINKS_IN_VALUES', 32 );

// LinksInValue features
define( 'SMW_LINV_PCRE', 2 );
define( 'SMW_LINV_OBFU', 4 );

// Category features
define( 'SMW_CAT_NONE', 0 );
define( 'SMW_CAT_REDIRECT', 2 );
define( 'SMW_CAT_INSTANCE', 4 );
define( 'SMW_CAT_HIERARCHY', 8 );

// Sort features
define( 'SMW_QSORT_NONE', 0 );
define( 'SMW_QSORT', 2 );
define( 'SMW_QSORT_RANDOM', 4 );
define( 'SMW_QSORT_UNCONDITIONAL', 8 );

// Remote request features
define( 'SMW_REMOTE_REQ_SEND_RESPONSE', 2 );
define( 'SMW_REMOTE_REQ_SHOW_NOTE', 4 );

// Schema groups
define( 'SMW_SCHEMA_GROUP_FORMAT', 'schema/group/format' );
define( 'SMW_SCHEMA_GROUP_SEARCH', 'schema/group/search' );
define( 'SMW_SCHEMA_GROUP_PROPERTY', 'schema/group/property' );
define( 'SMW_SCHEMA_GROUP_CONSTRAINT', 'schema/group/constraint' );
define( 'SMW_SCHEMA_GROUP_PROFILE', 'schema/group/profile' );

// Special:Ask submit method
define( 'SMW_SASK_SUBMIT_GET', 'get' );
define( 'SMW_SASK_SUBMIT_GET_REDIRECT', 'get.redirect' );
define( 'SMW_SASK_SUBMIT_POST', 'post' );

// Constraint error check
define( 'SMW_CONSTRAINT_ERR_CHECK_NONE', false );
define( 'SMW_CONSTRAINT_ERR_CHECK_MAIN', 'check/main' );
define( 'SMW_CONSTRAINT_ERR_CHECK_ALL', 'check/all' );

// Content types
define( 'CONTENT_MODEL_SMW_SCHEMA', 'smw/schema' );
