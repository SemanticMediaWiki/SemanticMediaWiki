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
			'smwgSparqlDatabaseConnector' => $GLOBALS['smwgSparqlDatabaseConnector'],
			'smwgSparqlDatabase' => $GLOBALS['smwgSparqlDatabase'],
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
			'smwgToolboxBrowseLink' => $GLOBALS['smwgToolboxBrowseLink'],
			'smwgInlineErrors' => $GLOBALS['smwgInlineErrors'],
			'smwgUseCategoryHierarchy' => $GLOBALS['smwgUseCategoryHierarchy'],
			'smwgCategoriesAsInstances' => $GLOBALS['smwgCategoriesAsInstances'],
			'smwgUseCategoryRedirect' => $GLOBALS['smwgUseCategoryRedirect'],
			'smwgLinksInValues' => $GLOBALS['smwgLinksInValues'],
			'smwgDefaultNumRecurringEvents' => $GLOBALS['smwgDefaultNumRecurringEvents'],
			'smwgMaxNumRecurringEvents' => $GLOBALS['smwgMaxNumRecurringEvents'],
			'smwgBrowseShowInverse' => $GLOBALS['smwgBrowseShowInverse'],
			'smwgBrowseShowAll' => $GLOBALS['smwgBrowseShowAll'],
			'smwgBrowseByApi' => $GLOBALS['smwgBrowseByApi'],
			'smwgSearchByPropertyFuzzy' => $GLOBALS['smwgSearchByPropertyFuzzy'],
			'smwgTypePagingLimit' => $GLOBALS['smwgTypePagingLimit'],
			'smwgConceptPagingLimit' => $GLOBALS['smwgConceptPagingLimit'],
			'smwgPropertyPagingLimit' => $GLOBALS['smwgPropertyPagingLimit'],
			'smwgSubPropertyListLimit' => $GLOBALS['smwgSubPropertyListLimit'],
			'smwgRedirectPropertyListLimit' => $GLOBALS['smwgRedirectPropertyListLimit'],
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
			'smwgDeclarationProperties' => $GLOBALS['smwgDeclarationProperties'],
			'smwgDataTypePropertyExemptionList' => $GLOBALS['smwgDataTypePropertyExemptionList'],
			'smwgTranslate' => $GLOBALS['smwgTranslate'],
			'smwgAutocompleteInSpecialAsk' => $GLOBALS['smwgAutocompleteInSpecialAsk'],
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
			'smwgShowHiddenCategories' => $GLOBALS['smwgShowHiddenCategories'],
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
			'smwgEnabledInTextAnnotationParserStrictMode' => $GLOBALS['smwgEnabledInTextAnnotationParserStrictMode'],
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
			'smwgSimilarityLookupExemptionProperty' => $GLOBALS['smwgSimilarityLookupExemptionProperty'],
			'smwgPropertyInvalidCharacterList' => $GLOBALS['smwgPropertyInvalidCharacterList'],
			'smwgEntityCollation' => $GLOBALS['smwgEntityCollation'],
			'smwgEntityLookupFeatures' => $GLOBALS['smwgEntityLookupFeatures'],
			'smwgFieldTypeFeatures' => $GLOBALS['smwgFieldTypeFeatures'],
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
	 * @since 2.5
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function add( $key, $value ) {

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

		if ( isset( $GLOBALS['smwgQueryDependencyPropertyExemptionlist'] ) ) {
			$configuration['smwgQueryDependencyPropertyExemptionList'] = $GLOBALS['smwgQueryDependencyPropertyExemptionlist'];
		}

		if ( isset( $GLOBALS['smwgQueryDependencyAffiliatePropertyDetectionlist'] ) ) {
			$configuration['smwgQueryDependencyAffiliatePropertyDetectionList'] = $GLOBALS['smwgQueryDependencyAffiliatePropertyDetectionlist'];
		}

		// Deprecated mapping used in DeprecationNoticeTaskHandler to detect and
		// output notices
		$GLOBALS['smwgDeprecationNotices'] = array(
			'notice' => array(
				'smwgAdminRefreshStore' => '3.1.0',
				'smwgQueryDependencyPropertyExemptionlist' => '3.1.0',
				'smwgQueryDependencyAffiliatePropertyDetectionlist' => '3.1.0'
			),
			'replacement' => array(
				'smwgAdminRefreshStore' => 'smwgAdminFeatures',
				'smwgQueryDependencyPropertyExemptionlist' => 'smwgQueryDependencyPropertyExemptionList',
				'smwgQueryDependencyAffiliatePropertyDetectionlist' => 'smwgQueryDependencyAffiliatePropertyDetectionList'
			),
			'removal' => array(
				'smwgOnDeleteAction' => '2.4.0'
			)
		);
	}

}
