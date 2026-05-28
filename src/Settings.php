<?php

namespace SMW;

use MediaWiki\HookContainer\HookContainer;
use RuntimeException;
use SMW\Exception\SettingNotFoundException;
use SMW\Exception\SettingsAlreadyLoadedException;
use SMW\Listener\ChangeListener\ChangeListenerAwareTrait;
use SMW\Setup\LegacyConstantNormalizer;

/**
 * @private
 *
 * Encapsulate Semantic MediaWiki specific settings from GLOBALS access using a
 * dedicated interface.
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class Settings extends Options {

	use ChangeListenerAwareTrait;

	private ?HookContainer $hookContainer = null;

	private bool $isLoaded = false;

	/**
	 * @since 7.0.0
	 */
	public function setHookContainer( HookContainer $hookContainer ): void {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * Assemble individual SMW related settings into one accessible array for
	 * easy instantiation since we don't have unique way of accessing only
	 * SMW related settings ( e.g. $smwgSettings['...']) we need this method
	 * as short cut to invoke only smwg* related settings
	 *
	 * @since 3.2
	 *
	 * @throws SettingsAlreadyLoadedException
	 */
	public function loadFromGlobals(): void {
		// This function is never expected to be called more than once per active
		// instance which should only happen via the service factory, yet, if
		// someone attempted to call this function then we want to know by what
		// or whom!
		if ( $this->isLoaded ) {
			throw new SettingsAlreadyLoadedException(
				'Some function (or program) tried to reload the settings while ' .
				'already being initialized!'
			);
		}

		$this->options = [
			'smwgIP' => $GLOBALS['smwgIP'],
			'smwgExtraneousLanguageFileDir' => $GLOBALS['smwgExtraneousLanguageFileDir'],
			'smwgServicesFileDir' => $GLOBALS['smwgServicesFileDir'],
			'smwgMaintenanceDir' => $GLOBALS['smwgMaintenanceDir'],
			'smwgDir' => $GLOBALS['smwgDir'],
			'smwgConfigFileDir' => $GLOBALS['smwgConfigFileDir'],
			'smwgImportFileDirs' => $GLOBALS['smwgImportFileDirs'],
			'smwgImportReqVersion' => $GLOBALS['smwgImportReqVersion'],
			'smwgImportPerformers' => $GLOBALS['smwgImportPerformers'],
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
			'smwgSparqlRepositoryFeatures' => LegacyConstantNormalizer::normalize( 'smwgSparqlRepositoryFeatures', $GLOBALS['smwgSparqlRepositoryFeatures'] ),
			'smwgSparqlReplicationPropertyExemptionList' => $GLOBALS['smwgSparqlReplicationPropertyExemptionList'],
			'smwgSparqlQFeatures' => LegacyConstantNormalizer::normalize( 'smwgSparqlQFeatures', $GLOBALS['smwgSparqlQFeatures'] ),
			'smwgFactboxFeatures' => LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', $GLOBALS['smwgFactboxFeatures'] ),
			'smwgShowFactbox' => LegacyConstantNormalizer::normalize( 'smwgShowFactbox', $GLOBALS['smwgShowFactbox'] ),
			'smwgShowFactboxEdit' => LegacyConstantNormalizer::normalize( 'smwgShowFactboxEdit', $GLOBALS['smwgShowFactboxEdit'] ),
			'smwgCompactLinkSupport' => $GLOBALS['smwgCompactLinkSupport'],
			'smwgDefaultNumRecurringEvents' => $GLOBALS['smwgDefaultNumRecurringEvents'],
			'smwgMaxNumRecurringEvents' => $GLOBALS['smwgMaxNumRecurringEvents'],
			'smwgSearchByPropertyFuzzy' => $GLOBALS['smwgSearchByPropertyFuzzy'],
			'smwgPagingLimit' => $GLOBALS['smwgPagingLimit'],
			'smwgPropertyListLimit' => $GLOBALS['smwgPropertyListLimit'],
			'smwgQEnabled' => $GLOBALS['smwgQEnabled'],
			'smwgQMaxLimit' => $GLOBALS['smwgQMaxLimit'],
			'smwgIgnoreQueryErrors' => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSubcategoryDepth' => $GLOBALS['smwgQSubcategoryDepth'],
			'smwgQSubpropertyDepth' => $GLOBALS['smwgQSubpropertyDepth'],
			'smwgQEqualitySupport' => LegacyConstantNormalizer::normalize( 'smwgQEqualitySupport', $GLOBALS['smwgQEqualitySupport'] ),
			'smwgQDefaultNamespaces' => $GLOBALS['smwgQDefaultNamespaces'],
			'smwgQComparators' => $GLOBALS['smwgQComparators'],
			'smwgQFilterDuplicates' => $GLOBALS['smwgQFilterDuplicates'],
			'smwgQUseLegacyQuery' => $GLOBALS['smwgQUseLegacyQuery'],
			'smwStrictComparators' => $GLOBALS['smwStrictComparators'],
			'smwgQStrictComparators' => $GLOBALS['smwgQStrictComparators'],
			'smwgQMaxSize' => $GLOBALS['smwgQMaxSize'],
			'smwgQMaxDepth' => $GLOBALS['smwgQMaxDepth'],
			'smwgQFeatures' => LegacyConstantNormalizer::normalize( 'smwgQFeatures', $GLOBALS['smwgQFeatures'] ),
			'smwgQDefaultLimit' => $GLOBALS['smwgQDefaultLimit'],
			'smwgQUpperbound' => $GLOBALS['smwgQUpperbound'],
			'smwgQMaxInlineLimit' => $GLOBALS['smwgQMaxInlineLimit'],
			'smwgQPrintoutLimit' => $GLOBALS['smwgQPrintoutLimit'],
			'smwgQDefaultLinking' => $GLOBALS['smwgQDefaultLinking'],
			'smwgQConceptCaching' => LegacyConstantNormalizer::normalize( 'smwgQConceptCaching', $GLOBALS['smwgQConceptCaching'] ),
			'smwgQConceptMaxSize' => $GLOBALS['smwgQConceptMaxSize'],
			'smwgQConceptMaxDepth' => $GLOBALS['smwgQConceptMaxDepth'],
			'smwgQConceptFeatures' => LegacyConstantNormalizer::normalize( 'smwgQConceptFeatures', $GLOBALS['smwgQConceptFeatures'] ),
			'smwgQConceptCacheLifetime' => $GLOBALS['smwgQConceptCacheLifetime'],
			'smwgQExpensiveThreshold' => $GLOBALS['smwgQExpensiveThreshold'],
			'smwgQExpensiveExecutionLimit' => $GLOBALS['smwgQExpensiveExecutionLimit'],
			'smwgRemoteReqFeatures' => LegacyConstantNormalizer::normalize( 'smwgRemoteReqFeatures', $GLOBALS['smwgRemoteReqFeatures'] ),
			'smwgQuerySources' => $GLOBALS['smwgQuerySources'],
			'smwgQTemporaryTablesAutoCommitMode' => $GLOBALS['smwgQTemporaryTablesAutoCommitMode'],
			'smwgQSortFeatures' => LegacyConstantNormalizer::normalize( 'smwgQSortFeatures', $GLOBALS['smwgQSortFeatures'] ),
			'smwgResultFormats' => $GLOBALS['smwgResultFormats'],
			'smwgResultFormatsFeatures' => LegacyConstantNormalizer::normalize( 'smwgResultFormatsFeatures', $GLOBALS['smwgResultFormatsFeatures'] ),
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
			'smwgAdminFeatures' => LegacyConstantNormalizer::normalize( 'smwgAdminFeatures', $GLOBALS['smwgAdminFeatures'] ),
			'smwgAutoRefreshOnPurge' => $GLOBALS['smwgAutoRefreshOnPurge'],
			'smwgAutoRefreshOnPageMove' => $GLOBALS['smwgAutoRefreshOnPageMove'],
			'smwgMaxPropertyValues' => $GLOBALS['smwgMaxPropertyValues'],
			'smwgNamespace' => $GLOBALS['smwgNamespace'],
			'smwgMasterStore' => $GLOBALS['smwgMasterStore'] ?? '',
			'smwgIQRunningNumber' => $GLOBALS['smwgIQRunningNumber'] ?? 0,
			'smwgCacheUsage' => $GLOBALS['smwgCacheUsage'],
			'smwgMainCacheType' => $GLOBALS['smwgMainCacheType'],
			'smwgFixedProperties' => $GLOBALS['smwgFixedProperties'],
			'smwgPropertyLowUsageThreshold' => $GLOBALS['smwgPropertyLowUsageThreshold'],
			'smwgPropertyZeroCountDisplay' => $GLOBALS['smwgPropertyZeroCountDisplay'],
			'smwgQueryProfiler' => LegacyConstantNormalizer::normalize( 'smwgQueryProfiler', $GLOBALS['smwgQueryProfiler'] ),
			'smwgEnabledSpecialPage' => $GLOBALS['smwgEnabledSpecialPage'],
			'smwgFallbackSearchType' => $GLOBALS['smwgFallbackSearchType'],
			'smwgEnabledEditPageHelp' => $GLOBALS['smwgEnabledEditPageHelp'],
			'smwgEnabledDeferredUpdate' => $GLOBALS['smwgEnabledDeferredUpdate'],
			'smwgEnabledQueryDependencyLinksStore' => $GLOBALS['smwgEnabledQueryDependencyLinksStore'],
			'smwgQueryDependencyPropertyExemptionList' => $GLOBALS['smwgQueryDependencyPropertyExemptionList'],
			'smwgParserFeatures' => LegacyConstantNormalizer::normalize( 'smwgParserFeatures', $GLOBALS['smwgParserFeatures'] ),
			'smwgDVFeatures' => LegacyConstantNormalizer::normalize( 'smwgDVFeatures', $GLOBALS['smwgDVFeatures'] ),
			'smwgEnabledFulltextSearch' => $GLOBALS['smwgEnabledFulltextSearch'],
			'smwgFulltextDeferredUpdate' => $GLOBALS['smwgFulltextDeferredUpdate'],
			'smwgFulltextSearchTableOptions' => $GLOBALS['smwgFulltextSearchTableOptions'],
			'smwgFulltextSearchPropertyExemptionList' => $GLOBALS['smwgFulltextSearchPropertyExemptionList'],
			'smwgFulltextSearchMinTokenSize' => $GLOBALS['smwgFulltextSearchMinTokenSize'],
			'smwgFulltextLanguageDetection' => $GLOBALS['smwgFulltextLanguageDetection'],
			'smwgFulltextSearchIndexableDataTypes' => LegacyConstantNormalizer::normalize( 'smwgFulltextSearchIndexableDataTypes', $GLOBALS['smwgFulltextSearchIndexableDataTypes'] ),
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
			'smwgEntityCacheSizes' => $GLOBALS['smwgEntityCacheSizes'],
			'smwgExperimentalFeatures' => LegacyConstantNormalizer::normalize( 'smwgExperimentalFeatures', $GLOBALS['smwgExperimentalFeatures'] ),
			'smwgFieldTypeFeatures' => LegacyConstantNormalizer::normalize( 'smwgFieldTypeFeatures', $GLOBALS['smwgFieldTypeFeatures'] ),
			'smwgChangePropagationProtection' => $GLOBALS['smwgChangePropagationProtection'],
			'smwgUseComparableContentHash' => $GLOBALS['smwgUseComparableContentHash'],
			'smwgBrowseFeatures' => LegacyConstantNormalizer::normalize( 'smwgBrowseFeatures', $GLOBALS['smwgBrowseFeatures'] ),
			'smwgCategoryFeatures' => LegacyConstantNormalizer::normalize( 'smwgCategoryFeatures', $GLOBALS['smwgCategoryFeatures'] ),
			'smwgURITypeSchemeList' => $GLOBALS['smwgURITypeSchemeList'],
			'smwgElasticsearchConfig' => $GLOBALS['smwgElasticsearchConfig'],
			'smwgElasticsearchProfile' => $GLOBALS['smwgElasticsearchProfile'],
			'smwgElasticsearchEndpoints' => $GLOBALS['smwgElasticsearchEndpoints'],
			'smwgElasticsearchCredentials' => $GLOBALS['smwgElasticsearchCredentials'],
			'smwgPostEditUpdate' => $GLOBALS['smwgPostEditUpdate'],
			'smwgSpecialAskFormSubmitMethod' => $GLOBALS['smwgSpecialAskFormSubmitMethod'],
			'smwgSupportSectionTag' => $GLOBALS['smwgSupportSectionTag'],
			'smwgMandatorySubpropertyParentTypeInheritance' => $GLOBALS['smwgMandatorySubpropertyParentTypeInheritance'],
			'smwgCheckForRemnantEntities' => $GLOBALS['smwgCheckForRemnantEntities'],
			'smwgCheckForConstraintErrors' => $GLOBALS['smwgCheckForConstraintErrors'],
			'smwgPlainList' => $GLOBALS['smwgPlainList'],
			'smwgDetectOutdatedData' => $GLOBALS['smwgDetectOutdatedData'],
			'smwgIgnoreUpgradeKeyCheck' => $GLOBALS['smwgIgnoreUpgradeKeyCheck'],
			'smwgEnableExportRDFLink' => $GLOBALS['smwgEnableExportRDFLink'],
			'smwgSetParserCacheTimestamp' => $GLOBALS['smwgSetParserCacheTimestamp'],
			'smwgSetParserCacheKeys' => $GLOBALS['smwgSetParserCacheKeys'],
		];

		$this->isLoaded = true;

		// Deprecated since 3.1, fired for backward compatibility.
		$this->hookContainer->run( 'SMW::Config::BeforeCompletion', [ &$this->options ] );
		$this->hookContainer->run( 'SMW::Settings::BeforeInitializationComplete', [ &$this->options ] );
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
	public static function newFromArray( array $settings ): self {
		return new self( $settings );
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function set( $key, $value ): void {
		// Mirror Settings::loadFromGlobals(): user-facing string/array config
		// values must be normalized before reaching change listeners and
		// internal storage, so that callbacks (e.g. `setEqualitySupport(int)`)
		// see the integer form regardless of how the caller wrote the value
		// (#6586).
		$value = LegacyConstantNormalizer::normalize( $key, $value );

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
		} catch ( SettingNotFoundException ) {
			return $default;
		}

		return $r;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param mixed $mung
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function mung( string $key, mixed $mung ): string {
		if ( is_string( $mung ) ) {
			return (string)$this->get( $key ) . $mung;
		}

		throw new RuntimeException( "Operation for the current type is not supported!" );
	}

}
