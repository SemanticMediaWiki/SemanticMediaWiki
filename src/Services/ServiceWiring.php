<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use Onoi\Cache\Cache;
use Psr\Log\LoggerInterface;
use SMW\CacheFactory;
use SMW\Connection\ConnectionManager;
use SMW\ConstraintFactory;
use SMW\DataItemFactory;
use SMW\DataTypeRegistry;
use SMW\DependencyValidatorFactory;
use SMW\DisplayTitleFinder;
use SMW\Elastic\ElasticFactory;
use SMW\EntityCache;
use SMW\EventDispatcher\EventDispatcher;
use SMW\Factbox\FactboxFactory;
use SMW\Factbox\FactboxText;
use SMW\HierarchyLookup;
use SMW\Indicator\EntityExaminerIndicatorsFactory;
use SMW\InMemoryPoolCache;
use SMW\IteratorFactory;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;
use SMW\Localizer\Localizer;
use SMW\Maintenance\MaintenanceFactory;
use SMW\MediaWiki\Api\TaskFactory;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\MediaWiki\Hooks\PersonalUrls;
use SMW\MediaWiki\Hooks\UserChange;
use SMW\MediaWiki\IndicatorRegistryFactory;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\Jobs\ContentParserFactory;
use SMW\MediaWiki\Jobs\PageUpdaterFactory;
use SMW\MediaWiki\Jobs\ParserDataFactory;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\MediaWiki\PermissionManager;
use SMW\MediaWiki\PostProcHandlerFactory;
use SMW\MediaWiki\RevisionGuard;
use SMW\NamespaceExaminer;
use SMW\Parser\InTextAnnotationParserFactory;
use SMW\ParserFunctionFactory;
use SMW\Property\AnnotatorFactory;
use SMW\Property\SpecificationLookup;
use SMW\PropertyLabelFinder;
use SMW\Protection\ProtectionValidator;
use SMW\Query\Processor\ParamListProcessor;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\QuerySourceFactory;
use SMW\QueryFactory;
use SMW\Schema\SchemaFactory;
use SMW\SerializerFactory;
use SMW\Services\DataValueServiceFactory;
use SMW\Services\ImporterServiceFactory;
use SMW\Services\ServicesFactory;
use SMW\Settings;
use SMW\SetupFile;
use SMW\SiteReadiness;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\Store;
use SMW\StoreFactory;

/**
 * Service wiring for SMW. Registered via `extension.json`'s
 * `ServiceWiringFiles`; each callback registers an `SMW.<Name>` service on
 * MediaWiki's `ServiceContainer`.
 *
 * Each callback first consults `ServicesFactory::hasTestOverride()` and, when a
 * test override has been registered for the matching name, returns it through
 * the corresponding `ServicesFactory::getX()` accessor. That keeps the global
 * container and `ServicesFactory::testOverrides` in agreement, so code paths
 * that resolve services through `MediaWikiServices` (notably `JobClasses`,
 * `SpecialPages`, `APIModules` and `HookHandlers` ObjectFactory specs) see the
 * same mock as code paths that go through `ServicesFactory::getX()` directly.
 *
 * When no test override is set the callback falls through to its normal
 * construction logic, which uses these dependency-resolution rules:
 *
 * - MediaWiki-core service: `$services->getXxx()`.
 *
 * - SMW factory-method services defined on `ServicesFactory` directly (not in
 *   this file): `ServicesFactory::getInstance()->newX()` or similar.
 *
 * - Sibling SMW service that is commonly test-mocked (`Store`, `Settings`,
 *   `Cache`, `EntityCache`, etc.): resolve through the matching
 *   `ServicesFactory::getX()` accessor. The accessor itself consults
 *   `testOverrides` first, so the override is honoured transitively for
 *   dependencies as well.
 *
 * - Sibling SMW service that is rarely test-mocked:
 *   `$services->getService( 'SMW.X' )`.
 *
 * Callbacks MUST NOT call `ServicesFactory::getInstance()->getX()` for the
 * same service the callback is wiring as the fallthrough path: that would
 * recurse (the accessor proxies back to the global container when no override
 * is set). The `hasTestOverride()` guard above is safe because the accessor
 * returns from `testOverrides` immediately when the override is present.
 *
 * @codeCoverageIgnore
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
return [

	'SMW.Settings' => static function ( MediaWikiServices $services ): Settings {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'Settings' ) ) {
			return $servicesFactory->getSettings();
		}

		$settings = new Settings();

		$settings->setHookContainer(
			$services->getHookContainer()
		);

		$settings->loadFromGlobals();

		return $settings;
	},

	'SMW.Store' => static function ( MediaWikiServices $services ): Store {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'Store' ) ) {
			return $servicesFactory->getStore();
		}

		$settings = $servicesFactory->getSettings();

		$store = $settings->get( 'smwgDefaultStore' );
		$instance = StoreFactory::getStore( $store );

		$configs = [
			'smwgDefaultStore',
			'smwgAutoRefreshSubject',
			'smwgEnableUpdateJobs',
			'smwgQEqualitySupport',
			'smwgElasticsearchConfig',
		];

		foreach ( $configs as $config ) {
			$instance->setOption( $config, $settings->get( $config ) );
		}

		$instance->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		return $instance;
	},

	'SMW.Cache' => static function ( MediaWikiServices $services ): Cache {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'Cache' ) ) {
			return $servicesFactory->getCache();
		}

		// Mirror ServicesFactory::getCache() default-path behaviour: build a
		// MediaWikiCompositeCache for the global $smwgMainCacheType. Callers
		// that need a non-default cache type still go through
		// CacheFactory::newMediaWikiCompositeCache() directly.
		return ( new CacheFactory() )->newMediaWikiCompositeCache();
	},

	'SMW.EntityCache' => static function ( MediaWikiServices $services ): EntityCache {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'EntityCache' ) ) {
			return $servicesFactory->getEntityCache();
		}

		return new EntityCache(
			$servicesFactory->getCache()
		);
	},

	'SMW.JobQueue' => static function ( MediaWikiServices $services ): JobQueue {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'JobQueue' ) ) {
			return $servicesFactory->getJobQueue();
		}

		// JobQueueGroup is commonly swapped in tests (e.g. ChangePropagationNotifierTest);
		// resolving it through ServicesFactory honours the testOverrides map.
		return new JobQueue(
			$servicesFactory->getJobQueueGroup()
		);
	},

	'SMW.PermissionManager' => static function ( MediaWikiServices $services ): PermissionManager {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'PermissionManager' ) ) {
			return $servicesFactory->getPermissionManager();
		}

		return new PermissionManager( $services->getPermissionManager() );
	},

	'SMW.RevisionGuard' => static function ( MediaWikiServices $services ): RevisionGuard {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'RevisionGuard' ) ) {
			return $servicesFactory->getRevisionGuard();
		}

		$revisionGuard = new RevisionGuard(
			$services->getRevisionLookup()
		);

		$revisionGuard->setHookContainer(
			$services->getHookContainer()
		);

		return $revisionGuard;
	},

	'SMW.ConnectionManager' => static function ( MediaWikiServices $services ): ConnectionManager {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ConnectionManager' ) ) {
			return $servicesFactory->getConnectionManager();
		}

		return new ConnectionManager();
	},

	'SMW.SetupFile' => static function ( MediaWikiServices $services ): SetupFile {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'SetupFile' ) ) {
			return $servicesFactory->getSetupFile();
		}

		return new SetupFile();
	},

	'SMW.SiteReadiness' => static function ( MediaWikiServices $services ): SiteReadiness {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'SiteReadiness' ) ) {
			return $servicesFactory->getSiteReadiness();
		}

		return new SiteReadiness();
	},

	'SMW.MediaWikiNsContentReader' => static function ( MediaWikiServices $services ): MediaWikiNsContentReader {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'MediaWikiNsContentReader' ) ) {
			return $servicesFactory->getMediaWikiNsContentReader();
		}

		$mediaWikiNsContentReader = new MediaWikiNsContentReader();

		$mediaWikiNsContentReader->setRevisionGuard(
			$servicesFactory->getRevisionGuard()
		);

		return $mediaWikiNsContentReader;
	},

	'SMW.InMemoryPoolCache' => static function ( MediaWikiServices $services ): InMemoryPoolCache {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'InMemoryPoolCache' ) ) {
			return $servicesFactory->getInMemoryPoolCache();
		}

		return InMemoryPoolCache::getInstance();
	},

	'SMW.PropertyAnnotatorFactory' => static function ( MediaWikiServices $services ): AnnotatorFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'PropertyAnnotatorFactory' ) ) {
			return $servicesFactory->getPropertyAnnotatorFactory();
		}

		return new AnnotatorFactory( $servicesFactory->getStore(), $servicesFactory->getPageCreator() );
	},

	'SMW.ConnectionProvider' => static function ( MediaWikiServices $services ): ConnectionProvider {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ConnectionProvider' ) ) {
			return $servicesFactory->getConnectionProvider();
		}

		$connectionProvider = new ConnectionProvider();

		$connectionProvider->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		return $connectionProvider;
	},

	'SMW.SchemaFactory' => static function ( MediaWikiServices $services ): SchemaFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'SchemaFactory' ) ) {
			return $servicesFactory->getSchemaFactory();
		}

		return new SchemaFactory();
	},

	'SMW.ConstraintFactory' => static function ( MediaWikiServices $services ): ConstraintFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ConstraintFactory' ) ) {
			return $servicesFactory->getConstraintFactory();
		}

		return new ConstraintFactory();
	},

	'SMW.ElasticFactory' => static function ( MediaWikiServices $services ): ElasticFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ElasticFactory' ) ) {
			return $servicesFactory->getElasticFactory();
		}

		return new ElasticFactory();
	},

	'SMW.QueryCreator' => static function ( MediaWikiServices $services ): QueryCreator {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'QueryCreator' ) ) {
			return $servicesFactory->getQueryCreator();
		}

		$settings = $servicesFactory->getSettings();

		$queryCreator = new QueryCreator(
			$servicesFactory->getQueryFactory(),
			$settings->get( 'smwgQDefaultNamespaces' ),
			$settings->get( 'smwgQDefaultLimit' )
		);

		$queryCreator->setQFeatures(
			$settings->get( 'smwgQFeatures' )
		);

		$queryCreator->setQConceptFeatures(
			$settings->get( 'smwgQConceptFeatures' )
		);

		return $queryCreator;
	},

	'SMW.ParamListProcessor' => static function ( MediaWikiServices $services ): ParamListProcessor {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ParamListProcessor' ) ) {
			return $servicesFactory->getParamListProcessor();
		}

		return new ParamListProcessor();
	},

	'SMW.FactboxText' => static function ( MediaWikiServices $services ): FactboxText {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'FactboxText' ) ) {
			return $servicesFactory->getFactboxText();
		}

		return new FactboxText();
	},

	'SMW.IteratorFactory' => static function ( MediaWikiServices $services ): IteratorFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'IteratorFactory' ) ) {
			return $servicesFactory->getIteratorFactory();
		}

		return new IteratorFactory();
	},

	'SMW.JobFactory' => static function ( MediaWikiServices $services ): JobFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'JobFactory' ) ) {
			return $servicesFactory->getJobFactory();
		}

		return new JobFactory( $services->getJobFactory() );
	},

	'SMW.TaskFactory' => static function ( MediaWikiServices $services ): TaskFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'TaskFactory' ) ) {
			return $servicesFactory->getTaskFactory();
		}

		return new TaskFactory(
			$servicesFactory->getStore(),
			$servicesFactory->getJobQueue(),
			$servicesFactory->getCache(),
			$servicesFactory->getSettings(),
			$servicesFactory->getJobFactory(),
			$services->getHookContainer()
		);
	},

	'SMW.FactboxFactory' => static function ( MediaWikiServices $services ): FactboxFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'FactboxFactory' ) ) {
			return $servicesFactory->getFactboxFactory();
		}

		return new FactboxFactory();
	},

	'SMW.QuerySourceFactory' => static function ( MediaWikiServices $services ): QuerySourceFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'QuerySourceFactory' ) ) {
			return $servicesFactory->getQuerySourceFactory();
		}

		return new QuerySourceFactory(
			$servicesFactory->getStore(),
			$servicesFactory->getSettings()->get( 'smwgQuerySources' )
		);
	},

	'SMW.QueryFactory' => static function ( MediaWikiServices $services ): QueryFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'QueryFactory' ) ) {
			return $servicesFactory->getQueryFactory();
		}

		return new QueryFactory();
	},

	'SMW.DataItemFactory' => static function ( MediaWikiServices $services ): DataItemFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'DataItemFactory' ) ) {
			return $servicesFactory->getDataItemFactory();
		}

		return new DataItemFactory();
	},

	'SMW.DataTypeRegistry' => static function ( MediaWikiServices $services ): DataTypeRegistry {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'DataTypeRegistry' ) ) {
			return $servicesFactory->getDataTypeRegistry();
		}

		return DataTypeRegistry::getInstance();
	},

	'SMW.QueryDependencyLinksStoreFactory' => static function ( MediaWikiServices $services ): QueryDependencyLinksStoreFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'QueryDependencyLinksStoreFactory' ) ) {
			return $servicesFactory->getQueryDependencyLinksStoreFactory();
		}

		return new QueryDependencyLinksStoreFactory();
	},

	'SMW.PropertySpecificationLookup' => static function ( MediaWikiServices $services ): SpecificationLookup {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'PropertySpecificationLookup' ) ) {
			return $servicesFactory->getPropertySpecificationLookup();
		}

		$contentLanguage = Localizer::getInstance()->getContentLanguage();

		$propertySpecificationLookup = new SpecificationLookup(
			$servicesFactory->getStore(),
			$servicesFactory->getEntityCache()
		);

		$propertySpecificationLookup->setLanguageCode(
			$contentLanguage->getCode()
		);

		return $propertySpecificationLookup;
	},

	'SMW.ProtectionValidator' => static function ( MediaWikiServices $services ): ProtectionValidator {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ProtectionValidator' ) ) {
			return $servicesFactory->getProtectionValidator();
		}

		$settings = $servicesFactory->getSettings();

		$protectionValidator = new ProtectionValidator(
			$servicesFactory->getStore(),
			$servicesFactory->getEntityCache(),
			$servicesFactory->getPermissionManager(),
			$servicesFactory->getPageCreator()
		);

		$protectionValidator->setImportPerformers(
			$settings->get( 'smwgImportPerformers' )
		);

		$protectionValidator->setEditProtectionRight(
			$settings->get( 'smwgEditProtectionRight' )
		);

		$protectionValidator->setCreateProtectionRight(
			$settings->get( 'smwgCreateProtectionRight' )
		);

		$protectionValidator->setChangePropagationProtection(
			$settings->get( 'smwgChangePropagationProtection' )
		);

		return $protectionValidator;
	},

	'SMW.TitlePermissions' => static function ( MediaWikiServices $services ): TitlePermissions {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'TitlePermissions' ) ) {
			return $servicesFactory->getTitlePermissions();
		}

		return new TitlePermissions(
			$servicesFactory->getProtectionValidator(),
			$servicesFactory->getPermissionManager()
		);
	},

	'SMW.ContentParserFactory' => static function ( MediaWikiServices $services ): ContentParserFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ContentParserFactory' ) ) {
			return $servicesFactory->getContentParserFactory();
		}

		return new ContentParserFactory(
			static fn (): Parser => $services->getParser(),
			$servicesFactory->getRevisionGuard()
		);
	},

	'SMW.ParserDataFactory' => static function ( MediaWikiServices $services ): ParserDataFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ParserDataFactory' ) ) {
			return $servicesFactory->getParserDataFactory();
		}

		return new ParserDataFactory(
			LoggerFactory::getInstance( 'smw' )
		);
	},

	'SMW.PageUpdaterFactory' => static function ( MediaWikiServices $services ): PageUpdaterFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'PageUpdaterFactory' ) ) {
			return $servicesFactory->getPageUpdaterFactory();
		}

		return new PageUpdaterFactory( $servicesFactory );
	},

	'SMW.Logger' => static function ( MediaWikiServices $services ): LoggerInterface {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'Logger' ) ) {
			return $servicesFactory->getLogger();
		}

		return LoggerFactory::getInstance( 'smw' );
	},

	'SMW.PropertyLabelFinder' => static function ( MediaWikiServices $services ): PropertyLabelFinder {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'PropertyLabelFinder' ) ) {
			return $servicesFactory->getPropertyLabelFinder();
		}

		$lang = Localizer::getInstance()->getLang();

		return new PropertyLabelFinder(
			$servicesFactory->getStore(),
			$lang->getPropertyLabels(),
			$lang->getCanonicalPropertyLabels(),
			$lang->getCanonicalDatatypeLabels()
		);
	},

	'SMW.InvalidateResultCacheEventListener' => static function ( MediaWikiServices $services ): InvalidateResultCacheEventListener {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'InvalidateResultCacheEventListener' ) ) {
			return $servicesFactory->getInvalidateResultCacheEventListener();
		}

		return new InvalidateResultCacheEventListener(
			$servicesFactory->getResultCache()
		);
	},

	'SMW.InvalidateEntityCacheEventListener' => static function ( MediaWikiServices $services ): InvalidateEntityCacheEventListener {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'InvalidateEntityCacheEventListener' ) ) {
			return $servicesFactory->getInvalidateEntityCacheEventListener();
		}

		return new InvalidateEntityCacheEventListener(
			$servicesFactory->getEntityCache()
		);
	},

	'SMW.InvalidatePropertySpecificationLookupCacheEventListener' => static function ( MediaWikiServices $services ): InvalidatePropertySpecificationLookupCacheEventListener {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'InvalidatePropertySpecificationLookupCacheEventListener' ) ) {
			return $servicesFactory->getInvalidatePropertySpecificationLookupCacheEventListener();
		}

		return new InvalidatePropertySpecificationLookupCacheEventListener(
			$servicesFactory->getPropertySpecificationLookup()
		);
	},

	'SMW.SerializerFactory' => static function ( MediaWikiServices $services ): SerializerFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'SerializerFactory' ) ) {
			return $servicesFactory->getSerializerFactory();
		}

		return new SerializerFactory( $servicesFactory->getStore() );
	},

	'SMW.ParserFunctionFactory' => static function ( MediaWikiServices $services ): ParserFunctionFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ParserFunctionFactory' ) ) {
			return $servicesFactory->getParserFunctionFactory();
		}

		return new ParserFunctionFactory();
	},

	'SMW.MaintenanceFactory' => static function ( MediaWikiServices $services ): MaintenanceFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'MaintenanceFactory' ) ) {
			return $servicesFactory->getMaintenanceFactory();
		}

		return new MaintenanceFactory();
	},

	'SMW.CacheFactory' => static function ( MediaWikiServices $services ): CacheFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'CacheFactory' ) ) {
			return $servicesFactory->getCacheFactory();
		}

		return new CacheFactory(
			$servicesFactory->getSettings()->get( 'smwgMainCacheType' )
		);
	},

	'SMW.PageCreator' => static function ( MediaWikiServices $services ): PageCreator {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'PageCreator' ) ) {
			return $servicesFactory->getPageCreator();
		}

		return new PageCreator();
	},

	'SMW.MwCollaboratorFactory' => static function ( MediaWikiServices $services ): MwCollaboratorFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'MwCollaboratorFactory' ) ) {
			return $servicesFactory->getMwCollaboratorFactory();
		}

		return new MwCollaboratorFactory( $servicesFactory );
	},

	'SMW.IndicatorRegistryFactory' => static function ( MediaWikiServices $services ): IndicatorRegistryFactory {
		return new IndicatorRegistryFactory(
			new EntityExaminerIndicatorsFactory()
		);
	},

	'SMW.PostProcHandlerFactory' => static function ( MediaWikiServices $services ): PostProcHandlerFactory {
		$servicesFactory = ServicesFactory::getInstance();

		return new PostProcHandlerFactory(
			$servicesFactory->getCache(),
			$servicesFactory->getSettings()
		);
	},

	'SMW.InTextAnnotationParserFactory' => static function ( MediaWikiServices $services ): InTextAnnotationParserFactory {
		$servicesFactory = ServicesFactory::getInstance();

		return new InTextAnnotationParserFactory(
			$services->getService( 'SMW.MwCollaboratorFactory' ),
			$servicesFactory->getSettings(),
			$services->getHookContainer()
		);
	},

	'SMW.NamespaceExaminer' => static function ( MediaWikiServices $services ): NamespaceExaminer {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'NamespaceExaminer' ) ) {
			return $servicesFactory->getNamespaceExaminer();
		}

		$settings = $servicesFactory->getSettings();

		$namespaceExaminer = new NamespaceExaminer(
			$settings->get( 'smwgNamespacesWithSemanticLinks' )
		);

		$namespaceExaminer->setValidNamespaces(
			$services->getNamespaceInfo()->getValidNamespaces()
		);

		return $namespaceExaminer;
	},

	'SMW.DataValueServiceFactory' => static function ( MediaWikiServices $services ): DataValueServiceFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'DataValueServiceFactory' ) ) {
			return $servicesFactory->getDataValueServiceFactory();
		}

		$servicesContainer = DataValueServiceFactory::newServicesContainer(
			$servicesFactory->getSettings()->get( 'smwgServicesFileDir' )
		);

		return new DataValueServiceFactory( $servicesContainer );
	},

	'SMW.ImporterServiceFactory' => static function ( MediaWikiServices $services ): ImporterServiceFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'ImporterServiceFactory' ) ) {
			return $servicesFactory->getImporterServiceFactory();
		}

		$servicesContainer = ImporterServiceFactory::newServicesContainer(
			$servicesFactory->getSettings()->get( 'smwgServicesFileDir' )
		);

		return new ImporterServiceFactory( $servicesContainer );
	},

	'SMW.DisplayTitleFinder' => static function ( MediaWikiServices $services ): DisplayTitleFinder {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'DisplayTitleFinder' ) ) {
			return $servicesFactory->getDisplayTitleFinder();
		}

		$settings = $servicesFactory->getSettings();

		$displayTitleFinder = new DisplayTitleFinder(
			$servicesFactory->getStore(),
			$servicesFactory->getEntityCache()
		);

		$displayTitleFinder->setCanUse(
			$settings->isFlagSet( 'smwgDVFeatures', SMW_DV_WPV_DTITLE )
		);

		return $displayTitleFinder;
	},

	'SMW.HierarchyLookup' => static function ( MediaWikiServices $services ): HierarchyLookup {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'HierarchyLookup' ) ) {
			return $servicesFactory->getHierarchyLookup();
		}

		$settings = $servicesFactory->getSettings();

		$hierarchyLookup = new HierarchyLookup(
			$servicesFactory->getStore(),
			$servicesFactory->getCache()
		);

		$hierarchyLookup->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		$hierarchyLookup->setSubcategoryDepth(
			$settings->get( 'smwgQSubcategoryDepth' )
		);

		$hierarchyLookup->setSubpropertyDepth(
			$settings->get( 'smwgQSubpropertyDepth' )
		);

		return $hierarchyLookup;
	},

	'SMW.EventDispatcher' => static function ( MediaWikiServices $services ): EventDispatcher {
		return ServicesFactory::getInstance()->getEventDispatcher();
	},

	'SMW.DependencyValidatorFactory' => static function ( MediaWikiServices $services ): DependencyValidatorFactory {
		$servicesFactory = ServicesFactory::getInstance();

		return new DependencyValidatorFactory(
			$servicesFactory->getNamespaceExaminer(),
			$servicesFactory->getQueryDependencyLinksStoreFactory(),
			$servicesFactory->getEntityCache(),
			$servicesFactory->getEventDispatcher(),
			$services->getParserCache()
		);
	},

	'SMW.PersonalUrls' => static function ( MediaWikiServices $services ): PersonalUrls {
		$servicesFactory = ServicesFactory::getInstance();
		return new PersonalUrls(
			$servicesFactory->getJobQueue(),
			$services->getUserOptionsLookup(),
			$servicesFactory->getSettings(),
			$servicesFactory->getPermissionManager()
		);
	},

	'SMW.ApplicationFactory' => static function ( MediaWikiServices $services ): ServicesFactory {
		// ApplicationFactory is the legacy alias for ServicesFactory. A handful of
		// hook handlers still take it as a constructor dependency while their
		// internals are being unwound (LinksUpdateComplete, ParserAfterTidy).
		return ServicesFactory::getInstance();
	},

	'SMW.UserChange' => static function ( MediaWikiServices $services ): UserChange {
		$servicesFactory = ServicesFactory::getInstance();
		return new UserChange(
			$servicesFactory->getNamespaceExaminer(),
			$servicesFactory->newJobFactory()
		);
	},

	'SMW.ArticleDelete' => static function ( MediaWikiServices $services ): ArticleDelete {
		$servicesFactory = ServicesFactory::getInstance();
		return new ArticleDelete(
			$servicesFactory->getStore(),
			$servicesFactory->newJobFactory(),
			$servicesFactory->getEventDispatcher(),
			$servicesFactory->getSerializerFactory(),
			$servicesFactory
		);
	},

	'SMW.FulltextSearchTableFactory' => static function ( MediaWikiServices $services ): FulltextSearchTableFactory {
		$servicesFactory = ServicesFactory::getInstance();

		if ( $servicesFactory->hasTestOverride( 'FulltextSearchTableFactory' ) ) {
			return $servicesFactory->singleton( 'FulltextSearchTableFactory' );
		}

		return new FulltextSearchTableFactory();
	},

];
