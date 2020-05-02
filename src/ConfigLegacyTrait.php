<?php

namespace SMW;

use SMW\Exception\SettingNotFoundException;

/**
 * @private
 *
 * Mentioned settings are either planned to to be removed, will be, or already
 * got replaced by a different setting(s).
 *
 * Information will be used in DeprecationNoticeTaskHandler to detect and output
 * deprecation notices.
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
trait ConfigLegacyTrait {

	/**
	 * @since 3.2
	 *
	 */
	public function loadLegacyMappings( &$configuration ) {
		self::setLegacyMappings( $configuration );
		self::fillDeprecationNotices();
	}

	/**
	 * If some settings got renamed or reassigned then add the setting together
	 * with the new mapping to this section.
	 */
	private static function setLegacyMappings( &$configuration ) {

		if ( isset( $GLOBALS['smwgAdminRefreshStore'] ) && $GLOBALS['smwgAdminRefreshStore'] === false ) {
			$configuration['smwgAdminFeatures'] = $configuration['smwgAdminFeatures'] & ~SMW_ADM_REFRESH;
		}

		// smwgParserFeatures
		if ( isset( $GLOBALS['smwgEnabledInTextAnnotationParserStrictMode'] ) && $GLOBALS['smwgEnabledInTextAnnotationParserStrictMode'] === false ) {
			$configuration['smwgParserFeatures'] = $configuration['smwgParserFeatures'] & ~SMW_PARSER_STRICT;
		}

		if ( isset( $GLOBALS['smwgInlineErrors'] ) && $GLOBALS['smwgInlineErrors'] === false ) {
			$configuration['smwgParserFeatures'] = $configuration['smwgParserFeatures'] & ~SMW_PARSER_INL_ERROR;
		}

		if ( isset( $GLOBALS['smwgShowHiddenCategories'] ) && $GLOBALS['smwgShowHiddenCategories'] === false ) {
			$configuration['smwgParserFeatures'] = $configuration['smwgParserFeatures'] & ~SMW_PARSER_HID_CATS;
		}

		// smwgFactboxFeatures
		if ( isset( $GLOBALS['smwgFactboxUseCache'] ) && $GLOBALS['smwgFactboxUseCache'] === false ) {
			$configuration['smwgFactboxFeatures'] = $configuration['smwgFactboxFeatures'] & ~SMW_FACTBOX_CACHE;
		}

		if ( isset( $GLOBALS['smwgFactboxCacheRefreshOnPurge'] ) && $GLOBALS['smwgFactboxCacheRefreshOnPurge'] === false ) {
			$configuration['smwgFactboxFeatures'] = $configuration['smwgFactboxFeatures'] & ~SMW_FACTBOX_PURGE_REFRESH;
		}

		// smwgLinksInValues
		if ( isset( $GLOBALS['smwgLinksInValues'] ) && $GLOBALS['smwgLinksInValues'] === SMW_LINV_PCRE ) {
			$configuration['smwgParserFeatures'] = $configuration['smwgParserFeatures'] | SMW_PARSER_LINV;
		}

		if ( isset( $GLOBALS['smwgLinksInValues'] ) && $GLOBALS['smwgLinksInValues'] === SMW_LINV_OBFU ) {
			$configuration['smwgParserFeatures'] = $configuration['smwgParserFeatures'] | SMW_PARSER_LINV;
		}

		if ( isset( $GLOBALS['smwgLinksInValues'] ) && $GLOBALS['smwgLinksInValues'] === true ) {
			$configuration['smwgParserFeatures'] = $configuration['smwgParserFeatures'] | SMW_PARSER_LINV;
		}

		// smwgCategoryFeatures
		if ( isset( $GLOBALS['smwgUseCategoryRedirect'] ) && $GLOBALS['smwgUseCategoryRedirect'] === false ) {
			$configuration['smwgCategoryFeatures'] = $configuration['smwgCategoryFeatures'] & ~SMW_CAT_REDIRECT;
		}

		if ( isset( $GLOBALS['smwgCategoriesAsInstances'] ) && $GLOBALS['smwgCategoriesAsInstances'] === false ) {
			$configuration['smwgCategoryFeatures'] = $configuration['smwgCategoryFeatures'] & ~SMW_CAT_INSTANCE;
		}

		if ( isset( $GLOBALS['smwgUseCategoryHierarchy'] ) && $GLOBALS['smwgUseCategoryHierarchy'] === false ) {
			$configuration['smwgCategoryFeatures'] = $configuration['smwgCategoryFeatures'] & ~SMW_CAT_HIERARCHY;
		}

		if ( isset( $GLOBALS['smwgQueryDependencyPropertyExemptionlist'] ) ) {
			$configuration['smwgQueryDependencyPropertyExemptionList'] = $GLOBALS['smwgQueryDependencyPropertyExemptionlist'];
		}

		// smwgPropertyListLimit
		if ( isset( $GLOBALS['smwgSubPropertyListLimit'] ) ) {
			$configuration['smwgPropertyListLimit']['subproperty'] = $GLOBALS['smwgSubPropertyListLimit'];
		}

		if ( isset( $GLOBALS['smwgRedirectPropertyListLimit'] ) ) {
			$configuration['smwgPropertyListLimit']['redirect'] = $GLOBALS['smwgRedirectPropertyListLimit'];
		}

		// smwgCacheUsage
		if ( isset( $GLOBALS['smwgCacheUsage']['smwgStatisticsCacheExpiry'] ) ) {
			$configuration['smwgCacheUsage']['special.statistics'] = $GLOBALS['smwgCacheUsage']['smwgStatisticsCacheExpiry'];
		}

		if ( isset( $GLOBALS['smwgCacheUsage']['smwgStatisticsCache'] ) && $GLOBALS['smwgCacheUsage']['smwgStatisticsCache'] === false ) {
			$configuration['smwgCacheUsage']['special.statistics'] = false;
		}

		if ( isset( $GLOBALS['smwgCacheUsage']['smwgPropertiesCacheExpiry'] ) ) {
			$configuration['smwgCacheUsage']['special.properties'] = $GLOBALS['smwgCacheUsage']['smwgPropertiesCacheExpiry'];
		}

		if ( isset( $GLOBALS['smwgCacheUsage']['smwgPropertiesCache'] ) && $GLOBALS['smwgCacheUsage']['smwgPropertiesCache'] === false ) {
			$configuration['smwgCacheUsage']['special.properties'] = false;
		}

		if ( isset( $GLOBALS['smwgCacheUsage']['smwgUnusedPropertiesCacheExpiry'] ) ) {
			$configuration['smwgCacheUsage']['special.unusedproperties'] = $GLOBALS['smwgCacheUsage']['smwgUnusedPropertiesCacheExpiry'];
		}

		if ( isset( $GLOBALS['smwgCacheUsage']['smwgUnusedPropertiesCache'] ) && $GLOBALS['smwgCacheUsage']['smwgUnusedPropertiesCache'] === false ) {
			$configuration['smwgCacheUsage']['special.unusedproperties'] = false;
		}

		if ( isset( $GLOBALS['smwgCacheUsage']['smwgWantedPropertiesCacheExpiry'] ) ) {
			$configuration['smwgCacheUsage']['special.wantedproperties'] = $GLOBALS['smwgCacheUsage']['smwgWantedPropertiesCacheExpiry'];
		}

		if ( isset( $GLOBALS['smwgCacheUsage']['smwgWantedPropertiesCache'] ) && $GLOBALS['smwgCacheUsage']['smwgWantedPropertiesCache'] === false ) {
			$configuration['smwgCacheUsage']['special.wantedproperties'] = false;
		}

		// smwgQueryProfiler
		if ( isset( $GLOBALS['smwgQueryProfiler']['smwgQueryDurationEnabled'] ) && $GLOBALS['smwgQueryProfiler']['smwgQueryDurationEnabled'] === true ) {
			$configuration['smwgQueryProfiler'] = $configuration['smwgQueryProfiler'] | SMW_QPRFL_DUR;
		}

		if ( isset( $GLOBALS['smwgQueryProfiler']['smwgQueryParametersEnabled'] ) && $GLOBALS['smwgQueryProfiler']['smwgQueryParametersEnabled'] === true ) {
			$configuration['smwgQueryProfiler'] = $configuration['smwgQueryProfiler'] | SMW_QPRFL_PARAMS;
		}

		if ( isset( $GLOBALS['smwgSparqlDatabaseConnector'] ) ) {
			$configuration['smwgSparqlRepositoryConnector'] = $GLOBALS['smwgSparqlDatabaseConnector'];
		}

		if ( isset( $GLOBALS['smwgSparqlDatabase'] ) ) {
			$configuration['smwgSparqlCustomConnector'] = $GLOBALS['smwgSparqlDatabase'];
		}

		if ( isset( $GLOBALS['smwgDeclarationProperties'] ) ) {
			$configuration['smwgChangePropagationWatchlist'] = $GLOBALS['smwgDeclarationProperties'];
		}

		// smwgBrowseFeatures
		if ( isset( $GLOBALS['smwgToolboxBrowseLink'] ) && $GLOBALS['smwgToolboxBrowseLink'] === false ) {
			$configuration['smwgBrowseFeatures'] = $configuration['smwgBrowseFeatures'] & ~SMW_BROWSE_TLINK;
		}

		if ( isset( $GLOBALS['smwgBrowseShowInverse'] ) && $GLOBALS['smwgBrowseShowInverse'] === true ) {
			$configuration['smwgBrowseFeatures'] = $configuration['smwgBrowseFeatures'] | SMW_BROWSE_SHOW_INVERSE;
		}

		if ( isset( $GLOBALS['smwgBrowseShowAll'] ) && $GLOBALS['smwgBrowseShowAll'] === false ) {
			$configuration['smwgBrowseFeatures'] = $configuration['smwgBrowseFeatures'] & ~SMW_BROWSE_SHOW_INCOMING;
		}

		if ( isset( $GLOBALS['smwgBrowseByApi'] ) && $GLOBALS['smwgBrowseByApi'] === false ) {
			$configuration['smwgBrowseFeatures'] = $configuration['smwgBrowseFeatures'] & ~SMW_BROWSE_USE_API;
		}

		// smwgQSortFeatures
		if ( isset( $GLOBALS['smwgQSortingSupport'] ) && $GLOBALS['smwgQSortingSupport'] === false ) {
			$configuration['smwgQSortFeatures'] = $configuration['smwgQSortFeatures'] & ~SMW_QSORT;
		}

		if ( isset( $GLOBALS['smwgQRandSortingSupport'] ) && $GLOBALS['smwgQRandSortingSupport'] === false ) {
			$configuration['smwgQSortFeatures'] = $configuration['smwgQSortFeatures'] & ~SMW_QSORT_RANDOM;
		}

		if ( isset( $GLOBALS['smwgImportFileDir'] ) ) {
			$configuration['smwgImportFileDirs'] = (array)$GLOBALS['smwgImportFileDir'];
		}

		// smwgPagingLimit
		if ( isset( $GLOBALS['smwgTypePagingLimit'] ) ) {
			$configuration['smwgPagingLimit']['type'] = $GLOBALS['smwgTypePagingLimit'];
		}

		if ( isset( $GLOBALS['smwgConceptPagingLimit'] ) ) {
			$configuration['smwgPagingLimit']['concept'] = $GLOBALS['smwgConceptPagingLimit'];
		}

		if ( isset( $GLOBALS['smwgPropertyPagingLimit'] ) ) {
			$configuration['smwgPagingLimit']['property'] = $GLOBALS['smwgPropertyPagingLimit'];
		}

		// smwgSparqlEndpoint
		if ( isset( $GLOBALS['smwgSparqlQueryEndpoint'] ) ) {
			$configuration['smwgSparqlEndpoint']['query'] = $GLOBALS['smwgSparqlQueryEndpoint'];
		}

		if ( isset( $GLOBALS['smwgSparqlUpdateEndpoint'] ) ) {
			$configuration['smwgSparqlEndpoint']['update'] = $GLOBALS['smwgSparqlUpdateEndpoint'];
		}

		if ( isset( $GLOBALS['smwgSparqlDataEndpoint'] ) ) {
			$configuration['smwgSparqlEndpoint']['data'] = $GLOBALS['smwgSparqlDataEndpoint'];
		}

		if ( isset( $GLOBALS['smwgCacheType'] ) ) {
			$configuration['smwgMainCacheType'] = $GLOBALS['smwgCacheType'];
		}

		if ( isset( $GLOBALS['smwgSchemaTypes'] ) && $GLOBALS['smwgSchemaTypes'] === [] ) {
			unset( $GLOBALS['smwgSchemaTypes'] );
		}
	}

	/**
	 * Settings planned to be removed (or replaced) should be registered in this
	 * section.
	 */
	private static function fillDeprecationNotices() {

		$jobQueueWatchlist = [];

		// FIXME Remove with 3.1
		foreach ( $GLOBALS['smwgJobQueueWatchlist'] as $job ) {
			if ( strpos( $job, 'SMW\\' ) !== false ) {
				$jobQueueWatchlist[$job] = \SMW\MediaWiki\JobQueue::mapLegacyType( $job );
			}
		}

		$GLOBALS['smwgDeprecationNotices']['smw'] = [

			// Creates a notice for all of settings of this section that are planned
			// to be removed with the tentative target release
			'notice' => [
				'smwgAdminRefreshStore' => '3.1.0',
				'smwgQueryDependencyPropertyExemptionlist' => '3.1.0',
				'smwgQueryDependencyAffiliatePropertyDetectionlist' => '3.1.0',
				'smwgSubPropertyListLimit' => '3.1.0',
				'smwgRedirectPropertyListLimit' => '3.1.0',
				'smwgSparqlDatabaseConnector' => '3.1.0',
				'smwgSparqlDatabase' => '3.1.0',
				'smwgDeclarationProperties' => '3.1.0',
				'smwgToolboxBrowseLink' => '3.1.0',
				'smwgBrowseShowInverse' => '3.1.0',
				'smwgBrowseShowAll' => '3.1.0',
				'smwgBrowseByApi' => '3.1.0',
				'smwgEnabledInTextAnnotationParserStrictMode' => '3.1.0',
				'smwgInlineErrors' => '3.1.0',
				'smwgShowHiddenCategories' => '3.1.0',
				'smwgUseCategoryRedirect' => '3.1.0',
				'smwgCategoriesAsInstances' => '3.1.0',
				'smwgUseCategoryHierarchy' => '3.1.0',
				'smwgQSortingSupport' => '3.1.0',
				'smwgQRandSortingSupport' => '3.1.0',
				'smwgLinksInValues' => '3.1.0',
				'smwgTypePagingLimit' => '3.1.0',
				'smwgConceptPagingLimit' => '3.1.0',
				'smwgPropertyPagingLimit' => '3.1.0',
				'smwgSparqlQueryEndpoint' => '3.1.0',
				'smwgSparqlUpdateEndpoint' => '3.1.0',
				'smwgSparqlDataEndpoint' => '3.1.0',
				'smwgCacheType' => '3.1.0',
				'smwgFactboxUseCache' => '3.1.0',
				'smwgFactboxCacheRefreshOnPurge' => '3.1.0',

				// 3.2
				'smwgSchemaTypes' => '3.3.0',

				// Identifies options of settings planned to be removed
				'options' => [
					'smwgCacheUsage' => [
						'smwgStatisticsCache' => '3.1.0',
						'smwgStatisticsCacheExpiry' => '3.1.0',
						'smwgPropertiesCache' => '3.1.0',
						'smwgPropertiesCacheExpiry' => '3.1.0',
						'smwgUnusedPropertiesCache' => '3.1.0',
						'smwgUnusedPropertiesCacheExpiry' => '3.1.0',
						'smwgWantedPropertiesCache' => '3.1.0',
						'smwgWantedPropertiesCacheExpiry' => '3.1.0',
					],
					'smwgQueryProfiler' => [
						'smwgQueryDurationEnabled' => '3.1.0',
						'smwgQueryParametersEnabled' => '3.1.0'
					]
				]
			],

			// Identify settings that have been replaced by another setting
			'replacement' => [
				'smwgAdminRefreshStore' => 'smwgAdminFeatures',
				'smwgQueryDependencyPropertyExemptionlist' => 'smwgQueryDependencyPropertyExemptionList',
				'smwgSubPropertyListLimit' => 'smwgPropertyListLimit',
				'smwgRedirectPropertyListLimit' => 'smwgPropertyListLimit',
				'smwgSparqlDatabaseConnector' => 'smwgSparqlRepositoryConnector',
				'smwgSparqlDatabase' => 'smwgSparqlCustomConnector',
				'smwgDeclarationProperties' => 'smwgChangePropagationWatchlist',
				'smwgToolboxBrowseLink' => 'smwgBrowseFeatures',
				'smwgBrowseShowInverse' => 'smwgBrowseFeatures',
				'smwgBrowseShowAll' => 'smwgBrowseFeatures',
				'smwgBrowseByApi' => 'smwgBrowseFeatures',
				'smwgEnabledInTextAnnotationParserStrictMode' => 'smwgParserFeatures',
				'smwgInlineErrors' => 'smwgParserFeatures',
				'smwgShowHiddenCategories' => 'smwgParserFeatures',
				'smwgLinksInValues' => 'smwgParserFeatures',
				'smwgUseCategoryRedirect' => 'smwgCategoryFeatures',
				'smwgCategoriesAsInstances' => 'smwgCategoryFeatures',
				'smwgUseCategoryHierarchy' => 'smwgCategoryFeatures',
				'smwgQSortingSupport' => 'smwgQSortFeatures',
				'smwgQRandSortingSupport' => 'smwgQSortFeatures',
				'smwgImportFileDir' => 'smwgImportFileDirs',
				'smwgTypePagingLimit' => 'smwgPagingLimit',
				'smwgConceptPagingLimit' => 'smwgPagingLimit',
				'smwgPropertyPagingLimit' => 'smwgPagingLimit',
				'smwgSparqlQueryEndpoint' => 'smwgSparqlEndpoint',
				'smwgSparqlUpdateEndpoint' => 'smwgSparqlEndpoint',
				'smwgSparqlDataEndpoint' => 'smwgSparqlEndpoint',
				'smwgCacheType' => 'smwgMainCacheType',
				'smwgFactboxUseCache' => 'smwgFactboxFeatures',
				'smwgFactboxCacheRefreshOnPurge' => 'smwgFactboxFeatures',

				// 3.2
				'smwgContLang' => 'smwfContLang() (#4618)',
				'smwgSchemaTypes' => 'SchemaTypes (#4591)',

				// Identifies options of settings planned to be replaced
				'options' => [
					'smwgCacheUsage' => [
						'smwgStatisticsCacheExpiry' => 'special.statistics',
						'smwgPropertiesCacheExpiry' => 'special.properties',
						'smwgUnusedPropertiesCacheExpiry' => 'special.unusedproperties',
						'smwgWantedPropertiesCacheExpiry' => 'special.wantedproperties',
					],
					'smwgQueryProfiler' => [
						'smwgQueryDurationEnabled' => 'SMW_QPRFL_DUR',
						'smwgQueryParametersEnabled' => 'SMW_QPRFL_PARAMS'
					]
				] + ( $jobQueueWatchlist !== [] ? [ 'smwgJobQueueWatchlist' => $jobQueueWatchlist ] : [] )
			],

			// Identifies settings that got removed including the release it got
			// removed.
			'removal' => [
				'smwgOnDeleteAction' => '2.4.0',
				'smwgAutocompleteInSpecialAsk' => '3.0.0',
				'smwgSparqlDatabaseMaster' => '3.0.0',
				'smwgHistoricTypeNamespace' => '3.0.0',
				'smwgEnabledHttpDeferredJobRequest' => '3.0.0',
				'smwgQueryDependencyAffiliatePropertyDetectionList' => '3.1.0',
				'smwgValueLookupCacheType' => '3.1.0',
				'smwgEntityLookupCacheType' => '3.1.0',
				'smwgEntityLookupCacheLifetime' => '3.1.0',
				'smwgEntityLookupFeatures' => '3.1.0',
				'smwgContLang' => '3.2.0'
			]
		];
	}

}
