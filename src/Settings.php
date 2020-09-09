<?php

namespace SMW;

use SMW\Exception\SettingNotFoundException;
use SMW\Exception\SettingsAlreadyLoadedException;
use SMW\Listener\ChangeListener\ChangeListenerAwareTrait;
use SMW\MediaWiki\HookDispatcherAwareTrait;
use RuntimeException;

/**
 * @private
 *
 * Encapsulate Semantic MediaWiki specific settings from GLOBALS access using a
 * dedicated interface.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class Settings extends Options {

	use ConfigLegacyTrait;
	use ChangeListenerAwareTrait;
	use HookDispatcherAwareTrait;

	/**
	 * @var bool
	 */
	private $isLoaded = false;

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
	public function loadFromGlobals() {

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

		$this->options = [
			'smwgIP' => $GLOBALS['smwgIP'],
			'smwgExtraneousLanguageFileDir' => $GLOBALS['smwgExtraneousLanguageFileDir'],
			'smwgServicesFileDir' => $GLOBALS['smwgServicesFileDir'],
			'smwgResourceLoaderDefFiles' => $GLOBALS['smwgResourceLoaderDefFiles'],
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
			'smwgSparqlRepositoryFeatures' => $GLOBALS['smwgSparqlRepositoryFeatures'],
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
			'smwgPagingLimit' => $GLOBALS['smwgPagingLimit'],
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
			'smwgSchemaTypes' => $GLOBALS['smwgSchemaTypes'] ?? [],
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
			'smwgDetectOutdatedData' => $GLOBALS['smwgDetectOutdatedData'],
		];

		$this->isLoaded = true;

		/**
		 * @see ConfigLegacyTrait::loadLegacyMappings
		 */
		$this->loadLegacyMappings( $this->options );

		/**
		 * @see HookDispatcher::onSettingsBeforeInitializationComplete
		 */
		$this->hookDispatcher->onSettingsBeforeInitializationComplete( $this->options );
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
	 * @since 3.2
	 *
	 * @param string $key
	 * @param mixed $key
	 *
	 * @return mixed
	 * @throws RuntimeException
	 */
	public function mung( string $key, $mung ) {

		if ( is_string( $mung ) ) {
			return (string)$this->get( $key ) . $mung;
		}

		throw new RuntimeException( "Operation for the current type is not supported!" );
	}

}
