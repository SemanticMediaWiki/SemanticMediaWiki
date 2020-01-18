<?php

namespace SMW;

use SMW\Exception\SettingNotFoundException;
use SMW\Listener\ChangeListener\ChangeListenerAwareTrait;

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

	use ConfigLegacyTrait;
	use ChangeListenerAwareTrait;

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

		/**
		 * IF YOU REMOVE SETTING(S) FROM THIS ARRAY DEFINTION, PLEASE ENSURE
		 * TO REGISTER THEM WITH THE `ConfigLegacyTrait`.
		 */

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
			'smwgPlainList' => $GLOBALS['smwgPlainList'],
		];

		// @see ConfigLegacyTrait
		self::loadLegacyMappings( $configuration );

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
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function set( $key, $value ) {

		foreach ( $this->getChangeListeners() as $changeListener ) {

			if ( !$changeListener->canTrigger( $key ) ) {
				continue;
			}

			$changeListener->setAttrs( [ $key => $value ] );
			$changeListener->trigger( $key );
		}

		parent::set( $key, $value );
	}

	/**
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

		throw new SettingNotFoundException( "'{$key}' is not a valid settings key" );
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

}
