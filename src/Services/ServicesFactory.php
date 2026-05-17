<?php

namespace SMW\Services;

use JobQueueGroup;
use MediaWiki\Config\Config;
use MediaWiki\Language\Language;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\MagicWordFactory;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserCache;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use Onoi\BlobStore\BlobStore;
use Onoi\Cache\Cache;
use Onoi\Cache\CacheFactory as OnoiCacheFactory;
use Onoi\Cache\FixedInMemoryLruCache;
use Onoi\EventDispatcher\EventDispatcher;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SearchEngineConfig;
use SMW\CacheFactory;
use SMW\Connection\ConnectionManager;
use SMW\ConstraintFactory;
use SMW\DataItemFactory;
use SMW\DataModel\SemanticData;
use SMW\DataUpdater;
use SMW\DataValueFactory;
use SMW\DependencyValidator;
use SMW\DisplayTitleFinder;
use SMW\Elastic\ElasticFactory;
use SMW\EntityCache;
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
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\MediaWiki\FileRepoFinder;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\IndicatorRegistry;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageUpdater;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\MediaWiki\PermissionManager;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\MediaWiki\RevisionGuard;
use SMW\MediaWiki\TitleFactory;
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
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Store;
use SMW\StoreFactory;
use SMW\Utils\Logger;
use SMW\Utils\Stats;
use SMW\Utils\TempFile;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Services\ServiceContainer;
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
	 * Private container holding the Bucket-A (no-argument, stateless) services
	 * defined in `ServiceWiring.php`.
	 */
	private readonly ServiceContainer $container;

	/**
	 * Test-override map keyed by service name. When a service name is present
	 * here the registered object is returned by the matching accessor and by
	 * the `singleton()`/`create()` shims regardless of bucket. This is how
	 * `TestEnvironment::registerObject()` injects mocked services.
	 *
	 * @var array<string, mixed>
	 */
	private array $testOverrides = [];

	/**
	 * @since 2.0
	 */
	public function __construct( private $servicesFileDir = '' ) {
		$this->container = new ServiceContainer();
		$this->container->loadWiringFiles( [ __DIR__ . '/ServiceWiring.php' ] );
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
	 * @since 2.0
	 */
	public static function clear(): void {
		self::$instance = null;
	}

	/**
	 * Registers a service object so that tests can inject mocked collaborators.
	 *
	 * The injected object is recorded in a test-override map consulted by the
	 * typed accessors, the factory methods and the `singleton()`/`create()`
	 * shims. For Bucket-A services it is additionally redefined on the private
	 * `ServiceContainer` so that sibling wiring callbacks resolve the override
	 * as well.
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

		if ( $this->container->hasService( $objectName ) ) {
			// disableService() drops any already-instantiated instance so the
			// subsequent redefineService() cannot hit CannotReplaceActiveServiceException.
			$this->container->disableService( $objectName );
			$this->container->redefineService(
				$objectName,
				static function () use ( $objectSignature ) {
					return $objectSignature;
				}
			);
		}
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
			$this->registerObject( $name, $instantiator( $this->container ) );
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
	 */
	public function singleton( $serviceName, ...$args ) {
		if ( array_key_exists( $serviceName, $this->testOverrides ) ) {
			return $this->testOverrides[$serviceName];
		}

		if ( $this->container->hasService( $serviceName ) ) {
			return $this->container->getService( $serviceName );
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
	 */
	public function create( $serviceName, ...$args ) {
		if ( array_key_exists( $serviceName, $this->testOverrides ) ) {
			return $this->testOverrides[$serviceName];
		}

		if ( $this->container->hasService( $serviceName ) ) {
			return $this->container->getService( $serviceName );
		}

		return $this->routeToFactoryMethod( $serviceName, $args );
	}

	/**
	 * Routes a legacy `singleton()`/`create()` call for a Bucket-B/C service
	 * to its dedicated factory method.
	 *
	 * @param string $serviceName
	 * @param array $args
	 */
	private function routeToFactoryMethod( string $serviceName, array $args ) {
		$handlers = [
			'Store' => fn () => $this->getStore( ...$args ),
			'IndicatorRegistry' => fn () => $this->newIndicatorRegistry( ...$args ),
			'Cache' => fn () => $this->getCache( ...$args ),
			'CacheFactory' => fn () => $this->getCacheFactory( ...$args ),
			'NamespaceExaminer' => fn () => $this->newNamespaceExaminer(),
			'ParserData' => fn () => $this->newParserData( ...$args ),
			'LinksProcessor' => fn () => $this->newLinksProcessor(),
			'MessageFormatter' => fn () => $this->newMessageFormatter( ...$args ),
			'PageCreator' => fn () => $this->newPageCreator(),
			'PageUpdater' => fn () => $this->newPageUpdater( ...$args ),
			'TitleFactory' => fn () => $this->newTitleFactory(),
			'ContentParser' => fn () => $this->newContentParser( ...$args ),
			'DeferredCallableUpdate' => fn () => $this->newDeferredCallableUpdate( ...$args ),
			'DeferredTransactionalCallableUpdate' => fn () => $this->newDeferredTransactionalCallableUpdate( ...$args ),
			'TempFile' => fn () => $this->newTempFile(),
			'PostProcHandler' => fn () => $this->newPostProcHandler( ...$args ),
			'DataValueServiceFactory' => fn () => $this->getDataValueServiceFactory(),
			'ImporterServiceFactory' => fn () => $this->getImporterServiceFactory(),
			'BlobStore' => fn () => $this->newBlobStore( ...$args ),
			'ResultCache' => fn () => $this->getResultCache( ...$args ),
			'Stats' => fn () => $this->newStats( ...$args ),
			'EditProtectionUpdater' => fn () => $this->newEditProtectionUpdater( ...$args ),
			'PropertyRestrictionExaminer' => fn () => $this->newPropertyRestrictionExaminer(),
			'HierarchyLookup' => fn () => $this->newHierarchyLookup( ...$args ),
			'DisplayTitleFinder' => fn () => $this->newDisplayTitleFinder( ...$args ),
			'MagicWordsFinder' => fn () => $this->newMagicWordsFinder( ...$args ),
			'DependencyValidator' => fn () => $this->newDependencyValidator( ...$args ),
			'PreferenceExaminer' => fn () => $this->newPreferenceExaminer( ...$args ),
			'Parser' => fn () => $this->newParser(),
			'RevisionLookup' => fn () => $this->newRevisionLookup(),
			'DefaultSearchEngineTypeForDB' => fn () => $this->getDefaultSearchEngineTypeForDB( ...$args ),
			'MediaWikiLogger' => fn () => $this->getMediaWikiLogger( ...$args ),
			'WikiPage' => fn () => $this->newWikiPage( ...$args ),
			'FixedInMemoryLruCache' => fn () => $this->newFixedInMemoryLruCache( ...$args ),
			'JobFactory' => fn () => $this->newJobFactory(),
			'JobQueueGroup' => fn () => $this->getJobQueueGroup(),
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
	 * @since 3.2
	 *
	 * @param User|null $user
	 *
	 * @return PreferenceExaminer
	 */
	public function newPreferenceExaminer( ?User $user = null ): PreferenceExaminer {
		if ( array_key_exists( 'PreferenceExaminer', $this->testOverrides ) ) {
			return $this->testOverrides['PreferenceExaminer'];
		}

		return new PreferenceExaminer( $user, $this->getUserOptionsLookup() );
	}

	/**
	 * @since 2.0
	 */
	public function newSerializerFactory(): SerializerFactory {
		return new SerializerFactory();
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
	 * @since 2.1
	 */
	public function newParserFunctionFactory(): ParserFunctionFactory {
		return new ParserFunctionFactory();
	}

	/**
	 * @since 2.2
	 */
	public function newMaintenanceFactory(): MaintenanceFactory {
		return new MaintenanceFactory();
	}

	/**
	 * @since 2.2
	 */
	public function newCacheFactory(): CacheFactory {
		return new CacheFactory( $this->getSettings()->get( 'smwgMainCacheType' ) );
	}

	/**
	 * @since 2.2
	 */
	public function getCacheFactory(): CacheFactory {
		return $this->newCacheFactory();
	}

	/**
	 * @since 2.5
	 *
	 * @param string|null $source
	 *
	 * @return QuerySourceFactory
	 */
	public function getQuerySourceFactory( $source = null ): QuerySourceFactory {
		return $this->container->getService( 'QuerySourceFactory' );
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

		$settings = $this->getSettings();

		if ( $store === null || $store === '' ) {
			$store = $settings->get( 'smwgDefaultStore' );
		}

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
			$this->getMediaWikiLogger()
		);

		return $instance;
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
		return $this->getService( 'Settings' );
	}

	/**
	 * @since 3.0
	 *
	 * @return ConnectionManager
	 */
	public function getConnectionManager(): ConnectionManager {
		return $this->getService( 'ConnectionManager' );
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
	 * @since 3.2
	 *
	 * @return HookDispatcher
	 */
	public function getHookDispatcher(): HookDispatcher {
		return $this->getService( 'HookDispatcher' );
	}

	/**
	 * @since 2.0
	 */
	public function newTitleFactory(): TitleFactory {
		if ( array_key_exists( 'TitleFactory', $this->testOverrides ) ) {
			return $this->testOverrides['TitleFactory'];
		}

		return new TitleFactory();
	}

	/**
	 * @since 2.0
	 *
	 * @return PageCreator
	 */
	public function newPageCreator() {
		if ( array_key_exists( 'PageCreator', $this->testOverrides ) ) {
			return $this->testOverrides['PageCreator'];
		}

		return new PageCreator();
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
			$this->getMediaWikiLogger()
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
		return $this->getService( 'IteratorFactory' );
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

		return ( new CacheFactory() )->newMediaWikiCompositeCache( $cacheType );
	}

	/**
	 * @since 3.1
	 */
	public function getEntityCache(): EntityCache {
		return $this->getService( 'EntityCache' );
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

		$inTextAnnotationParser->setHookDispatcher(
			$this->getHookDispatcher()
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
			$this->getMediaWikiLogger()
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
			$changePropagationNotifier
		);

		$dataUpdater->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$dataUpdater->setLogger(
			$this->getMediaWikiLogger()
		);

		$dataUpdater->setEventDispatcher(
			$this->getEventDispatcher()
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
		return new MwCollaboratorFactory( $this );
	}

	/**
	 * @since 2.1
	 */
	public function getNamespaceExaminer(): NamespaceExaminer {
		return $this->newNamespaceExaminer();
	}

	/**
	 * @since 7.0.0
	 */
	public function newNamespaceExaminer(): NamespaceExaminer {
		if ( array_key_exists( 'NamespaceExaminer', $this->testOverrides ) ) {
			return $this->testOverrides['NamespaceExaminer'];
		}

		$settings = $this->getSettings();
		$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();

		$namespaceExaminer = new NamespaceExaminer(
			$settings->get( 'smwgNamespacesWithSemanticLinks' )
		);

		$namespaceExaminer->setValidNamespaces(
			$namespaceInfo->getValidNamespaces()
		);

		return $namespaceExaminer;
	}

	/**
	 * @since 2.4
	 */
	public function getPropertySpecificationLookup(): SpecificationLookup {
		return $this->getService( 'PropertySpecificationLookup' );
	}

	/**
	 * @since 2.4
	 */
	public function newHierarchyLookup( $store = null, $cacheType = null ): HierarchyLookup {
		if ( array_key_exists( 'HierarchyLookup', $this->testOverrides ) ) {
			return $this->testOverrides['HierarchyLookup'];
		}

		$hierarchyLookup = new HierarchyLookup(
			$store ?? $this->getStore(),
			$this->getCache( $cacheType )
		);

		$hierarchyLookup->setLogger(
			$this->getMediaWikiLogger()
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
	public function newDisplayTitleFinder( $store = null ): DisplayTitleFinder {
		if ( array_key_exists( 'DisplayTitleFinder', $this->testOverrides ) ) {
			return $this->testOverrides['DisplayTitleFinder'];
		}

		$settings = $this->getSettings();

		$displayTitleFinder = new DisplayTitleFinder(
			$store ?? $this->getStore(),
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
	public function newMagicWordsFinder( ?ParserOutput $parserOutput = null ): MagicWordsFinder {
		if ( array_key_exists( 'MagicWordsFinder', $this->testOverrides ) ) {
			return $this->testOverrides['MagicWordsFinder'];
		}

		return new MagicWordsFinder(
			$parserOutput,
			$this->getMagicWordFactory()
		);
	}

	/**
	 * @since 7.0.0
	 */
	public function newDependencyValidator( $store = null ): DependencyValidator {
		if ( array_key_exists( 'DependencyValidator', $this->testOverrides ) ) {
			return $this->testOverrides['DependencyValidator'];
		}

		$queryDependencyLinksStoreFactory = $this->getQueryDependencyLinksStoreFactory();

		return new DependencyValidator(
			$this->newNamespaceExaminer(),
			$queryDependencyLinksStoreFactory->newDependencyLinksValidator(),
			$this->getEntityCache()
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
			$this->getMediaWikiLogger()
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
			$this->getMediaWikiLogger()
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

		$servicesContainer = DataValueServiceFactory::newServicesContainer(
			$this->getSettings()->get( 'smwgServicesFileDir' )
		);

		return new DataValueServiceFactory( $servicesContainer );
	}

	/**
	 * @since 7.0.0
	 */
	public function getImporterServiceFactory(): ImporterServiceFactory {
		if ( array_key_exists( 'ImporterServiceFactory', $this->testOverrides ) ) {
			return $this->testOverrides['ImporterServiceFactory'];
		}

		$servicesContainer = ImporterServiceFactory::newServicesContainer(
			$this->getSettings()->get( 'smwgServicesFileDir' )
		);

		return new ImporterServiceFactory( $servicesContainer );
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
		return $this->getService( 'PropertyLabelFinder' );
	}

	/**
	 * @since 2.4
	 */
	public function getMediaWikiNsContentReader(): MediaWikiNsContentReader {
		return $this->getService( 'MediaWikiNsContentReader' );
	}

	/**
	 * @since 2.4
	 */
	public function getInMemoryPoolCache(): InMemoryPoolCache {
		return $this->getService( 'InMemoryPoolCache' );
	}

	/**
	 * @since 2.5
	 *
	 * @return ILoadBalancer
	 */
	public function getLoadBalancer() {
		return $this->getDBLoadBalancer();
	}

	/**
	 * @since 7.0.0
	 *
	 * @return ILoadBalancer
	 */
	public function getDBLoadBalancer(): ILoadBalancer {
		return $this->getService( 'DBLoadBalancer' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getDBLoadBalancerFactory(): LBFactory {
		return $this->getService( 'DBLoadBalancerFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getMainConfig(): Config {
		return $this->getService( 'MainConfig' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getSearchEngineConfig(): SearchEngineConfig {
		return $this->getService( 'SearchEngineConfig' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getMagicWordFactory(): MagicWordFactory {
		return $this->getService( 'MagicWordFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getPermissionManager(): PermissionManager {
		return $this->getService( 'PermissionManager' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getFileRepoFinder(): FileRepoFinder {
		return $this->getService( 'FileRepoFinder' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getJobQueueGroup(): JobQueueGroup {
		return $this->getService( 'JobQueueGroup' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getContentLanguage(): Language {
		return $this->getService( 'ContentLanguage' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getParserCache(): ParserCache {
		return $this->getService( 'ParserCache' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getUserOptionsLookup(): UserOptionsLookup {
		return $this->getService( 'UserOptionsLookup' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getSetupFile(): SetupFile {
		return $this->getService( 'SetupFile' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getManualEntryLogger(): ManualEntryLogger {
		return $this->getService( 'ManualEntryLogger' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getRevisionGuard(): RevisionGuard {
		return $this->getService( 'RevisionGuard' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getPropertyAnnotatorFactory(): AnnotatorFactory {
		return $this->getService( 'PropertyAnnotatorFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getConnectionProvider(): ConnectionProvider {
		return $this->getService( 'ConnectionProvider' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getSchemaFactory(): SchemaFactory {
		return $this->getService( 'SchemaFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getConstraintFactory(): ConstraintFactory {
		return $this->getService( 'ConstraintFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getElasticFactory(): ElasticFactory {
		return $this->getService( 'ElasticFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getQueryCreator(): QueryCreator {
		return $this->getService( 'QueryCreator' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getParamListProcessor(): ParamListProcessor {
		return $this->getService( 'ParamListProcessor' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getFactboxFactory(): FactboxFactory {
		return $this->getService( 'FactboxFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getQueryDependencyLinksStoreFactory(): QueryDependencyLinksStoreFactory {
		return $this->getService( 'QueryDependencyLinksStoreFactory' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getProtectionValidator(): ProtectionValidator {
		return $this->getService( 'ProtectionValidator' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getTitlePermissions(): TitlePermissions {
		return $this->getService( 'TitlePermissions' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getInvalidateResultCacheEventListener(): InvalidateResultCacheEventListener {
		return $this->getService( 'InvalidateResultCacheEventListener' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getInvalidateEntityCacheEventListener(): InvalidateEntityCacheEventListener {
		return $this->getService( 'InvalidateEntityCacheEventListener' );
	}

	/**
	 * @since 7.0.0
	 */
	public function getInvalidatePropertySpecificationLookupCacheEventListener(): InvalidatePropertySpecificationLookupCacheEventListener {
		return $this->getService( 'InvalidatePropertySpecificationLookupCacheEventListener' );
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
			$this->getMediaWikiLogger()
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
			$this->getMediaWikiLogger()
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
		return $this->getService( 'DataItemFactory' );
	}

	/**
	 * @since 2.5
	 */
	public function getQueryFactory(): QueryFactory {
		return $this->getService( 'QueryFactory' );
	}

	/**
	 * @since 2.5
	 */
	public function getMediaWikiLogger( $channel = 'smw', $role = Logger::ROLE_DEVELOPER ): LoggerInterface {
		if ( array_key_exists( 'MediaWikiLogger', $this->testOverrides ) ) {
			return $this->testOverrides['MediaWikiLogger'];
		}

		return new Logger( LoggerFactory::getInstance( $channel ), $role );
	}

	/**
	 * @since 3.0
	 */
	public function getJobQueue(): JobQueue {
		return $this->getService( 'JobQueue' );
	}

	/**
	 * @since 4.1.1
	 */
	public function getFactboxText(): FactboxText {
		return $this->getService( 'FactboxText' );
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

	/**
	 * Resolves a Bucket-A service from the private container, honouring any
	 * test override registered through {@see registerObject}.
	 *
	 * @param string $name
	 */
	private function getService( string $name ) {
		if ( array_key_exists( $name, $this->testOverrides ) ) {
			return $this->testOverrides[$name];
		}

		return $this->container->getService( $name );
	}

}
