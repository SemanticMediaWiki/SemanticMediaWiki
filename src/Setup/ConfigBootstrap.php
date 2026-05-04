<?php

namespace SMW\Setup;

/**
 * Registration-time bootstrap for SMW config defaults that cannot be
 * expressed as static JSON in extension.json — chiefly settings whose
 * values derive from SMW feature-flag constants (SMW_FACTBOX_*, SMW_DV_*,
 * etc.) or class constants.
 *
 * Called from SemanticMediaWiki::initExtension() after extension.json
 * config defaults have been seeded into $GLOBALS by ExtensionRegistry.
 * Implementations must use provide-default semantics: write only when the
 * key is not already set, so user values from LocalSettings.php win.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ConfigBootstrap {

	/**
	 * @since 7.0.0
	 */
	public static function seedComputedDefaults(): void {
		if ( !isset( $GLOBALS['smwgSparqlQFeatures'] ) ) {
			$GLOBALS['smwgSparqlQFeatures'] = SMW_SPARQL_QF_REDI | SMW_SPARQL_QF_SUBP | SMW_SPARQL_QF_SUBC;
		}

		if ( !isset( $GLOBALS['smwgSparqlRepositoryFeatures'] ) ) {
			$GLOBALS['smwgSparqlRepositoryFeatures'] = SMW_SPARQL_NONE;
		}

		if ( !isset( $GLOBALS['smwgFactboxFeatures'] ) ) {
			$GLOBALS['smwgFactboxFeatures'] = SMW_FACTBOX_CACHE | SMW_FACTBOX_PURGE_REFRESH | SMW_FACTBOX_DISPLAY_SUBOBJECT | SMW_FACTBOX_DISPLAY_ATTACHMENT;
		}

		if ( !isset( $GLOBALS['smwgShowFactbox'] ) ) {
			$GLOBALS['smwgShowFactbox'] = SMW_FACTBOX_HIDDEN;
		}

		if ( !isset( $GLOBALS['smwgShowFactboxEdit'] ) ) {
			$GLOBALS['smwgShowFactboxEdit'] = SMW_FACTBOX_NONEMPTY;
		}

		if ( !isset( $GLOBALS['smwgCategoryFeatures'] ) ) {
			$GLOBALS['smwgCategoryFeatures'] = SMW_CAT_REDIRECT | SMW_CAT_INSTANCE | SMW_CAT_HIERARCHY;
		}

		if ( !isset( $GLOBALS['smwgBrowseFeatures'] ) ) {
			$GLOBALS['smwgBrowseFeatures'] = SMW_BROWSE_TLINK | SMW_BROWSE_SHOW_INCOMING | SMW_BROWSE_SHOW_GROUP | SMW_BROWSE_USE_API;
		}

		if ( !isset( $GLOBALS['smwgQEqualitySupport'] ) ) {
			$GLOBALS['smwgQEqualitySupport'] = SMW_EQ_SOME;
		}

		if ( !isset( $GLOBALS['smwgQSortFeatures'] ) ) {
			$GLOBALS['smwgQSortFeatures'] = SMW_QSORT | SMW_QSORT_RANDOM;
		}

		if ( !isset( $GLOBALS['smwgQFeatures'] ) ) {
			$GLOBALS['smwgQFeatures'] = SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_CONCEPT_QUERY | SMW_NAMESPACE_QUERY | SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY;
		}

		if ( !isset( $GLOBALS['smwgQConceptCaching'] ) ) {
			$GLOBALS['smwgQConceptCaching'] = CONCEPT_CACHE_HARD;
		}

		if ( !isset( $GLOBALS['smwgQConceptFeatures'] ) ) {
			$GLOBALS['smwgQConceptFeatures'] = SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_NAMESPACE_QUERY | SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY | SMW_CONCEPT_QUERY;
		}

		if ( !isset( $GLOBALS['smwgResultFormatsFeatures'] ) ) {
			$GLOBALS['smwgResultFormatsFeatures'] = SMW_RF_TEMPLATE_OUTSEP;
		}

		if ( !isset( $GLOBALS['smwgRemoteReqFeatures'] ) ) {
			$GLOBALS['smwgRemoteReqFeatures'] = SMW_REMOTE_REQ_SEND_RESPONSE | SMW_REMOTE_REQ_SHOW_NOTE;
		}

		if ( !isset( $GLOBALS['smwgAdminFeatures'] ) ) {
			$GLOBALS['smwgAdminFeatures'] = SMW_ADM_REFRESH | SMW_ADM_SETUP | SMW_ADM_DISPOSAL | SMW_ADM_PSTATS | SMW_ADM_FULLT | SMW_ADM_MAINTENANCE_SCRIPT_DOCS | SMW_ADM_SHOW_OVERVIEW | SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN;
		}

		if ( !isset( $GLOBALS['smwgMainCacheType'] ) ) {
			$GLOBALS['smwgMainCacheType'] = CACHE_ANYTHING;
		}

		if ( !isset( $GLOBALS['smwgQueryResultCacheType'] ) ) {
			$GLOBALS['smwgQueryResultCacheType'] = CACHE_NONE;
		}

		if ( !isset( $GLOBALS['smwgParserFeatures'] ) ) {
			$GLOBALS['smwgParserFeatures'] = SMW_PARSER_STRICT | SMW_PARSER_INL_ERROR | SMW_PARSER_HID_CATS;
		}

		if ( !isset( $GLOBALS['smwgDVFeatures'] ) ) {
			$GLOBALS['smwgDVFeatures'] = SMW_DV_PROV_REDI | SMW_DV_MLTV_LCODE | SMW_DV_PVAP | SMW_DV_WPV_DTITLE | SMW_DV_TIMEV_CM | SMW_DV_PPLB | SMW_DV_PROV_LHNT;
		}

		if ( !isset( $GLOBALS['smwgFulltextSearchIndexableDataTypes'] ) ) {
			$GLOBALS['smwgFulltextSearchIndexableDataTypes'] = SMW_FT_BLOB | SMW_FT_URI;
		}

		if ( !isset( $GLOBALS['smwgExperimentalFeatures'] ) ) {
			$GLOBALS['smwgExperimentalFeatures'] = SMW_QUERYRESULT_PREFETCH | SMW_SHOWPARSER_USE_CURTAILMENT;
		}

		if ( !isset( $GLOBALS['smwgSpecialAskFormSubmitMethod'] ) ) {
			$GLOBALS['smwgSpecialAskFormSubmitMethod'] = SMW_SASK_SUBMIT_POST;
		}

		if ( !isset( $GLOBALS['smwgCheckForConstraintErrors'] ) ) {
			$GLOBALS['smwgCheckForConstraintErrors'] = SMW_CONSTRAINT_ERR_CHECK_ALL;
		}
	}

}
