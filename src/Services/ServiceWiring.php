<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Onoi\Cache\Cache;
use SMW\CacheFactory;
use SMW\Connection\ConnectionManager;
use SMW\ConstraintFactory;
use SMW\DataItemFactory;
use SMW\Elastic\ElasticFactory;
use SMW\EntityCache;
use SMW\Factbox\FactboxFactory;
use SMW\Factbox\FactboxText;
use SMW\InMemoryPoolCache;
use SMW\IteratorFactory;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\MediaWiki\PermissionManager;
use SMW\MediaWiki\RevisionGuard;
use SMW\Property\AnnotatorFactory;
use SMW\Property\SpecificationLookup;
use SMW\PropertyLabelFinder;
use SMW\Protection\ProtectionValidator;
use SMW\Query\Processor\ParamListProcessor;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\QuerySourceFactory;
use SMW\QueryFactory;
use SMW\Schema\SchemaFactory;
use SMW\Services\ServicesFactory;
use SMW\Settings;
use SMW\SetupFile;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Store;
use SMW\StoreFactory;
use SMW\Utils\Logger;

/**
 * Service wiring for SMW. Registered via `extension.json`'s
 * `ServiceWiringFiles`; each callback registers an `SMW.<Name>` service on
 * MediaWiki's `ServiceContainer`.
 *
 * Each callback constructs the service directly. Dependency-resolution rules:
 *
 * - Sibling SMW service that is rarely test-mocked:
 *   `$services->getService( 'SMW.X' )`.
 *
 * - Sibling SMW service that is commonly test-mocked (`Store`, `Settings`,
 *   `Cache`, `EntityCache`, `JobQueue`, `JobQueueGroup`, `RevisionGuard`,
 *   `HookDispatcher`, `PropertySpecificationLookup`, etc.): resolve through
 *   the matching `ServicesFactory::getX()` accessor so the `testOverrides`
 *   map is honoured at construction time. Going through `$services` would
 *   skip the overrides and use the production instance, defeating the mock.
 *
 * - MediaWiki-core service: `$services->getXxx()`.
 *
 * - SMW factory-method services defined on `ServicesFactory` directly (not in
 *   this file): `ServicesFactory::getInstance()->newX()` or similar.
 *
 * Callbacks MUST NOT call `ServicesFactory::getInstance()->getX()` for the
 * same service the callback is wiring: `ServicesFactory::getX()` proxies back
 * to the container, so doing so would recurse infinitely.
 *
 * @codeCoverageIgnore
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
return [

	'SMW.Settings' => static function ( MediaWikiServices $services ): Settings {
		$settings = new Settings();

		$settings->setHookDispatcher(
			ServicesFactory::getInstance()->getHookDispatcher()
		);

		$settings->loadFromGlobals();

		return $settings;
	},

	'SMW.Store' => static function ( MediaWikiServices $services ): Store {
		$settings = ServicesFactory::getInstance()->getSettings();

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
			new Logger( LoggerFactory::getInstance( 'smw' ), Logger::ROLE_DEVELOPER )
		);

		return $instance;
	},

	'SMW.Cache' => static function ( MediaWikiServices $services ): Cache {
		// Mirror ServicesFactory::getCache() default-path behaviour: build a
		// MediaWikiCompositeCache for the global $smwgMainCacheType. Callers
		// that need a non-default cache type still go through
		// CacheFactory::newMediaWikiCompositeCache() directly.
		return ( new CacheFactory() )->newMediaWikiCompositeCache();
	},

	'SMW.EntityCache' => static function ( MediaWikiServices $services ): EntityCache {
		return new EntityCache(
			ServicesFactory::getInstance()->getCache()
		);
	},

	'SMW.JobQueue' => static function ( MediaWikiServices $services ): JobQueue {
		// JobQueueGroup is commonly swapped in tests (e.g. ChangePropagationNotifierTest);
		// resolving it through ServicesFactory honours the testOverrides map.
		return new JobQueue(
			ServicesFactory::getInstance()->getJobQueueGroup()
		);
	},

	'SMW.PermissionManager' => static function ( MediaWikiServices $services ): PermissionManager {
		return new PermissionManager( $services->getPermissionManager() );
	},

	'SMW.HookDispatcher' => static function ( MediaWikiServices $services ): HookDispatcher {
		return new HookDispatcher();
	},

	'SMW.RevisionGuard' => static function ( MediaWikiServices $services ): RevisionGuard {
		$revisionGuard = new RevisionGuard(
			$services->getRevisionLookup()
		);

		$revisionGuard->setHookDispatcher(
			ServicesFactory::getInstance()->getHookDispatcher()
		);

		return $revisionGuard;
	},

	'SMW.ConnectionManager' => static function ( MediaWikiServices $services ): ConnectionManager {
		return new ConnectionManager();
	},

	'SMW.SetupFile' => static function ( MediaWikiServices $services ): SetupFile {
		return new SetupFile();
	},

	'SMW.MediaWikiNsContentReader' => static function ( MediaWikiServices $services ): MediaWikiNsContentReader {
		$mediaWikiNsContentReader = new MediaWikiNsContentReader();

		$mediaWikiNsContentReader->setRevisionGuard(
			ServicesFactory::getInstance()->getRevisionGuard()
		);

		return $mediaWikiNsContentReader;
	},

	'SMW.ManualEntryLogger' => static function ( MediaWikiServices $services ): ManualEntryLogger {
		return new ManualEntryLogger();
	},

	'SMW.InMemoryPoolCache' => static function ( MediaWikiServices $services ): InMemoryPoolCache {
		return InMemoryPoolCache::getInstance();
	},

	'SMW.PropertyAnnotatorFactory' => static function ( MediaWikiServices $services ): AnnotatorFactory {
		return new AnnotatorFactory();
	},

	'SMW.ConnectionProvider' => static function ( MediaWikiServices $services ): ConnectionProvider {
		$connectionProvider = new ConnectionProvider();

		$connectionProvider->setLogger(
			ServicesFactory::getInstance()->getMediaWikiLogger()
		);

		return $connectionProvider;
	},

	'SMW.SchemaFactory' => static function ( MediaWikiServices $services ): SchemaFactory {
		return new SchemaFactory();
	},

	'SMW.ConstraintFactory' => static function ( MediaWikiServices $services ): ConstraintFactory {
		return new ConstraintFactory();
	},

	'SMW.ElasticFactory' => static function ( MediaWikiServices $services ): ElasticFactory {
		return new ElasticFactory();
	},

	'SMW.QueryCreator' => static function ( MediaWikiServices $services ): QueryCreator {
		$settings = ServicesFactory::getInstance()->getSettings();

		$queryCreator = new QueryCreator(
			ServicesFactory::getInstance()->getQueryFactory(),
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
		return new ParamListProcessor();
	},

	'SMW.FactboxText' => static function ( MediaWikiServices $services ): FactboxText {
		return new FactboxText();
	},

	'SMW.IteratorFactory' => static function ( MediaWikiServices $services ): IteratorFactory {
		return new IteratorFactory();
	},

	'SMW.JobFactory' => static function ( MediaWikiServices $services ): JobFactory {
		return new JobFactory();
	},

	'SMW.FactboxFactory' => static function ( MediaWikiServices $services ): FactboxFactory {
		return new FactboxFactory();
	},

	'SMW.QuerySourceFactory' => static function ( MediaWikiServices $services ): QuerySourceFactory {
		$servicesFactory = ServicesFactory::getInstance();

		return new QuerySourceFactory(
			$servicesFactory->getStore(),
			$servicesFactory->getSettings()->get( 'smwgQuerySources' )
		);
	},

	'SMW.QueryFactory' => static function ( MediaWikiServices $services ): QueryFactory {
		return new QueryFactory();
	},

	'SMW.DataItemFactory' => static function ( MediaWikiServices $services ): DataItemFactory {
		return new DataItemFactory();
	},

	'SMW.QueryDependencyLinksStoreFactory' => static function ( MediaWikiServices $services ): QueryDependencyLinksStoreFactory {
		return new QueryDependencyLinksStoreFactory();
	},

	'SMW.PropertySpecificationLookup' => static function ( MediaWikiServices $services ): SpecificationLookup {
		$servicesFactory = ServicesFactory::getInstance();
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
		$settings = $servicesFactory->getSettings();

		$protectionValidator = new ProtectionValidator(
			$servicesFactory->getStore(),
			$servicesFactory->getEntityCache(),
			$servicesFactory->getPermissionManager()
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

		return new TitlePermissions(
			$servicesFactory->getProtectionValidator(),
			$servicesFactory->getPermissionManager()
		);
	},

	'SMW.PropertyLabelFinder' => static function ( MediaWikiServices $services ): PropertyLabelFinder {
		$lang = Localizer::getInstance()->getLang();

		return new PropertyLabelFinder(
			ServicesFactory::getInstance()->getStore(),
			$lang->getPropertyLabels(),
			$lang->getCanonicalPropertyLabels(),
			$lang->getCanonicalDatatypeLabels()
		);
	},

	'SMW.InvalidateResultCacheEventListener' => static function ( MediaWikiServices $services ): InvalidateResultCacheEventListener {
		return new InvalidateResultCacheEventListener(
			ServicesFactory::getInstance()->getResultCache()
		);
	},

	'SMW.InvalidateEntityCacheEventListener' => static function ( MediaWikiServices $services ): InvalidateEntityCacheEventListener {
		return new InvalidateEntityCacheEventListener(
			ServicesFactory::getInstance()->getEntityCache()
		);
	},

	'SMW.InvalidatePropertySpecificationLookupCacheEventListener' => static function ( MediaWikiServices $services ): InvalidatePropertySpecificationLookupCacheEventListener {
		return new InvalidatePropertySpecificationLookupCacheEventListener(
			ServicesFactory::getInstance()->getPropertySpecificationLookup()
		);
	},

];
