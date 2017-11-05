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
	private $iterate = array();

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

		$configuration = array(
			'smwgScriptPath' => isset( $GLOBALS['smwgScriptPath'] ) ? $GLOBALS['smwgScriptPath'] : '',
			'smwgIP' => $GLOBALS['smwgIP'],
			'smwgExtraneousLanguageFileDir' => $GLOBALS['smwgExtraneousLanguageFileDir'],
			'smwgServicesFileDir' => $GLOBALS['smwgServicesFileDir'],
			'smwgImportFileDir' => $GLOBALS['smwgImportFileDir'],
			'smwgImportReqVersion' => $GLOBALS['smwgImportReqVersion'],
			'smwgSemanticsEnabled' => $GLOBALS['smwgSemanticsEnabled'],
			'smwgEnabledCompatibilityMode' => $GLOBALS['smwgEnabledCompatibilityMode'],
			'smwgDefaultStore' => $GLOBALS['smwgDefaultStore'],
			'smwgLocalConnectionConf' => $GLOBALS['smwgLocalConnectionConf'],
			'smwgSparqlRepositoryConnector' => $GLOBALS['smwgSparqlRepositoryConnector'],
			'smwgSparqlCustomConnector' => $GLOBALS['smwgSparqlCustomConnector'],
			'smwgSparqlQueryEndpoint' => $GLOBALS['smwgSparqlQueryEndpoint'],
			'smwgSparqlUpdateEndpoint' => $GLOBALS['smwgSparqlUpdateEndpoint'],
			'smwgSparqlDataEndpoint' => $GLOBALS['smwgSparqlDataEndpoint'],
			'smwgSparqlDefaultGraph' => $GLOBALS['smwgSparqlDefaultGraph'],
			'smwgSparqlRepositoryConnectorForcedHttpVersion' => $GLOBALS['smwgSparqlRepositoryConnectorForcedHttpVersion'],
			'smwgSparqlReplicationPropertyExemptionList' => $GLOBALS['smwgSparqlReplicationPropertyExemptionList'],
			'smwgSparqlQFeatures' => $GLOBALS['smwgSparqlQFeatures'],
			'smwgHistoricTypeNamespace' => $GLOBALS['smwgHistoricTypeNamespace'],
			'smwgNamespaceIndex' => $GLOBALS['smwgNamespaceIndex'],
			'smwgShowFactbox' => $GLOBALS['smwgShowFactbox'],
			'smwgShowFactboxEdit' => $GLOBALS['smwgShowFactboxEdit'],
			'smwgUseCategoryHierarchy' => $GLOBALS['smwgUseCategoryHierarchy'],
			'smwgCategoriesAsInstances' => $GLOBALS['smwgCategoriesAsInstances'],
			'smwgUseCategoryRedirect' => $GLOBALS['smwgUseCategoryRedirect'],
			'smwgLinksInValues' => $GLOBALS['smwgLinksInValues'],
			'smwgDefaultNumRecurringEvents' => $GLOBALS['smwgDefaultNumRecurringEvents'],
			'smwgMaxNumRecurringEvents' => $GLOBALS['smwgMaxNumRecurringEvents'],
			'smwgSearchByPropertyFuzzy' => $GLOBALS['smwgSearchByPropertyFuzzy'],
			'smwgTypePagingLimit' => $GLOBALS['smwgTypePagingLimit'],
			'smwgConceptPagingLimit' => $GLOBALS['smwgConceptPagingLimit'],
			'smwgPropertyPagingLimit' => $GLOBALS['smwgPropertyPagingLimit'],
			'smwgPropertyListLimit' => $GLOBALS['smwgPropertyListLimit'],
			'smwgQEnabled' => $GLOBALS['smwgQEnabled'],
			'smwgQMaxLimit' => $GLOBALS['smwgQMaxLimit'],
			'smwgIgnoreQueryErrors' => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSubcategoryDepth' => $GLOBALS['smwgQSubcategoryDepth'],
			'smwgQSubpropertyDepth' => $GLOBALS['smwgQSubpropertyDepth'],
			'smwgQEqualitySupport' => $GLOBALS['smwgQEqualitySupport'],
			'smwgQSortingSupport' => $GLOBALS['smwgQSortingSupport'],
			'smwgQRandSortingSupport' => $GLOBALS['smwgQRandSortingSupport'],
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
			'smwgQuerySources' => $GLOBALS['smwgQuerySources'],
			'smwgQTemporaryTablesAutoCommitMode' => $GLOBALS['smwgQTemporaryTablesAutoCommitMode'],
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
			'smwgCacheType' => $GLOBALS['smwgCacheType'],
			'smwgMainCacheType' => $GLOBALS['smwgMainCacheType'],
			'smwgValueLookupCacheType' => $GLOBALS['smwgValueLookupCacheType'],
			'smwgValueLookupCacheLifetime' => $GLOBALS['smwgValueLookupCacheLifetime'],
			'smwgValueLookupFeatures' => $GLOBALS['smwgValueLookupFeatures'],
			'smwgFixedProperties' => $GLOBALS['smwgFixedProperties'],
			'smwgPropertyLowUsageThreshold' => $GLOBALS['smwgPropertyLowUsageThreshold'],
			'smwgPropertyZeroCountDisplay' => $GLOBALS['smwgPropertyZeroCountDisplay'],
			'smwgFactboxUseCache' => $GLOBALS['smwgFactboxUseCache'],
			'smwgFactboxCacheRefreshOnPurge' => $GLOBALS['smwgFactboxCacheRefreshOnPurge'],
			'smwgQueryProfiler' => $GLOBALS['smwgQueryProfiler'],
			'smwgEnabledSpecialPage' => $GLOBALS['smwgEnabledSpecialPage'],
			'smwgFallbackSearchType' => $GLOBALS['smwgFallbackSearchType'],
			'smwgEnabledEditPageHelp' => $GLOBALS['smwgEnabledEditPageHelp'],
			'smwgEnabledDeferredUpdate' => $GLOBALS['smwgEnabledDeferredUpdate'],
			'smwgEnabledHttpDeferredJobRequest' => $GLOBALS['smwgEnabledHttpDeferredJobRequest'],
			'smwgEnabledQueryDependencyLinksStore' => $GLOBALS['smwgEnabledQueryDependencyLinksStore'],
			'smwgQueryDependencyPropertyExemptionList' => $GLOBALS['smwgQueryDependencyPropertyExemptionList'],
			'smwgQueryDependencyAffiliatePropertyDetectionList' => $GLOBALS['smwgQueryDependencyAffiliatePropertyDetectionList'],
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
			'smwgEntityCollation' => $GLOBALS['smwgEntityCollation'],
			'smwgEntityLookupFeatures' => $GLOBALS['smwgEntityLookupFeatures'],
			'smwgFieldTypeFeatures' => $GLOBALS['smwgFieldTypeFeatures'],
			'smwgChangePropagationProtection' => $GLOBALS['smwgChangePropagationProtection'],
			'smwgUseComparableContentHash' => $GLOBALS['smwgUseComparableContentHash'],
			'smwgBrowseFeatures' => $GLOBALS['smwgBrowseFeatures'],
		);

		self::initLegacyMapping( $configuration );

		\Hooks::run( 'SMW::Config::BeforeCompletion', array( &$configuration ) );

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
	 * @deprecated 3.0
	 */
	private function add( $key, $value ) {

		if ( !$this->has( $key ) ) {
			return $this->set( $key, $value );
		}

		$val = $this->get( $key );

		if ( is_array( $val ) ) {
			$value = array_merge_recursive( $val, $value );
		}

		return $this->set( $key, $value );
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
		return $this->doIterate( $key, $this->getOptions() );
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

		if ( isset( $GLOBALS['smwgEnabledInTextAnnotationParserStrictMode'] ) && $GLOBALS['smwgEnabledInTextAnnotationParserStrictMode'] === false ) {
			$configuration['smwgParserFeatures'] = $configuration['smwgParserFeatures'] & ~SMW_PARSER_STRICT;
		}

		if ( isset( $GLOBALS['smwgInlineErrors'] ) && $GLOBALS['smwgInlineErrors'] === false ) {
			$configuration['smwgParserFeatures'] = $configuration['smwgParserFeatures'] & ~SMW_PARSER_INL_ERROR;
		}

		if ( isset( $GLOBALS['smwgShowHiddenCategories'] ) && $GLOBALS['smwgShowHiddenCategories'] === false ) {
			$configuration['smwgParserFeatures'] = $configuration['smwgParserFeatures'] & ~SMW_PARSER_HID_CATS;
		}

		if ( isset( $GLOBALS['smwgQueryDependencyPropertyExemptionlist'] ) ) {
			$configuration['smwgQueryDependencyPropertyExemptionList'] = $GLOBALS['smwgQueryDependencyPropertyExemptionlist'];
		}

		if ( isset( $GLOBALS['smwgQueryDependencyAffiliatePropertyDetectionlist'] ) ) {
			$configuration['smwgQueryDependencyAffiliatePropertyDetectionList'] = $GLOBALS['smwgQueryDependencyAffiliatePropertyDetectionlist'];
		}

		if ( isset( $GLOBALS['smwgSubPropertyListLimit'] ) ) {
			$configuration['smwgPropertyListLimit']['subproperty'] = $GLOBALS['smwgSubPropertyListLimit'];
		}

		if ( isset( $GLOBALS['smwgRedirectPropertyListLimit'] ) ) {
			$configuration['smwgPropertyListLimit']['redirect'] = $GLOBALS['smwgRedirectPropertyListLimit'];
		}

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

		// Deprecated mapping used in DeprecationNoticeTaskHandler to detect and
		// output notices
		$GLOBALS['smwgDeprecationNotices'] = array(
			'notice' => array(
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
			),
			'replacement' => array(
				'smwgAdminRefreshStore' => 'smwgAdminFeatures',
				'smwgQueryDependencyPropertyExemptionlist' => 'smwgQueryDependencyPropertyExemptionList',
				'smwgQueryDependencyAffiliatePropertyDetectionlist' => 'smwgQueryDependencyAffiliatePropertyDetectionList',
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
				]
			),
			'removal' => array(
				'smwgOnDeleteAction' => '2.4.0',
				'smwgAutocompleteInSpecialAsk' => '3.0.0',
				'smwgSparqlDatabaseMaster' => '3.0.0'
			)
		);
	}

}
