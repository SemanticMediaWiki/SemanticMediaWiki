<?php

namespace SMW\Services;

use JobQueueGroup;
use MediaWiki\Language\Language;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Onoi\BlobStore\BlobStore;
use Onoi\Cache\Cache;
use Onoi\Cache\CacheFactory as OnoiCacheFactory;
use Onoi\Cache\FixedInMemoryLruCache;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SMW\CacheFactory;
use SMW\Connection\ConnectionManager;
use SMW\ConstraintFactory;
use SMW\DataItemFactory;
use SMW\DataModel\SemanticData;
use SMW\DataTypeRegistry;
use SMW\DataUpdater;
use SMW\DataValueFactory;
use SMW\DependencyValidator;
use SMW\DisplayTitleFinder;
use SMW\Elastic\ElasticFactory;
use SMW\EntityCache;
use SMW\EventDispatcher\EventDispatcher;
use SMW\Factbox\FactboxFactory;
use SMW\Factbox\FactboxText;
use SMW\Formatters\MessageFormatter;
use SMW\HierarchyLookup;
use SMW\Indicator\EntityExaminerIndicatorsFactory;
use SMW\InMemoryPoolCache;
use SMW\IteratorFactory;
use SMW\Listener\EventListener\EventHandler;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;
use SMW\Maintenance\MaintenanceFactory;
use SMW\MediaWiki\Api\TaskFactory;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\MediaWiki\IndicatorRegistry;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\Jobs\ContentParserFactory;
use SMW\MediaWiki\Jobs\PageUpdaterFactory;
use SMW\MediaWiki\Jobs\ParserDataFactory;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageUpdater;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\MediaWiki\PermissionManager;
use SMW\MediaWiki\RevisionGuard;
use SMW\NamespaceExaminer;
use SMW\Parser\ContentParser;
use SMW\Parser\InTextAnnotationParser;
use SMW\Parser\LinksProcessor;
use SMW\ParserData;
use SMW\ParserFunctionFactory;
use SMW\PostProcHandler;
use SMW\Property\AnnotatorFactory;
use SMW\Property\ChangePropagationNotifier;
use SMW\Property\RestrictionExaminer;
use SMW\Property\SpecificationLookup;
use SMW\PropertyLabelFinder;
use SMW\Protection\EditProtectionUpdater;
use SMW\Protection\ProtectionValidator;
use SMW\Query\Cache\CacheStats;
use SMW\Query\Cache\ResultCache;
use SMW\Query\Processor\ParamListProcessor;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\QuerySourceFactory;
use SMW\QueryFactory;
use SMW\Schema\SchemaFactory;
use SMW\SerializerFactory;
use SMW\Settings;
use SMW\SetupFile;
use SMW\Site;
use SMW\SiteReadiness;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Store;
use SMW\StoreFactory;
use SMW\Utils\Stats;
use SMW\Utils\TempFile;
use Wikimedia\Rdbms\IConnectionProvider;
use WikiPage;

/**
 * Application instances access for internal and external use
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ServicesFactory {

	private static ?ServicesFactory $instance = null;

	/**
	 * Test-override map keyed by service name. When a service name is present
	 * here the registered object is returned by the matching accessor and by
	 * the `singleton()`/`create()` shims. This is how
	 * `TestEnvironment::registerObject()` injects mocked services.
	 *
	 * @var array<string, mixed>
	 */
	private array $testOverrides = [];

	/**
	 * @since 2.0
	 */
	public function __construct( private $servicesFileDir = '' ) {
	}

	/**
	 * This method returns the global instance of the application factory.
	 *
	 * Reliance on global state is needed at entry points into SMW such as
	 * hook handlers, special pages and jobs, since there we tend to not
	 * have control over the object lifecycle. Pragmatically we might also
	 * want to use this when refactoring legacy code that already has the
	 * global state dependency. For new code very special justification is
	 * required to rely on global state.
	 *
	 * @since 2.0
	 *
	 * @return self
	 */
	public static function getInstance(): ServicesFactory {
		if ( self::$instance !== null ) {
			return self::$instance;
		}

		self::$instance = new self( $GLOBALS['smwgServicesFileDir'] );
		return self::$instance;
	}

	/**
	 * Test-only: drops the singleton and resets every `SMW.*` service on
	 * `MediaWikiServices` so the next test starts with a freshly-constructed
	 * service tree. Mirrors `MediaWikiIntegrationTestCase::resetServices()`,
	 * which SMW's unit tests do not inherit because they extend plain
	 * `TestCase`. Production code never calls this.
	 *
	 * @since 2.0
	 */
	public static function clear(): void {
		self::$instance = null;

		$mwServices = MediaWikiServices::getInstance();

		foreach ( $mwServices->getServiceNames() as $serviceName ) {
			if ( str_starts_with( $serviceName, 'SMW.' ) ) {
				$mwServices->resetServiceForTesting( $serviceName );
			}
		}
	}

	/**
	 * Registers a service object so that tests can inject mocked collaborators.
	 *
	 * The injected object is recorded in a test-override map consulted by the
	 * typed accessors, the factory methods and the `singleton()`/`create()`
	 * shims.
	 *
	 * @since 2.0
	 *
	 * @param string $objectName
	 * @param callable|mixed $objectSignature A service instance, or a callable
	 *  that receives this factory and returns the service instance.
	 */
	public function registerObject( $objectName, $objectSignature ): void {
		if ( is_callable( $objectSignature ) ) {
			$objectSignature = $objectSignature( $this );
		}

		$this->testOverrides[$objectName] = $objectSignature;
	}

	/**
	 * Whether a test override has been registered for `$objectName`. Wiring
	 * callbacks in `ServiceWiring.php` consult this so that
	 * `MediaWikiServices->getService('SMW.X')` honours the same `testOverrides`
	 * map as the typed accessors; without it, `ObjectFactory`-injected
	 * dependencies (e.g. `JobClasses` services) would bypass test mocks.
	 *
	 * @since 7.0.0
	 */
	public function hasTestOverride( string $objectName ): bool {
		return array_key_exists( $objectName, $this->testOverrides );
	}

	/**
	 * @deprecated since 7.0.0, no replacement
	 * @since 2.5
	 *
	 * @param string $file
	 */
	public function registerFromFile( $file ): void {
		$services = require $file;

		foreach ( $services as $name => $instantiator ) {
			$this->registerObject( $name, $instantiator( $this ) );
		}
	}

	/**
	 * @private
	 *
	 * @deprecated since 7.0.0, use a typed accessor or factory method instead
	 *
	 * @note Services called via this function are for internal use only and
	 * not to be relied upon for external access.
	 *
	 * @param string $serviceName
	 * @param mixed ...$args
	 *
	 * @return mixed Any registered service; the concrete type depends on
	 *  $serviceName. Declared as mixed so static analysis does not infer a
	 *  spurious union type from the routing logic and mismatch call sites.
	 */
	public function singleton( $serviceName, ...$args ) {
		if ( array_key_exists( $serviceName, $this->testOverrides ) ) {
			return $this->testOverrides[$serviceName];
		}

		return $this->routeToFactoryMethod( $serviceName, $args );
	}

	/**
	 * @private
	 *
	 * @deprecated since 7.0.0, use a typed accessor or factory method instead
	 *
	 * @note Services called via this function are for internal use only and
	 * not to be relied upon for external access.
	 *
	 * @since 2.5
	 *
	 * @param string $serviceName
	 * @param mixed ...$args
	 *
	 * @return mixed Any registered service; the concrete type depends on
	 *  $serviceName. Declared as mixed so static analysis does not infer a
	 *  spurious union type from the routing logic and mismatch call sites.
	 */
	public function create( $serviceName, ...$args ) {
		if ( array_key_exists( $serviceName, $this->testOverrides ) ) {
			return $this->testOverrides[$serviceName];
		}

		return $this->routeToFactoryMethod( $serviceName, $args );
	}

	/**
	 * Routes a legacy `singleton()`/`create()` call for a Bucket-B/C service
	 * to its dedicated factory method.
	 *
	 * The `...$args` spread forwards the variadic arguments collected by the
	 * deprecated `singleton()`/`create()` shims into fixed-arity factory
	 * methods. Phan cannot prove the argument count of such a variadic
	 * dispatch, so the per-line suppressions silence the resulting
	 * `PhanParamTooFewUnpack`/`PhanParamTooManyUnpack` false positives. Only
	 * the dispatch lines phan actually flags carry a suppression, to avoid
	 * triggering `UnusedPluginSuppression`. The dispatch is correct at
	 * runtime: callers pass the arguments the target factory method expects,
	 * and `getCacheFactory()` is genuinely argument-less (no caller forwards
	 * args to the `CacheFactory` service).
	 *
	 * @param string $serviceName
	 * @param array $args
	 */
	private function routeToFactoryMethod( string $serviceName, array $args ) {
		$handlers = [
			// Globalised SMW services and inlined MW-core passthroughs (all
			// resolved via the matching `ServicesFactory` accessor so that the
			// testOverrides map is honoured).
			'Store' => fn () => $this->getStore( ...$args ),
			'Settings' => fn () => $this->getSettings(),
			'EntityCache' => fn () => $this->getEntityCache(),
			'JobQueue' => fn () => $this->getJobQueue(),
			'Cache' => fn () => $this->getCache( ...$args ),
			'JobQueueGroup' => fn () => $this->getJobQueueGroup(),
			'PermissionManager' => fn () => $this->getPermissionManager(),
			'RevisionGuard' => fn () => $this->getRevisionGuard(),
			'ConnectionManager' => fn () => $this->getConnectionManager(),
			'SetupFile' => fn () => $this->getSetupFile(),
			'MediaWikiNsContentReader' => fn () => $this->getMediaWikiNsContentReader(),
			'InMemoryPoolCache' => fn () => $this->getInMemoryPoolCache(),
			'PropertyAnnotatorFactory' => fn () => $this->getPropertyAnnotatorFactory(),
			'ConnectionProvider' => fn () => $this->getConnectionProvider(),
			'SchemaFactory' => fn () => $this->getSchemaFactory(),
			'ConstraintFactory' => fn () => $this->getConstraintFactory(),
			'ElasticFactory' => fn () => $this->getElasticFactory(),
			'TaskFactory' => fn () => $this->getTaskFactory(),
			'QueryCreator' => fn () => $this->getQueryCreator(),
			'ParamListProcessor' => fn () => $this->getParamListProcessor(),
			'FactboxText' => fn () => $this->getFactboxText(),
			'IteratorFactory' => fn () => $this->getIteratorFactory(),
			'FactboxFactory' => fn () => $this->getFactboxFactory(),
			'QuerySourceFactory' => fn () => $this->getQuerySourceFactory( ...$args ),
			'QueryFactory' => fn () => $this->getQueryFactory(),
			'DataItemFactory' => fn () => $this->getDataItemFactory(),
			'QueryDependencyLinksStoreFactory' => fn () => $this->getQueryDependencyLinksStoreFactory(),
			'PropertySpecificationLookup' => fn () => $this->getPropertySpecificationLookup(),
			'ProtectionValidator' => fn () => $this->getProtectionValidator(),
			'TitlePermissions' => fn () => $this->getTitlePermissions(),
			'PropertyLabelFinder' => fn () => $this->getPropertyLabelFinder(),
			'InvalidateResultCacheEventListener' => fn () => $this->getInvalidateResultCacheEventListener(),
			'InvalidateEntityCacheEventListener' => fn () => $this->getInvalidateEntityCacheEventListener(),
			'InvalidatePropertySpecificationLookupCacheEventListener' => fn () => $this->getInvalidatePropertySpecificationLookupCacheEventListener(),
			'SerializerFactory' => fn () => $this->getSerializerFactory(),
			'ParserFunctionFactory' => fn () => $this->getParserFunctionFactory(),
			'MaintenanceFactory' => fn () => $this->getMaintenanceFactory(),
			'CacheFactory' => fn () => $this->getCacheFactory(),
			'PageCreator' => fn () => $this->getPageCreator(),
			'ContentParserFactory' => fn () => $this->getContentParserFactory(),
			'ParserDataFactory' => fn () => $this->getParserDataFactory(),
			'PageUpdaterFactory' => fn () => $this->getPageUpdaterFactory(),
			'Logger' => fn () => $this->getLogger(),
			'MwCollaboratorFactory' => fn () => $this->getMwCollaboratorFactory(),
			'NamespaceExaminer' => fn () => $this->getNamespaceExaminer(),
			'DataValueServiceFactory' => fn () => $this->getDataValueServiceFactory(),
			'ImporterServiceFactory' => fn () => $this->getImporterServiceFactory(),
			'HierarchyLookup' => fn () => $this->newHierarchyLookup( ...$args ),
			'DisplayTitleFinder' => fn () => $this->newDisplayTitleFinder( ...$args ),

			// Bucket-B/C SMW services constructed fresh per call.
			'IndicatorRegistry' => fn () => $this->newIndicatorRegistry( ...$args ),
			// @phan-suppress-next-line PhanParamTooFewUnpack
			'ParserData' => fn () => $this->newParserData( ...$args ),
			'LinksProcessor' => fn () => $this->newLinksProcessor(),
			// @phan-suppress-next-line PhanParamTooFewUnpack
			'MessageFormatter' => fn () => $this->newMessageFormatter( ...$args ),
			'PageUpdater' => fn () => $this->newPageUpdater( ...$args ),
			// @phan-suppress-next-line PhanParamTooFewUnpack
			'ContentParser' => fn () => $this->newContentParser( ...$args ),
			'DeferredCallableUpdate' => fn () => $this->newDeferredCallableUpdate( ...$args ),
			'DeferredTransactionalCallableUpdate' => fn () => $this->newDeferredTransactionalCallableUpdate( ...$args ),
			'TempFile' => fn () => $this->newTempFile(),
			// @phan-suppress-next-line PhanParamTooFewUnpack
			'PostProcHandler' => fn () => $this->newPostProcHandler( ...$args ),
			// @phan-suppress-next-line PhanParamTooFewUnpack
			'BlobStore' => fn () => $this->newBlobStore( ...$args ),
			'ResultCache' => fn () => $this->getResultCache( ...$args ),
			// @phan-suppress-next-line PhanParamTooFewUnpack
			'Stats' => fn () => $this->newStats( ...$args ),
			// @phan-suppress-next-line PhanParamTooFewUnpack
			'EditProtectionUpdater' => fn () => $this->newEditProtectionUpdater( ...$args ),
			'PropertyRestrictionExaminer' => fn () => $this->newPropertyRestrictionExaminer(),
			'MagicWordsFinder' => fn () => $this->newMagicWordsFinder( ...$args ),
			'Parser' => fn () => $this->newParser(),
			'RevisionLookup' => fn () => $this->newRevisionLookup(),
			// @phan-suppress-next-line PhanParamTooFewUnpack
			'DefaultSearchEngineTypeForDB' => fn () => $this->getDefaultSearchEngineTypeForDB( ...$args ),
			// @phan-suppress-next-line PhanParamTooFewUnpack
			'WikiPage' => fn () => $this->newWikiPage( ...$args ),
			'FixedInMemoryLruCache' => fn () => $this->newFixedInMemoryLruCache( ...$args ),
			'JobFactory' => fn () => $this->newJobFactory(),
		];

		if ( !isset( $handlers[$serviceName] ) ) {
			throw new RuntimeException( "$serviceName is an unknown service!" );
		}

		return $handlers[$serviceName]();
	}

	/**
	 * @since 3.2
	 *
	 * @param User|null $user
	 *
	 * @return PermissionExaminer
	 */
	public function newPermissionExaminer( ?User $user = null ): PermissionExaminer {
		return new PermissionExaminer( $this->getPermissionManager(), $user );
	}

	/**
	 * @since 2.0
	 */
	public function newSerializerFactory(): SerializerFactory {
		return $this->getSerializerFactory();
	}

	/**
	 * @since 7.0.0
	 */
	public function getSerializerFactory(): SerializerFactory {
		if ( array_key_exists( 'SerializerFactory', $this->testOverrides ) ) {
			return $this->testOverrides['SerializerFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.SerializerFactory' );
	}

	/**
	 * @since 2.0
	 */
	public function newJobFactory(): JobFactory {
		if ( array_key_exists( 'JobFactory', $this->testOverrides ) ) {
			return $this->testOverrides['JobFactory'];
		}

		return new JobFactory();
	}

	/**
	 * @since 7.0.0
	 */
	public function getJobFactory(): JobFactory {
		if ( array_key_exists( 'JobFactory', $this->testOverrides ) ) {
			return $this->testOverrides['JobFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.JobFactory' );
	}

	/**
	 * @since 2.1
	 */
	public function newParserFunctionFactory(): ParserFunctionFactory {
		return $this->getParserFunctionFactory();
	}

	/**
	 * @since 7.0.0
	 */
	public function getParserFunctionFactory(): ParserFunctionFactory {
		if ( array_key_exists( 'ParserFunctionFactory', $this->testOverrides ) ) {
			return $this->testOverrides['ParserFunctionFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ParserFunctionFactory' );
	}

	/**
	 * @since 2.2
	 */
	public function newMaintenanceFactory(): MaintenanceFactory {
		return $this->getMaintenanceFactory();
	}

	/**
	 * @since 7.0.0
	 */
	public function getMaintenanceFactory(): MaintenanceFactory {
		if ( array_key_exists( 'MaintenanceFactory', $this->testOverrides ) ) {
			return $this->testOverrides['MaintenanceFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.MaintenanceFactory' );
	}

	/**
	 * @since 2.2
	 */
	public function newCacheFactory(): CacheFactory {
		return $this->getCacheFactory();
	}

	/**
	 * @since 2.2
	 */
	public function getCacheFactory(): CacheFactory {
		if ( array_key_exists( 'CacheFactory', $this->testOverrides ) ) {
			return $this->testOverrides['CacheFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.CacheFactory' );
	}

	/**
	 * @since 2.5
	 *
	 * @param string|null $source
	 *
	 * @return QuerySourceFactory
	 */
	public function getQuerySourceFactory( $source = null ): QuerySourceFactory {
		if ( array_key_exists( 'QuerySourceFactory', $this->testOverrides ) ) {
			return $this->testOverrides['QuerySourceFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.QuerySourceFactory' );
	}

	/**
	 * @since 2.0
	 *
	 * @return Store
	 */
	public function getStore( $store = null ): Store {
		if ( array_key_exists( 'Store', $this->testOverrides ) ) {
			return $this->testOverrides['Store'];
		}

		// SMW.Store on the global container is the default store. When the
		// caller requests a non-default store class, build it inline; this
		// branch is rare and preserves the existing signature for callers like
		// Maintenance scripts.
		if ( $store !== null && $store !== '' ) {
			$settings = $this->getSettings();
			$instance = StoreFactory::getStore( $store );

			$configs = [
				'smwgDefaultStore',
				'smwgAutoRefreshSubject',
				'smwgEnableUpdateJobs',
				'smwgQEqualitySupport',
				'smwgElasticsearchConfig'
			];

			foreach ( $configs as $config ) {
				$instance->setOption( $config, $settings->get( $config ) );
			}

			$instance->setLogger(
				LoggerFactory::getInstance( 'smw' )
			);

			return $instance;
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.Store' );
	}

	/**
	 * @since 3.1
	 *
	 * @param bool $addEntityExaminer
	 *
	 * @return IndicatorRegistry
	 */
	public function newIndicatorRegistry( bool $addEntityExaminer = false ): IndicatorRegistry {
		$indicatorRegistry = new IndicatorRegistry();

		if ( !$addEntityExaminer ) {
			return $indicatorRegistry;
		}

		$entityExaminerIndicatorsFactory = new EntityExaminerIndicatorsFactory();

		$entityExaminerIndicatorProvider = $entityExaminerIndicatorsFactory->newEntityExaminerIndicatorProvider(
			$this->getStore()
		);

		$indicatorRegistry->addIndicatorProvider(
			$entityExaminerIndicatorProvider
		);

		return $indicatorRegistry;
	}

	/**
	 * @since 2.0
	 *
	 * @return Settings
	 */
	public function getSettings(): Settings {
		if ( array_key_exists( 'Settings', $this->testOverrides ) ) {
			return $this->testOverrides['Settings'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.Settings' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getDataTypeRegistry(): DataTypeRegistry {
		if ( array_key_exists( 'DataTypeRegistry', $this->testOverrides ) ) {
			return $this->testOverrides['DataTypeRegistry'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.DataTypeRegistry' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getSiteReadiness(): SiteReadiness {
		if ( array_key_exists( 'SiteReadiness', $this->testOverrides ) ) {
			return $this->testOverrides['SiteReadiness'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.SiteReadiness' );
	}

	/**
	 * @since 3.0
	 *
	 * @return ConnectionManager
	 */
	public function getConnectionManager(): ConnectionManager {
		if ( array_key_exists( 'ConnectionManager', $this->testOverrides ) ) {
			return $this->testOverrides['ConnectionManager'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ConnectionManager' );
	}

	/**
	 * @since 3.1
	 *
	 * @return EventDispatcher
	 */
	public function getEventDispatcher(): EventDispatcher {
		return EventHandler::getInstance()->getEventDispatcher();
	}

	/**
	 * @since 2.0
	 *
	 * @return PageCreator
	 */
	public function newPageCreator() {
		return $this->getPageCreator();
	}

	/**
	 * @since 7.0.0
	 */
	public function getPageCreator(): PageCreator {
		if ( array_key_exists( 'PageCreator', $this->testOverrides ) ) {
			return $this->testOverrides['PageCreator'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.PageCreator' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getContentParserFactory(): ContentParserFactory {
		if ( array_key_exists( 'ContentParserFactory', $this->testOverrides ) ) {
			return $this->testOverrides['ContentParserFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ContentParserFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getParserDataFactory(): ParserDataFactory {
		if ( array_key_exists( 'ParserDataFactory', $this->testOverrides ) ) {
			return $this->testOverrides['ParserDataFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ParserDataFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getPageUpdaterFactory(): PageUpdaterFactory {
		if ( array_key_exists( 'PageUpdaterFactory', $this->testOverrides ) ) {
			return $this->testOverrides['PageUpdaterFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.PageUpdaterFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getLogger(): LoggerInterface {
		if ( array_key_exists( 'Logger', $this->testOverrides ) ) {
			return $this->testOverrides['Logger'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.Logger' );
	}

	/**
	 * @since 2.5
	 */
	public function newPageUpdater( $connection = null, ?TransactionalCallableUpdate $transactionalCallableUpdate = null ): PageUpdater {
		if ( array_key_exists( 'PageUpdater', $this->testOverrides ) ) {
			return $this->testOverrides['PageUpdater'];
		}

		if ( $connection === null ) {
			$connection = $this->getStore()->getConnection( 'mw.db' );
		}

		if ( $transactionalCallableUpdate === null ) {
			$transactionalCallableUpdate = $this->newDeferredTransactionalCallableUpdate();
		}

		$pageUpdater = new PageUpdater( $connection, $transactionalCallableUpdate );

		$pageUpdater->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		// https://phabricator.wikimedia.org/T154427
		// It is unclear what changed in MW 1.29 but it has been observed that
		// executing a HTMLCacheUpdate from within an transaction can lead to a
		// "ErrorException ... 1 buffered job ... HTMLCacheUpdateJob never
		// inserted" hence disable the update functionality
		$pageUpdater->isHtmlCacheUpdate(
			false
		);

		return $pageUpdater;
	}

	/**
	 * @since 2.5
	 */
	public function getIteratorFactory(): IteratorFactory {
		if ( array_key_exists( 'IteratorFactory', $this->testOverrides ) ) {
			return $this->testOverrides['IteratorFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.IteratorFactory' );
	}

	/**
	 * @since 2.5
	 *
	 * @return DataValueFactory
	 */
	public function getDataValueFactory(): DataValueFactory {
		return DataValueFactory::getInstance();
	}

	/**
	 * @since 2.0
	 *
	 * @return Cache
	 */
	public function getCache( $cacheType = null ) {
		if ( array_key_exists( 'Cache', $this->testOverrides ) ) {
			return $this->testOverrides['Cache'];
		}

		// SMW.Cache on the global container is the default-type cache.
		// Non-default cache-type requests still build a fresh
		// MediaWikiCompositeCache (callers depend on type-specific caches and
		// the global registration only covers the default).
		if ( $cacheType !== null ) {
			return ( new CacheFactory() )->newMediaWikiCompositeCache( $cacheType );
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.Cache' );
	}

	/**
	 * @since 3.1
	 */
	public function getEntityCache(): EntityCache {
		if ( array_key_exists( 'EntityCache', $this->testOverrides ) ) {
			return $this->testOverrides['EntityCache'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.EntityCache' );
	}

	/**
	 * @since 2.0
	 *
	 * @return InTextAnnotationParser
	 */
	public function newInTextAnnotationParser( ParserData $parserData ): InTextAnnotationParser {
		$mwCollaboratorFactory = $this->newMwCollaboratorFactory();

		$linksProcessor = $this->newLinksProcessor();
		$settings = $this->getSettings();

		$linksProcessor->isStrictMode(
			$settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_STRICT )
		);

		$inTextAnnotationParser = new InTextAnnotationParser(
			$parserData,
			$linksProcessor,
			$mwCollaboratorFactory->newMagicWordsFinder(),
			$mwCollaboratorFactory->newRedirectTargetFinder()
		);

		$inTextAnnotationParser->isLinksInValues(
			$settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_LINV )
		);

		$inTextAnnotationParser->showErrors(
			$settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_INL_ERROR )
		);

		$inTextAnnotationParser->setHookContainer(
			MediaWikiServices::getInstance()->getHookContainer()
		);

		return $inTextAnnotationParser;
	}

	/**
	 * @since 7.0.0
	 */
	public function newLinksProcessor(): LinksProcessor {
		return new LinksProcessor();
	}

	/**
	 * @since 7.0.0
	 */
	public function newMessageFormatter( Language $language ): MessageFormatter {
		return new MessageFormatter( $language );
	}

	/**
	 * @since 2.0
	 *
	 * @return ParserData
	 */
	public function newParserData( Title $title, ParserOutput $parserOutput ) {
		if ( array_key_exists( 'ParserData', $this->testOverrides ) ) {
			return $this->testOverrides['ParserData'];
		}

		$parserData = new ParserData( $title, $parserOutput );

		$parserData->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		return $parserData;
	}

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 *
	 * @return ContentParser
	 */
	public function newContentParser( Title $title ): ContentParser {
		if ( array_key_exists( 'ContentParser', $this->testOverrides ) ) {
			return $this->testOverrides['ContentParser'];
		}

		$contentParser = new ContentParser(
			$title,
			$this->newParser()
		);

		$contentParser->setRevisionGuard(
			$this->getRevisionGuard()
		);

		return $contentParser;
	}

	/**
	 * @since 2.1
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return DataUpdater
	 */
	public function newDataUpdater( SemanticData $semanticData ): DataUpdater {
		$settings = $this->getSettings();

		$changePropagationNotifier = new ChangePropagationNotifier(
			$this->getStore(),
			$this->newSerializerFactory()
		);

		$changePropagationNotifier->setPropertyList(
			$settings->get( 'smwgChangePropagationWatchlist' )
		);

		$changePropagationNotifier->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$dataUpdater = new DataUpdater(
			$this->getStore(),
			$semanticData,
			$changePropagationNotifier,
			$this->getPageCreator(),
			$this->getEventDispatcher()
		);

		$dataUpdater->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$dataUpdater->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		$dataUpdater->setRevisionGuard(
			$this->getRevisionGuard()
		);

		return $dataUpdater;
	}

	/**
	 * @since 2.1
	 *
	 * @return MwCollaboratorFactory
	 */
	public function newMwCollaboratorFactory(): MwCollaboratorFactory {
		return $this->getMwCollaboratorFactory();
	}

	/**
	 * @since 7.0.0
	 */
	public function getMwCollaboratorFactory(): MwCollaboratorFactory {
		if ( array_key_exists( 'MwCollaboratorFactory', $this->testOverrides ) ) {
			return $this->testOverrides['MwCollaboratorFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.MwCollaboratorFactory' );
	}

	/**
	 * @since 2.1
	 */
	public function getNamespaceExaminer(): NamespaceExaminer {
		if ( array_key_exists( 'NamespaceExaminer', $this->testOverrides ) ) {
			return $this->testOverrides['NamespaceExaminer'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.NamespaceExaminer' );
	}

	/**
	 * @since 7.0.0
	 */
	public function newNamespaceExaminer(): NamespaceExaminer {
		return $this->getNamespaceExaminer();
	}

	/**
	 * @since 2.4
	 */
	public function getPropertySpecificationLookup(): SpecificationLookup {
		if ( array_key_exists( 'PropertySpecificationLookup', $this->testOverrides ) ) {
			return $this->testOverrides['PropertySpecificationLookup'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.PropertySpecificationLookup' );
	}

	/**
	 * @since 2.4
	 */
	public function newHierarchyLookup( $store = null, $cacheType = null ): HierarchyLookup {
		if ( array_key_exists( 'HierarchyLookup', $this->testOverrides ) ) {
			return $this->testOverrides['HierarchyLookup'];
		}

		// SMW.HierarchyLookup on the global container uses the default store
		// and cache; when the caller asks for a non-default combination, build
		// the instance inline so the override-only entry-points keep working.
		if ( $store === null && $cacheType === null ) {
			return $this->getHierarchyLookup();
		}

		$hierarchyLookup = new HierarchyLookup(
			$store ?? $this->getStore(),
			$this->getCache( $cacheType )
		);

		$hierarchyLookup->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		$hierarchyLookup->setSubcategoryDepth(
			$this->getSettings()->get( 'smwgQSubcategoryDepth' )
		);

		$hierarchyLookup->setSubpropertyDepth(
			$this->getSettings()->get( 'smwgQSubpropertyDepth' )
		);

		return $hierarchyLookup;
	}

	/**
	 * @since 7.0.0
	 */
	public function getHierarchyLookup(): HierarchyLookup {
		if ( array_key_exists( 'HierarchyLookup', $this->testOverrides ) ) {
			return $this->testOverrides['HierarchyLookup'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.HierarchyLookup' );
	}

	/**
	 * @since 7.0.0
	 */
	public function newDisplayTitleFinder( $store = null ): DisplayTitleFinder {
		if ( array_key_exists( 'DisplayTitleFinder', $this->testOverrides ) ) {
			return $this->testOverrides['DisplayTitleFinder'];
		}

		// SMW.DisplayTitleFinder on the global container uses the default
		// store; when the caller passes a custom store, build the instance
		// inline so the override-only entry-points keep working.
		if ( $store === null ) {
			return $this->getDisplayTitleFinder();
		}

		$settings = $this->getSettings();

		$displayTitleFinder = new DisplayTitleFinder(
			$store,
			$this->getEntityCache()
		);

		$displayTitleFinder->setCanUse(
			$settings->isFlagSet( 'smwgDVFeatures', SMW_DV_WPV_DTITLE )
		);

		return $displayTitleFinder;
	}

	/**
	 * @since 7.0.0
	 */
	public function getDisplayTitleFinder(): DisplayTitleFinder {
		if ( array_key_exists( 'DisplayTitleFinder', $this->testOverrides ) ) {
			return $this->testOverrides['DisplayTitleFinder'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.DisplayTitleFinder' );
	}

	/**
	 * @since 7.0.0
	 */
	public function newMagicWordsFinder( ?ParserOutput $parserOutput = null ): MagicWordsFinder {
		if ( array_key_exists( 'MagicWordsFinder', $this->testOverrides ) ) {
			return $this->testOverrides['MagicWordsFinder'];
		}

		return new MagicWordsFinder(
			$parserOutput,
			MediaWikiServices::getInstance()->getMagicWordFactory()
		);
	}

	/**
	 * @since 7.0.0
	 */
	public function newDependencyValidator( string $eTag, int $cacheTTL ): DependencyValidator {
		if ( array_key_exists( 'DependencyValidator', $this->testOverrides ) ) {
			return $this->testOverrides['DependencyValidator'];
		}

		$queryDependencyLinksStoreFactory = $this->getQueryDependencyLinksStoreFactory();

		return new DependencyValidator(
			$this->getNamespaceExaminer(),
			$queryDependencyLinksStoreFactory->newDependencyLinksValidator(),
			$this->getEntityCache(),
			$eTag,
			$cacheTTL,
			$this->getEventDispatcher()
		);
	}

	/**
	 * @since 7.0.0
	 */
	public function newEditProtectionUpdater( WikiPage $wikiPage, ?User $user = null ): EditProtectionUpdater {
		if ( array_key_exists( 'EditProtectionUpdater', $this->testOverrides ) ) {
			return $this->testOverrides['EditProtectionUpdater'];
		}

		$editProtectionUpdater = new EditProtectionUpdater(
			$wikiPage,
			$user
		);

		$editProtectionUpdater->setEditProtectionRight(
			$this->getSettings()->get( 'smwgEditProtectionRight' )
		);

		$editProtectionUpdater->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		return $editProtectionUpdater;
	}

	/**
	 * @since 7.0.0
	 */
	public function newPropertyRestrictionExaminer(): RestrictionExaminer {
		if ( array_key_exists( 'PropertyRestrictionExaminer', $this->testOverrides ) ) {
			return $this->testOverrides['PropertyRestrictionExaminer'];
		}

		$propertyRestrictionExaminer = new RestrictionExaminer();

		$propertyRestrictionExaminer->setCreateProtectionRight(
			$this->getSettings()->get( 'smwgCreateProtectionRight' )
		);

		return $propertyRestrictionExaminer;
	}

	/**
	 * @since 7.0.0
	 */
	public function newTempFile(): TempFile {
		return new TempFile();
	}

	/**
	 * @since 7.0.0
	 */
	public function newBlobStore( $namespace, $cacheType = null, $ttl = 0 ): BlobStore {
		if ( array_key_exists( 'BlobStore', $this->testOverrides ) ) {
			return $this->testOverrides['BlobStore'];
		}

		$cacheFactory = $this->getCacheFactory();

		$blobStore = new BlobStore(
			$namespace,
			$cacheFactory->newMediaWikiCompositeCache( $cacheType )
		);

		$blobStore->setNamespacePrefix(
			$cacheFactory->getCachePrefix()
		);

		$blobStore->setExpiryInSeconds(
			$ttl
		);

		$blobStore->setUsageState(
			$cacheType !== CACHE_NONE && $cacheType !== false
		);

		return $blobStore;
	}

	/**
	 * @since 7.0.0
	 */
	public function getResultCache( $cacheType = null ): ResultCache {
		if ( array_key_exists( 'ResultCache', $this->testOverrides ) ) {
			return $this->testOverrides['ResultCache'];
		}

		$cacheFactory = $this->getCacheFactory();
		$settings = $this->getSettings();

		$cacheType = $cacheType === null ? $settings->get( 'smwgQueryResultCacheType' ) : $cacheType;

		// Explicitly use the CACHE_DB to access a SqlBagOstuff instance
		// for a bit more persistence
		$cacheStats = new CacheStats(
			$cacheFactory->newMediaWikiCache( CACHE_DB ),
			ResultCache::STATSD_ID
		);

		$resultCache = new ResultCache(
			$this->getStore(),
			$this->getQueryFactory(),
			$this->newBlobStore(
				ResultCache::CACHE_NAMESPACE,
				$cacheType,
				$settings->get( 'smwgQueryResultCacheLifetime' )
			),
			$cacheStats
		);

		$resultCache->setCacheKeyExtension(
			// If the mix of dataTypes changes then modify the hash
			$settings->get( 'smwgFulltextSearchIndexableDataTypes' ) .

			// If the collation is altered then modify the hash as it
			// is likely that the sort order of results change
			$settings->get( 'smwgEntityCollation' ) .

			// Changing the sobj has computation should invalidate
			// existing caches to avoid oudated references SOBJ IDs
			$settings->get( 'smwgUseComparableContentHash' )
		);

		$resultCache->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		$resultCache->setNonEmbeddedCacheLifetime(
			$settings->get( 'smwgQueryResultNonEmbeddedCacheLifetime' )
		);

		return $resultCache;
	}

	/**
	 * @since 7.0.0
	 */
	public function newStats( $id ): Stats {
		if ( array_key_exists( 'Stats', $this->testOverrides ) ) {
			return $this->testOverrides['Stats'];
		}

		$cacheFactory = $this->getCacheFactory();

		// Explicitly use the DB to access a SqlBagOstuff instance
		return new Stats(
			$cacheFactory->newMediaWikiCache( CACHE_DB ),
			$id
		);
	}

	/**
	 * @since 7.0.0
	 */
	public function getDataValueServiceFactory(): DataValueServiceFactory {
		if ( array_key_exists( 'DataValueServiceFactory', $this->testOverrides ) ) {
			return $this->testOverrides['DataValueServiceFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.DataValueServiceFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getImporterServiceFactory(): ImporterServiceFactory {
		if ( array_key_exists( 'ImporterServiceFactory', $this->testOverrides ) ) {
			return $this->testOverrides['ImporterServiceFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ImporterServiceFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function newParser(): Parser {
		return MediaWikiServices::getInstance()->getParser();
	}

	/**
	 * @since 7.0.0
	 */
	public function newRevisionLookup(): RevisionLookup {
		return MediaWikiServices::getInstance()->getRevisionLookup();
	}

	/**
	 * @since 7.0.0
	 *
	 * @param Title $title
	 *
	 * @return WikiPage
	 */
	public function newWikiPage( Title $title ) {
		return $this->newPageCreator()->createPage( $title );
	}

	/**
	 * @since 7.0.0
	 */
	public function newFixedInMemoryLruCache( int $cacheSize = 500 ): FixedInMemoryLruCache {
		return OnoiCacheFactory::getInstance()->newFixedInMemoryLruCache( $cacheSize );
	}

	/**
	 * @since 7.0.0
	 *
	 * @param IConnectionProvider $dbProvider
	 *
	 * @return string
	 */
	public function getDefaultSearchEngineTypeForDB( IConnectionProvider $dbProvider ): string {
		return MediaWikiServices::getInstance()->getSearchEngineFactory()->getSearchEngineClass( $dbProvider );
	}

	/**
	 * @since 2.4
	 */
	public function getPropertyLabelFinder(): PropertyLabelFinder {
		if ( array_key_exists( 'PropertyLabelFinder', $this->testOverrides ) ) {
			return $this->testOverrides['PropertyLabelFinder'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.PropertyLabelFinder' );
	}

	/**
	 * @since 2.4
	 */
	public function getMediaWikiNsContentReader(): MediaWikiNsContentReader {
		if ( array_key_exists( 'MediaWikiNsContentReader', $this->testOverrides ) ) {
			return $this->testOverrides['MediaWikiNsContentReader'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.MediaWikiNsContentReader' );
	}

	/**
	 * @since 2.4
	 */
	public function getInMemoryPoolCache(): InMemoryPoolCache {
		if ( array_key_exists( 'InMemoryPoolCache', $this->testOverrides ) ) {
			return $this->testOverrides['InMemoryPoolCache'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.InMemoryPoolCache' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getPermissionManager(): PermissionManager {
		if ( array_key_exists( 'PermissionManager', $this->testOverrides ) ) {
			return $this->testOverrides['PermissionManager'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.PermissionManager' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getJobQueueGroup(): JobQueueGroup {
		if ( array_key_exists( 'JobQueueGroup', $this->testOverrides ) ) {
			return $this->testOverrides['JobQueueGroup'];
		}

		return MediaWikiServices::getInstance()->getJobQueueGroup();
	}

	/**
	 * @since 7.0.0
	 */
	public function getSetupFile(): SetupFile {
		if ( array_key_exists( 'SetupFile', $this->testOverrides ) ) {
			return $this->testOverrides['SetupFile'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.SetupFile' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getRevisionGuard(): RevisionGuard {
		if ( array_key_exists( 'RevisionGuard', $this->testOverrides ) ) {
			return $this->testOverrides['RevisionGuard'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.RevisionGuard' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getPropertyAnnotatorFactory(): AnnotatorFactory {
		if ( array_key_exists( 'PropertyAnnotatorFactory', $this->testOverrides ) ) {
			return $this->testOverrides['PropertyAnnotatorFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.PropertyAnnotatorFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getConnectionProvider(): ConnectionProvider {
		if ( array_key_exists( 'ConnectionProvider', $this->testOverrides ) ) {
			return $this->testOverrides['ConnectionProvider'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ConnectionProvider' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getSchemaFactory(): SchemaFactory {
		if ( array_key_exists( 'SchemaFactory', $this->testOverrides ) ) {
			return $this->testOverrides['SchemaFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.SchemaFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getConstraintFactory(): ConstraintFactory {
		if ( array_key_exists( 'ConstraintFactory', $this->testOverrides ) ) {
			return $this->testOverrides['ConstraintFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ConstraintFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getElasticFactory(): ElasticFactory {
		if ( array_key_exists( 'ElasticFactory', $this->testOverrides ) ) {
			return $this->testOverrides['ElasticFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ElasticFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getTaskFactory(): TaskFactory {
		if ( array_key_exists( 'TaskFactory', $this->testOverrides ) ) {
			return $this->testOverrides['TaskFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.TaskFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getQueryCreator(): QueryCreator {
		if ( array_key_exists( 'QueryCreator', $this->testOverrides ) ) {
			return $this->testOverrides['QueryCreator'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.QueryCreator' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getParamListProcessor(): ParamListProcessor {
		if ( array_key_exists( 'ParamListProcessor', $this->testOverrides ) ) {
			return $this->testOverrides['ParamListProcessor'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ParamListProcessor' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getFactboxFactory(): FactboxFactory {
		if ( array_key_exists( 'FactboxFactory', $this->testOverrides ) ) {
			return $this->testOverrides['FactboxFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.FactboxFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getQueryDependencyLinksStoreFactory(): QueryDependencyLinksStoreFactory {
		if ( array_key_exists( 'QueryDependencyLinksStoreFactory', $this->testOverrides ) ) {
			return $this->testOverrides['QueryDependencyLinksStoreFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.QueryDependencyLinksStoreFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getProtectionValidator(): ProtectionValidator {
		if ( array_key_exists( 'ProtectionValidator', $this->testOverrides ) ) {
			return $this->testOverrides['ProtectionValidator'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.ProtectionValidator' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getTitlePermissions(): TitlePermissions {
		if ( array_key_exists( 'TitlePermissions', $this->testOverrides ) ) {
			return $this->testOverrides['TitlePermissions'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.TitlePermissions' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getInvalidateResultCacheEventListener(): InvalidateResultCacheEventListener {
		if ( array_key_exists( 'InvalidateResultCacheEventListener', $this->testOverrides ) ) {
			return $this->testOverrides['InvalidateResultCacheEventListener'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.InvalidateResultCacheEventListener' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getInvalidateEntityCacheEventListener(): InvalidateEntityCacheEventListener {
		if ( array_key_exists( 'InvalidateEntityCacheEventListener', $this->testOverrides ) ) {
			return $this->testOverrides['InvalidateEntityCacheEventListener'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.InvalidateEntityCacheEventListener' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getInvalidatePropertySpecificationLookupCacheEventListener(): InvalidatePropertySpecificationLookupCacheEventListener {
		if ( array_key_exists( 'InvalidatePropertySpecificationLookupCacheEventListener', $this->testOverrides ) ) {
			return $this->testOverrides['InvalidatePropertySpecificationLookupCacheEventListener'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.InvalidatePropertySpecificationLookupCacheEventListener' );
	}

	/**
	 * @since 2.4
	 *
	 * @param callable|null $callback
	 */
	public function newDeferredCallableUpdate( ?callable $callback = null ): CallableUpdate {
		if ( array_key_exists( 'DeferredCallableUpdate', $this->testOverrides ) ) {
			return $this->testOverrides['DeferredCallableUpdate'];
		}

		$deferredCallableUpdate = new CallableUpdate( $callback );

		$deferredCallableUpdate->isDeferrableUpdate(
			$this->getSettings()->get( 'smwgEnabledDeferredUpdate' )
		);

		$deferredCallableUpdate->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		$deferredCallableUpdate->isCommandLineMode(
			Site::isCommandLineMode()
		);

		return $deferredCallableUpdate;
	}

	/**
	 * @since 3.0
	 *
	 * @param callable|null $callback
	 */
	public function newDeferredTransactionalCallableUpdate( ?callable $callback = null, ?Database $connection = null ): TransactionalCallableUpdate {
		if ( $connection === null ) {
			$connection = $this->getStore()->getConnection( 'mw.db' );
		}

		$deferredTransactionalUpdate = new TransactionalCallableUpdate( $callback, $connection );

		$deferredTransactionalUpdate->isDeferrableUpdate(
			$this->getSettings()->get( 'smwgEnabledDeferredUpdate' )
		);

		$deferredTransactionalUpdate->setLogger(
			LoggerFactory::getInstance( 'smw' )
		);

		$deferredTransactionalUpdate->isCommandLineMode(
			Site::isCommandLineMode()
		);

		return $deferredTransactionalUpdate;
	}

	/**
	 * @since 2.5
	 */
	public function getDataItemFactory(): DataItemFactory {
		if ( array_key_exists( 'DataItemFactory', $this->testOverrides ) ) {
			return $this->testOverrides['DataItemFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.DataItemFactory' );
	}

	/**
	 * @since 2.5
	 */
	public function getQueryFactory(): QueryFactory {
		if ( array_key_exists( 'QueryFactory', $this->testOverrides ) ) {
			return $this->testOverrides['QueryFactory'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.QueryFactory' );
	}

	/**
	 * @since 2.5
	 */

	/**
	 * @since 3.0
	 */
	public function getJobQueue(): JobQueue {
		if ( array_key_exists( 'JobQueue', $this->testOverrides ) ) {
			return $this->testOverrides['JobQueue'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.JobQueue' );
	}

	/**
	 * @since 4.1.1
	 */
	public function getFactboxText(): FactboxText {
		if ( array_key_exists( 'FactboxText', $this->testOverrides ) ) {
			return $this->testOverrides['FactboxText'];
		}

		return MediaWikiServices::getInstance()->getService( 'SMW.FactboxText' );
	}

	public function newPostProcHandler( ParserOutput $parserOutput ): PostProcHandler {
		$settings = $this->getSettings();

		$postProcHandler = new PostProcHandler(
			$parserOutput,
			$this->getCache()
		);

		$postProcHandler->setOptions(
			$settings->get( 'smwgPostEditUpdate' ) +
			[ 'smwgEnabledQueryDependencyLinksStore' => $settings->get( 'smwgEnabledQueryDependencyLinksStore' ) ] +
			[ 'smwgEnabledFulltextSearch' => $settings->get( 'smwgEnabledFulltextSearch' ) ]
		);

		return $postProcHandler;
	}

}
