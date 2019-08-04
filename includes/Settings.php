<?php

namespace SMW;

use SMW\Exception\SettingNotFoundException;

/**
 * Encapsulate Semantic MediaWiki settings to access values through a
 * specified interface
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class Settings extends Options {

	/**
	 * @var Settings
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	private $iterate = [];

	/**
	 * Assemble individual SMW related settings into one accessible array for
	 * easy instantiation since we don't have unique way of accessing only
	 * SMW related settings ( e.g. $smwgSettings['...']) we need this method
	 * as short cut to invoke only smwg* related settings
	 *
	 * @par Example:
	 * @code
	 *  $settings = Settings::newFromGlobals();
	 *  $settings->get( 'smwgDefaultStore' );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public static function newFromGlobals() {

		// #4150
		// If someone tried to use SMW without proper initialization then something
		// like "Notice: Undefined index: smwgNamespaceIndex ..." would appear and
		// to produce a proper error message avoid those by adding a default.
		if ( !defined( 'SMW_VERSION' ) || !isset( $GLOBALS['smwgNamespaceIndex'] ) ) {
			NamespaceManager::initCustomNamespace( $GLOBALS );
		}

		$configuration = [
			'smwgIP' => $GLOBALS['smwgIP'],
			'smwgExtraneousLanguageFileDir' => $GLOBALS['smwgExtraneousLanguageFileDir'],
			'smwgServicesFileDir' => $GLOBALS['smwgServicesFileDir'],
			'smwgResourceLoaderDefFiles' => $GLOBALS['smwgResourceLoaderDefFiles'],
			'smwgMaintenanceDir' => $GLOBALS['smwgMaintenanceDir'],
			'smwgTemplateDir' => $GLOBALS['smwgTemplateDir'],
			'smwgConfigFileDir' => $GLOBALS['smwgConfigFileDir'],
			'smwgImportFileDirs' => $GLOBALS['smwgImportFileDirs'],
			'smwgImportReqVersion' => $GLOBALS['smwgImportReqVersion'],
			'smwgSemanticsEnabled' => $GLOBALS['smwgSemanticsEnabled'],
			'smwgIgnoreExtensionRegistrationCheck' => $GLOBALS['smwgIgnoreExtensionRegistrationCheck'],
			'smwgUpgradeKey' => $GLOBALS['smwgUpgradeKey'],
			'smwgJobQueueWatchlist' => $GLOBALS['smwgJobQueueWatchlist'],
			'smwgEnabledCompatibilityMode' => $GLOBALS['smwgEnabledCompatibilityMode'],
			'smwgDefaultStore' => $GLOBALS['smwgDefaultStore'],
			'smwgDefaultLoggerRole' => $GLOBALS['smwgDefaultLoggerRole'],
			'smwgLocalConnectionConf' => $GLOBALS['smwgLocalConnectionConf'],
			'smwgSparqlRepositoryConnector' => $GLOBALS['smwgSparqlRepositoryConnector'],
			'smwgSparqlCustomConnector' => $GLOBALS['smwgSparqlCustomConnector'],
			'smwgSparqlEndpoint' => $GLOBALS['smwgSparqlEndpoint'],
			'smwgSparqlDefaultGraph' => $GLOBALS['smwgSparqlDefaultGraph'],
			'smwgSparqlRepositoryConnectorForcedHttpVersion' => $GLOBALS['smwgSparqlRepositoryConnectorForcedHttpVersion'],
			'smwgSparqlReplicationPropertyExemptionList' => $GLOBALS['smwgSparqlReplicationPropertyExemptionList'],
			'smwgSparqlQFeatures' => $GLOBALS['smwgSparqlQFeatures'],
			'smwgNamespaceIndex' => $GLOBALS['smwgNamespaceIndex'],
			'smwgFactboxFeatures' => $GLOBALS['smwgFactboxFeatures'],
			'smwgShowFactbox' => $GLOBALS['smwgShowFactbox'],
			'smwgShowFactboxEdit' => $GLOBALS['smwgShowFactboxEdit'],
			'smwgCompactLinkSupport' => $GLOBALS['smwgCompactLinkSupport'],
			'smwgDefaultNumRecurringEvents' => $GLOBALS['smwgDefaultNumRecurringEvents'],
			'smwgMaxNumRecurringEvents' => $GLOBALS['smwgMaxNumRecurringEvents'],
			'smwgSearchByPropertyFuzzy' => $GLOBALS['smwgSearchByPropertyFuzzy'],
			'smwgPagingLimit'  => $GLOBALS['smwgPagingLimit'],
			'smwgPropertyListLimit' => $GLOBALS['smwgPropertyListLimit'],
			'smwgQEnabled' => $GLOBALS['smwgQEnabled'],
			'smwgQMaxLimit' => $GLOBALS['smwgQMaxLimit'],
			'smwgIgnoreQueryErrors' => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSubcategoryDepth' => $GLOBALS['smwgQSubcategoryDepth'],
			'smwgQSubpropertyDepth' => $GLOBALS['smwgQSubpropertyDepth'],
			'smwgQEqualitySupport' => $GLOBALS['smwgQEqualitySupport'],
			'smwgQDefaultNamespaces' => $GLOBALS['smwgQDefaultNamespaces'],
			'smwgQComparators' => $GLOBALS['smwgQComparators'],
			'smwgQFilterDuplicates' => $GLOBALS['smwgQFilterDuplicates'],
			'smwStrictComparators' => $GLOBALS['smwStrictComparators'],
			'smwgQStrictComparators' => $GLOBALS['smwgQStrictComparators'],
			'smwgQMaxSize' => $GLOBALS['smwgQMaxSize'],
			'smwgQMaxDepth' => $GLOBALS['smwgQMaxDepth'],
			'smwgQFeatures' => $GLOBALS['smwgQFeatures'],
			'smwgQDefaultLimit' => $GLOBALS['smwgQDefaultLimit'],
			'smwgQUpperbound' => $GLOBALS['smwgQUpperbound'],
			'smwgQMaxInlineLimit' => $GLOBALS['smwgQMaxInlineLimit'],
			'smwgQPrintoutLimit' => $GLOBALS['smwgQPrintoutLimit'],
			'smwgQDefaultLinking' => $GLOBALS['smwgQDefaultLinking'],
			'smwgQConceptCaching' => $GLOBALS['smwgQConceptCaching'],
			'smwgQConceptMaxSize' => $GLOBALS['smwgQConceptMaxSize'],
			'smwgQConceptMaxDepth' => $GLOBALS['smwgQConceptMaxDepth'],
			'smwgQConceptFeatures' => $GLOBALS['smwgQConceptFeatures'],
			'smwgQConceptCacheLifetime' => $GLOBALS['smwgQConceptCacheLifetime'],
			'smwgQExpensiveThreshold' => $GLOBALS['smwgQExpensiveThreshold'],
			'smwgQExpensiveExecutionLimit' => $GLOBALS['smwgQExpensiveExecutionLimit'],
			'smwgRemoteReqFeatures' => $GLOBALS['smwgRemoteReqFeatures'],
			'smwgQuerySources' => $GLOBALS['smwgQuerySources'],
			'smwgQTemporaryTablesAutoCommitMode' => $GLOBALS['smwgQTemporaryTablesAutoCommitMode'],
			'smwgQSortFeatures' => $GLOBALS['smwgQSortFeatures'],
			'smwgResultFormats' => $GLOBALS['smwgResultFormats'],
			'smwgResultFormatsFeatures' => $GLOBALS['smwgResultFormatsFeatures'],
			'smwgResultAliases' => $GLOBALS['smwgResultAliases'],
			'smwgPDefaultType' => $GLOBALS['smwgPDefaultType'],
			'smwgAllowRecursiveExport' => $GLOBALS['smwgAllowRecursiveExport'],
			'smwgExportBacklinks' => $GLOBALS['smwgExportBacklinks'],
			'smwgExportResourcesAsIri' => $GLOBALS['smwgExportResourcesAsIri'],
			'smwgExportBCNonCanonicalFormUse' => $GLOBALS['smwgExportBCNonCanonicalFormUse'],
			'smwgExportBCAuxiliaryUse' => $GLOBALS['smwgExportBCAuxiliaryUse'],
			'smwgMaxNonExpNumber' => $GLOBALS['smwgMaxNonExpNumber'],
			'smwgEnableUpdateJobs' => $GLOBALS['smwgEnableUpdateJobs'],
			'smwgNamespacesWithSemanticLinks' => $GLOBALS['smwgNamespacesWithSemanticLinks'],
			'smwgPageSpecialProperties' => $GLOBALS['smwgPageSpecialProperties'],
			'smwgChangePropagationWatchlist' => $GLOBALS['smwgChangePropagationWatchlist'],
			'smwgDataTypePropertyExemptionList' => $GLOBALS['smwgDataTypePropertyExemptionList'],
			'smwgDefaultOutputFormatters' => $GLOBALS['smwgDefaultOutputFormatters'],
			'smwgTranslate' => $GLOBALS['smwgTranslate'],
			'smwgAutoRefreshSubject' => $GLOBALS['smwgAutoRefreshSubject'],
			'smwgAdminFeatures' => $GLOBALS['smwgAdminFeatures'],
			'smwgAutoRefreshOnPurge' => $GLOBALS['smwgAutoRefreshOnPurge'],
			'smwgAutoRefreshOnPageMove' => $GLOBALS['smwgAutoRefreshOnPageMove'],
			'smwgContLang' => isset( $GLOBALS['smwgContLang'] ) ? $GLOBALS['smwgContLang'] : '',
			'smwgMaxPropertyValues' => $GLOBALS['smwgMaxPropertyValues'],
			'smwgNamespace' => $GLOBALS['smwgNamespace'],
			'smwgMasterStore' => isset( $GLOBALS['smwgMasterStore'] ) ? $GLOBALS['smwgMasterStore'] : '',
			'smwgIQRunningNumber' => isset( $GLOBALS['smwgIQRunningNumber'] ) ? $GLOBALS['smwgIQRunningNumber'] : 0,
			'smwgCacheUsage' => $GLOBALS['smwgCacheUsage'],
			'smwgMainCacheType' => $GLOBALS['smwgMainCacheType'],
			'smwgFixedProperties' => $GLOBALS['smwgFixedProperties'],
			'smwgPropertyLowUsageThreshold' => $GLOBALS['smwgPropertyLowUsageThreshold'],
			'smwgPropertyZeroCountDisplay' => $GLOBALS['smwgPropertyZeroCountDisplay'],
			'smwgQueryProfiler' => $GLOBALS['smwgQueryProfiler'],
			'smwgEnabledSpecialPage' => $GLOBALS['smwgEnabledSpecialPage'],
			'smwgFallbackSearchType' => $GLOBALS['smwgFallbackSearchType'],
			'smwgEnabledEditPageHelp' => $GLOBALS['smwgEnabledEditPageHelp'],
			'smwgEnabledDeferredUpdate' => $GLOBALS['smwgEnabledDeferredUpdate'],
			'smwgEnabledQueryDependencyLinksStore' => $GLOBALS['smwgEnabledQueryDependencyLinksStore'],
			'smwgQueryDependencyPropertyExemptionList' => $GLOBALS['smwgQueryDependencyPropertyExemptionList'],
			'smwgParserFeatures' => $GLOBALS['smwgParserFeatures'],
			'smwgDVFeatures' => $GLOBALS['smwgDVFeatures'],
			'smwgEnabledFulltextSearch' => $GLOBALS['smwgEnabledFulltextSearch'],
			'smwgFulltextDeferredUpdate' => $GLOBALS['smwgFulltextDeferredUpdate'],
			'smwgFulltextSearchTableOptions' => $GLOBALS['smwgFulltextSearchTableOptions'],
			'smwgFulltextSearchPropertyExemptionList' => $GLOBALS['smwgFulltextSearchPropertyExemptionList'],
			'smwgFulltextSearchMinTokenSize' => $GLOBALS['smwgFulltextSearchMinTokenSize'],
			'smwgFulltextLanguageDetection' => $GLOBALS['smwgFulltextLanguageDetection'],
			'smwgFulltextSearchIndexableDataTypes' => $GLOBALS['smwgFulltextSearchIndexableDataTypes'],
			'smwgQueryResultCacheType' => $GLOBALS['smwgQueryResultCacheType'],
			'smwgQueryResultCacheLifetime' => $GLOBALS['smwgQueryResultCacheLifetime'],
			'smwgQueryResultNonEmbeddedCacheLifetime' => $GLOBALS['smwgQueryResultNonEmbeddedCacheLifetime'],
			'smwgQueryResultCacheRefreshOnPurge' => $GLOBALS['smwgQueryResultCacheRefreshOnPurge'],
			'smwgEditProtectionRight' => $GLOBALS['smwgEditProtectionRight'],
			'smwgCreateProtectionRight' => $GLOBALS['smwgCreateProtectionRight'],
			'smwgSimilarityLookupExemptionProperty' => $GLOBALS['smwgSimilarityLookupExemptionProperty'],
			'smwgPropertyInvalidCharacterList' => $GLOBALS['smwgPropertyInvalidCharacterList'],
			'smwgPropertyRetiredList' => $GLOBALS['smwgPropertyRetiredList'],
			'smwgPropertyReservedNameList' => $GLOBALS['smwgPropertyReservedNameList'],
			'smwgEntityCollation' => $GLOBALS['smwgEntityCollation'],
			'smwgExperimentalFeatures' => $GLOBALS['smwgExperimentalFeatures'],
			'smwgFieldTypeFeatures' => $GLOBALS['smwgFieldTypeFeatures'],
			'smwgChangePropagationProtection' => $GLOBALS['smwgChangePropagationProtection'],
			'smwgUseComparableContentHash' => $GLOBALS['smwgUseComparableContentHash'],
			'smwgBrowseFeatures' => $GLOBALS['smwgBrowseFeatures'],
			'smwgCategoryFeatures' => $GLOBALS['smwgCategoryFeatures'],
			'smwgURITypeSchemeList' => $GLOBALS['smwgURITypeSchemeList'],
			'smwgSchemaTypes' => $GLOBALS['smwgSchemaTypes'],
			'smwgElasticsearchConfig' => $GLOBALS['smwgElasticsearchConfig'],
			'smwgElasticsearchProfile' => $GLOBALS['smwgElasticsearchProfile'],
			'smwgElasticsearchEndpoints' => $GLOBALS['smwgElasticsearchEndpoints'],
			'smwgPostEditUpdate' => $GLOBALS['smwgPostEditUpdate'],
			'smwgSpecialAskFormSubmitMethod' => $GLOBALS['smwgSpecialAskFormSubmitMethod'],
			'smwgSupportSectionTag' => $GLOBALS['smwgSupportSectionTag'],
			'smwgMandatorySubpropertyParentTypeInheritance' => $GLOBALS['smwgMandatorySubpropertyParentTypeInheritance'],
			'smwgCheckForRemnantEntities' => $GLOBALS['smwgCheckForRemnantEntities'],
			'smwgCheckForConstraintErrors' => $GLOBALS['smwgCheckForConstraintErrors'],
		];

		self::initLegacyMapping( $configuration );

		// Deprecated since 3.1
		\Hooks::run( 'SMW::Config::BeforeCompletion', [ &$configuration ] );

		// Since 3.1
		\Hooks::run( 'SMW::Settings::BeforeInitializationComplete', [ &$configuration ] );

		if ( self::$instance === null ) {
			self::$instance = self::newFromArray( $configuration );
		}

		return self::$instance;
	}

	/**
	 * Factory method for immediate instantiation of a settings object for a
	 * given array
	 *
	 * @par Example:
	 * @code
	 *  $settings = Settings::newFromArray( array( 'Foo' => 'Bar' ) );
	 *  $settings->get( 'Foo' );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public static function newFromArray( array $settings ) {
		return new self( $settings );
	}

	/**
	 * Returns settings for a given key (nested settings are supported)
	 *
	 * @par Example:
	 * @code
	 *  $settings = Settings::newFromArray( array(
	 *   'Foo' => 'Bar'
	 *   'Parent' => array(
	 *     'Child' => array( 'Lisa', 'Lula', array( 'Lila' ) )
	 *   )
	 *  );
	 *
	 *  $settings->get( 'Child' ) will return array( 'Lisa', 'Lula', array( 'Lila' ) )
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return mixed
	 * @throws SettingNotFoundException
	 */
	public function get( $key ) {

		if ( $this->has( $key ) ) {
			return parent::get( $key );
		}

		// If the key wasn't matched it could be because of a nested array
		// hence iterate and verify otherwise throw an exception
		return $this->doIterate( $key, $this->toArray() );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function safeGet( $key, $default = false ) {

		try {
			$r = $this->get( $key );
		} catch ( SettingNotFoundException $e ) {
			return $default;
		}

		return $r;
	}

	/**
	 * @since 1.9
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * Iterates over a nested array to find an element
	 */
	private function doIterate( $key, $options ) {

		if ( isset( $this->iterate[$key] ) ) {
			return $this->iterate[$key];
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveArrayIterator( $options ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach( $iterator as $it => $value ) {
			if ( $key === $it ) {
				return $this->iterate[$key] = $value;
			}
		}

		throw new SettingNotFoundException( "'{$key}' is not a valid settings key" );
	}

	private static function initLegacyMapping( &$configuration ) {

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

		$jobQueueWatchlist = [];

		// FIXME Remove with 3.1
		foreach ( $GLOBALS['smwgJobQueueWatchlist'] as $job ) {
			if ( strpos( $job, 'SMW\\' ) !== false ) {
				$jobQueueWatchlist[$job] = \SMW\MediaWiki\JobQueue::mapLegacyType( $job );
			}
		}

		// Deprecated mapping used in DeprecationNoticeTaskHandler to detect and
		// output notices
		$GLOBALS['smwgDeprecationNotices']['smw'] = [
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
				'smwgTypePagingLimit'  => '3.1.0',
				'smwgConceptPagingLimit'  => '3.1.0',
				'smwgPropertyPagingLimit'  => '3.1.0',
				'smwgSparqlQueryEndpoint' => '3.1.0',
				'smwgSparqlUpdateEndpoint' => '3.1.0',
				'smwgSparqlDataEndpoint' => '3.1.0',
				'smwgCacheType' => '3.1.0',
				'smwgFactboxUseCache' => '3.1.0',
				'smwgFactboxCacheRefreshOnPurge' => '3.1.0',
				'options' => [
					'smwgCacheUsage' =>  [
						'smwgStatisticsCache' => '3.1.0',
						'smwgStatisticsCacheExpiry' => '3.1.0',
						'smwgPropertiesCache' => '3.1.0',
						'smwgPropertiesCacheExpiry' => '3.1.0',
						'smwgUnusedPropertiesCache' => '3.1.0',
						'smwgUnusedPropertiesCacheExpiry' => '3.1.0',
						'smwgWantedPropertiesCache' => '3.1.0',
						'smwgWantedPropertiesCacheExpiry' => '3.1.0',
					],
					'smwgQueryProfiler' =>  [
						'smwgQueryDurationEnabled' => '3.1.0',
						'smwgQueryParametersEnabled' => '3.1.0'
					]
				]
			],
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
				'smwgTypePagingLimit'  => 'smwgPagingLimit',
				'smwgConceptPagingLimit'  => 'smwgPagingLimit',
				'smwgPropertyPagingLimit'  => 'smwgPagingLimit',
				'smwgSparqlQueryEndpoint' => 'smwgSparqlEndpoint',
				'smwgSparqlUpdateEndpoint' => 'smwgSparqlEndpoint',
				'smwgSparqlDataEndpoint' => 'smwgSparqlEndpoint',
				'smwgCacheType' => 'smwgMainCacheType',
				'smwgFactboxUseCache' => 'smwgFactboxFeatures',
				'smwgFactboxCacheRefreshOnPurge' => 'smwgFactboxFeatures',
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
			]
		];
	}

}
