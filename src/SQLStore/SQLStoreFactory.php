<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\Utils\CircularReferenceGuard;
use SMW\SQLStore\Lookup\CachedListLookup;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\SQLStore\Lookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\SQLStore\Lookup\LegacySpecialLookup;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\SQLStore\Lookup\TableLookup;
use SMWRequestOptions as RequestOptions;
use SMW\Options;
use SMWSQLStore3;
use SMW\SQLStore\TableBuilder\TableBuilder;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\NullMessageReporter;
use Onoi\Cache\Cache;
use SMWSql3SmwIds as EntityIdManager;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\DataItemHandlerDispatcher;
use SMW\SQLStore\EntityStore\CachingEntityLookup;
use SMW\SQLStore\EntityStore\NativeEntityLookup;
use SMW\SQLStore\EntityStore\TraversalPropertyLookup;
use SMW\SQLStore\EntityStore\SemanticDataLookup;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\ProcessLruCache;
use SMW\SQLStore\EntityStore\IdEntityFinder;
use SMW\SQLStore\EntityStore\SubobjectListFinder;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\ChangePropListener;
use SMW\SQLStore\PropertyTableRowDiffer;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\Services\ServicesManager;

/**
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class SQLStoreFactory {

	/**
	 * @var SMWSQLStore3
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @var QueryEngineFactory
	 */
	private $queryEngineFactory;

	/**
	 * @since 2.2
	 *
	 * @param SMWSQLStore3 $store
	 * @param MessageReporter|null $messageReporter
	 */
	public function __construct( SMWSQLStore3 $store, MessageReporter $messageReporter = null ) {
		$this->store = $store;
		$this->messageReporter = $messageReporter;

		if ( $this->messageReporter === null ) {
			$this->messageReporter = new NullMessageReporter();
		}

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->queryEngineFactory = new QueryEngineFactory( $store );
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newMasterQueryEngine() {
		return $this->queryEngineFactory->newQueryEngine();
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newSlaveQueryEngine() {
		return $this->newMasterQueryEngine();
	}

	/**
	 * @since 2.5
	 *
	 * @return EntityIdManager
	 */
	public function newEntityTable() {
		return new EntityIdManager( $this->store, $this );
	}

	/**
	 * @since 2.2
	 *
	 * @return ConceptCache
	 */
	public function newMasterConceptCache() {

		$conceptCache = new ConceptCache(
			$this->store,
			$this->queryEngineFactory->newConceptQuerySegmentBuilder()
		);

		$conceptCache->setUpperLimit(
			$GLOBALS['smwgQMaxLimit']
		);

		return $conceptCache;
	}

	/**
	 * @since 2.2
	 *
	 * @return ConceptCache
	 */
	public function newSlaveConceptCache() {
		return $this->newMasterConceptCache();
	}

	/**
	 * @since 2.2
	 *
	 * @return ListLookup
	 */
	public function newUsageStatisticsCachedListLookup() {

		$settings = $this->applicationFactory->getSettings();

		$usageStatisticsListLookup = new UsageStatisticsListLookup(
			$this->store,
			$this->newPropertyStatisticsStore()
		);

		$cachedListLookup = $this->newCachedListLookup(
			$usageStatisticsListLookup
		);

		$cachedListLookup->setOption(
			UsageStatisticsListLookup::OPT_USE_CACHE,
			(bool)$settings->safeGet( 'special.statistics' )
		);

		$cachedListLookup->setOption(
			UsageStatisticsListLookup::OPT_CACHE_TTL,
			$settings->safeGet( 'special.statistics' )
		);

		return $cachedListLookup;
	}

	/**
	 * @since 2.2
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function newPropertyUsageCachedListLookup( RequestOptions $requestOptions = null ) {

		$settings = $this->applicationFactory->getSettings();

		$propertyUsageListLookup = new PropertyUsageListLookup(
			$this->store,
			$this->newPropertyStatisticsStore(),
			$requestOptions
		);

		$cachedListLookup = $this->newCachedListLookup(
			$propertyUsageListLookup
		);

		$cachedListLookup->setOption(
			PropertyUsageListLookup::OPT_USE_CACHE,
			(bool)$settings->safeGet( 'special.properties' )
		);

		$cachedListLookup->setOption(
			PropertyUsageListLookup::OPT_CACHE_TTL,
			$settings->safeGet( 'special.properties' )
		);

		return $cachedListLookup;
	}

	/**
	 * @since 2.2
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function newUnusedPropertyCachedListLookup( RequestOptions $requestOptions = null ) {

		$settings = $this->applicationFactory->getSettings();

		$unusedPropertyListLookup = new UnusedPropertyListLookup(
			$this->store,
			$this->newPropertyStatisticsStore(),
			$requestOptions
		);

		$cachedListLookup = $this->newCachedListLookup(
			$unusedPropertyListLookup
		);

		$cachedListLookup->setOption(
			UnusedPropertyListLookup::OPT_USE_CACHE,
			(bool)$settings->safeGet( 'special.unusedproperties' )
		);

		$cachedListLookup->setOption(
			UnusedPropertyListLookup::OPT_CACHE_TTL,
			$settings->safeGet( 'special.unusedproperties' )
		);

		return $cachedListLookup;
	}

	/**
	 * @since 2.2
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return CachedListLookup
	 */
	public function newUndeclaredPropertyCachedListLookup( RequestOptions $requestOptions = null ) {

		$settings = $this->applicationFactory->getSettings();

		$undeclaredPropertyListLookup = new UndeclaredPropertyListLookup(
			$this->store,
			$settings->get( 'smwgPDefaultType' ),
			$requestOptions
		);

		$cachedListLookup = $this->newCachedListLookup(
			$undeclaredPropertyListLookup
		);

		$cachedListLookup->setOption(
			UndeclaredPropertyListLookup::OPT_USE_CACHE,
			(bool)$settings->safeGet( 'special.wantedproperties' )
		);

		$cachedListLookup->setOption(
			UndeclaredPropertyListLookup::OPT_CACHE_TTL,
			$settings->safeGet( 'special.wantedproperties' )
		);

		return $cachedListLookup;
	}

	/**
	 * @since 2.2
	 *
	 * @param ListLookup $listLookup
	 *
	 * @return ListLookup
	 */
	public function newCachedListLookup( ListLookup $listLookup ) {

		$cachedListLookup = new CachedListLookup(
			$listLookup,
			$this->applicationFactory->getCache()
		);

		return $cachedListLookup;
	}

	/**
	 * @since 2.3
	 *
	 * @return EntityRebuildDispatcher
	 */
	public function newEntityRebuildDispatcher() {
		return new EntityRebuildDispatcher( $this->store );
	}

	/**
	 * @since 2.5
	 *
	 * @return EntityLookup
	 */
	public function newEntityLookup() {

		$settings = $this->applicationFactory->getSettings();
		$nativeEntityLookup = new NativeEntityLookup( $this->store );

		if ( $settings->get( 'smwgEntityLookupCacheType' ) === CACHE_NONE ) {
			return $nativeEntityLookup;
		}

		$circularReferenceGuard = new CircularReferenceGuard( 'store:entitylookup' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$cacheFactory = $this->applicationFactory->newCacheFactory();

		$blobStore = $cacheFactory->newBlobStore(
			'smw:store:entitylookup:',
			$settings->get( 'smwgEntityLookupCacheType' ),
			$settings->get( 'smwgEntityLookupCacheLifetime' )
		);

		$cachingEntityLookup = new CachingEntityLookup(
			$nativeEntityLookup,
			new RedirectTargetLookup( $this->store, $circularReferenceGuard ),
			$blobStore
		);

		$cachingEntityLookup->setLookupFeatures(
			$settings->get( 'smwgEntityLookupFeatures' )
		);

		return $cachingEntityLookup;
	}

	/**
	 * @since 2.3
	 *
	 * @return PropertyTableInfoFetcher
	 */
	public function newPropertyTableInfoFetcher() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$propertyTableInfoFetcher = new PropertyTableInfoFetcher(
			new PropertyTypeFinder( $this->store->getConnection( 'mw.db' ) )
		);

		$propertyTableInfoFetcher->setCustomFixedPropertyList(
			$settings->get( 'smwgFixedProperties' )
		);

		$propertyTableInfoFetcher->setCustomSpecialPropertyList(
			$settings->get( 'smwgPageSpecialProperties' )
		);

		return $propertyTableInfoFetcher;
	}

	/**
	 * @since 2.4
	 *
	 * @return PropertyTableIdReferenceFinder
	 */
	public function newPropertyTableIdReferenceFinder() {

		$propertyTableIdReferenceFinder = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$propertyTableIdReferenceFinder->isCapitalLinks(
			$GLOBALS['wgCapitalLinks']
		);

		return $propertyTableIdReferenceFinder;
	}

	/**
	 * @since 2.5
	 *
	 * @return Installer
	 */
	public function newInstaller() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$tableBuilder = TableBuilder::factory(
			$this->store->getConnection( DB_MASTER )
		);

		$tableBuilder->setMessageReporter(
			$this->messageReporter
		);

		$tableIntegrityExaminer = new TableIntegrityExaminer(
			$this->store
		);

		$tableSchemaManager = new TableSchemaManager(
			$this->store
		);

		$tableSchemaManager->setFieldTypeFeatures(
			$settings->get( 'smwgFieldTypeFeatures' )
		);

		$installer = new Installer(
			$tableSchemaManager,
			$tableBuilder,
			$tableIntegrityExaminer
		);

		$installer->setMessageReporter(
			$this->messageReporter
		);

		$installer->setOptions(
			$this->store->getOptions()->filter(
				[
					Installer::OPT_TABLE_OPTIMIZE,
					Installer::OPT_IMPORT,
					Installer::OPT_SCHEMA_UPDATE,
					Installer::OPT_SUPPLEMENT_JOBS
				]
			)
		);

		return $installer;
	}

	/**
	 * @since 2.5
	 *
	 * @return DataItemHandlerDispatcher
	 */
	public function newDataItemHandlerDispatcher() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$dataItemHandlerDispatcher = new DataItemHandlerDispatcher(
			$this->store
		);

		$dataItemHandlerDispatcher->setFieldTypeFeatures(
			$settings->get( 'smwgFieldTypeFeatures' )
		);

		return $dataItemHandlerDispatcher;
	}

	/**
	 * @since 2.5
	 *
	 * @return LoggerInterface
	 */
	public function getLogger() {
		return ApplicationFactory::getInstance()->getMediaWikiLogger();
	}

	/**
	 * @since 3.0
	 *
	 * @return TraversalPropertyLookup
	 */
	public function newTraversalPropertyLookup() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$options = new Options(
			array(
				'smwgExperimentalFeatures' => $settings->get( 'smwgExperimentalFeatures' )
			)
		);

		return new TraversalPropertyLookup( $this->store, $options );
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertyStatisticsStore
	 */
	public function newPropertyStatisticsStore() {

		$propertyStatisticsStore = new PropertyStatisticsStore(
			$this->store->getConnection( 'mw.db' )
		);

		$propertyStatisticsStore->setLogger(
			$this->getLogger()
		);

		$propertyStatisticsStore->isCommandLineMode(
			$GLOBALS['wgCommandLineMode']
		);

		return $propertyStatisticsStore;
	}

	/**
	 * @since 3.0
	 *
	 * @return IdCacheManager
	 */
	public function newIdCacheManager( $id, array $config ) {

		$inMemoryPoolCache = ApplicationFactory::getInstance()->getInMemoryPoolCache();
		$caches = [];

		foreach ( $config as $key => $cacheSize ) {
			$inMemoryPoolCache->resetPoolCacheById(
				"$id.$key"
			);

			$caches[$key] = $inMemoryPoolCache->getPoolCacheById(
				"$id.$key",
				$cacheSize
			);
		}

		return new IdCacheManager( $caches );
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertyTableRowDiffer
	 */
	public function newPropertyTableRowDiffer() {

		$propertyTableRowMapper = new PropertyTableRowMapper(
			$this->store
		);

		$propertyTableRowDiffer = new PropertyTableRowDiffer(
			$this->store,
			$propertyTableRowMapper
		);

		return $propertyTableRowDiffer;
	}

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 *
	 * @return IdEntityFinder
	 */
	public function newIdEntityFinder( Cache $cache ) {

		$idMatchFinder = new IdEntityFinder(
			$this->store,
			$this->applicationFactory->getIteratorFactory(),
			$cache
		);

		return $idMatchFinder;
	}

	/**
	 * @since 3.0
	 *
	 * @return HierarchyLookup
	 */
	public function newHierarchyLookup() {
		return $this->applicationFactory->newHierarchyLookup();
	}

	/**
	 * @since 3.0
	 *
	 * @return SubobjectListFinder
	 */
	public function newSubobjectListFinder() {

		$subobjectListFinder = new SubobjectListFinder(
			$this->store,
			$this->applicationFactory->getIteratorFactory()
		);

		return $subobjectListFinder;
	}

	/**
	 * @since 3.0
	 *
	 * @return SemanticDataLookup
	 */
	public function newSemanticDataLookup() {

		$semanticDataLookup = new SemanticDataLookup(
			$this->store
		);

		$semanticDataLookup->setLogger(
			$this->getLogger()
		);

		$cachingSemanticDataLookup = new CachingSemanticDataLookup(
			$semanticDataLookup,
			ApplicationFactory::getInstance()->getCache()
		);

		return $cachingSemanticDataLookup;
	}

	/**
	 * @since 3.0
	 *
	 * @return RedirectStore
	 */
	public function newRedirectStore() {

		$redirectStore = new RedirectStore(
			$this->store
		);

		return $redirectStore;
	}

	/**
	 * @since 3.0
	 *
	 * @return ChangePropListener
	 */
	public function newChangePropListener() {
		return new ChangePropListener();
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return ChangeOp
	 */
	public function newChangeOp( DIWikiPage $subject ) {

		$settings = ApplicationFactory::getInstance()->getSettings();
		$changeOp = new ChangeOp( $subject );

		$changeOp->setTextItemsFlag(
			$settings->get( 'smwgEnabledFulltextSearch' )
		);

		return $changeOp;
	}

	/**
	 * @since 3.0
	 *
	 * @return ServicesManager
	 */
	public function newServicesManager() {

		$servicesManager = new ServicesManager();

		$servicesManager->registerCallback(
			'special.lookup',
			function() {
				return new LegacySpecialLookup(
					$this->store,
					[
						'special.properties' => function( $requestOptions = null ) {
							return $this->newPropertyUsageCachedListLookup( $requestOptions );
						},
						'special.unused.properties' => function( $requestOptions = null ) {
							return $this->newUnusedPropertyCachedListLookup( $requestOptions );
						},
						'special.wanted.properties' => function( $requestOptions = null ) {
							return $this->newUndeclaredPropertyCachedListLookup( $requestOptions );
						},
						'statistics' => function( $requestOptions = null ) {
							return $this->newUsageStatisticsCachedListLookup();
						}
					]
				);
			}
		);

		$servicesManager->registerCallback(
			'table.lookup',
			function() {
				return new TableLookup(
					$this->store->getConnection( 'mw.db' ),
					$this->applicationFactory->getIteratorFactory()
				);
			}
		);

		return $servicesManager;
	}

}
