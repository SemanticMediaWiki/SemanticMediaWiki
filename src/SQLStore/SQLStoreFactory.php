<?php

namespace SMW\SQLStore;

use Onoi\Cache\Cache;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\NullMessageReporter;
use SMW\ApplicationFactory;
use SMW\ChangePropListener;
use SMW\DIWikiPage;
use SMW\Options;
use SMW\ProcessLruCache;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\EntityStore\CachingEntityLookup;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\DataItemHandlerDispatcher;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\EntityStore\IdEntityFinder;
use SMW\SQLStore\EntityStore\IdChanger;
use SMW\SQLStore\EntityStore\UniquenessLookup;
use SMW\SQLStore\EntityStore\NativeEntityLookup;
use SMW\SQLStore\EntityStore\SemanticDataLookup;
use SMW\SQLStore\EntityStore\SubobjectListFinder;
use SMW\SQLStore\EntityStore\TraversalPropertyLookup;
use SMW\SQLStore\EntityStore\PropertySubjectsLookup;
use SMW\SQLStore\EntityStore\PropertiesLookup;
use SMW\SQLStore\Lookup\CachedListLookup;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\SQLStore\Lookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\Utils\CircularReferenceGuard;
use SMWRequestOptions as RequestOptions;
use SMWSql3SmwIds as EntityIdManager;
use SMW\Services\ServicesContainer;
use SMW\RequestData;
use SMWSQLStore3;

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

		return $this->newCachedListLookup(
			$usageStatisticsListLookup,
			$settings->safeGet( 'special.statistics' ),
			$settings->safeGet( 'special.statistics' )
		);
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

		return $this->newCachedListLookup(
			$propertyUsageListLookup,
			$settings->safeGet( 'special.properties' ),
			$settings->safeGet( 'special.properties' )
		);
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

		return $this->newCachedListLookup(
			$unusedPropertyListLookup,
			$settings->safeGet( 'special.unusedproperties' ),
			$settings->safeGet( 'special.unusedproperties' )
		);
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

		return $this->newCachedListLookup(
			$undeclaredPropertyListLookup,
			$settings->safeGet( 'special.wantedproperties' ),
			$settings->safeGet( 'special.wantedproperties' )
		);
	}

	/**
	 * @since 2.2
	 *
	 * @param ListLookup $listLookup
	 * @param boolean $useCache
	 * @param integer $cacheExpiry
	 *
	 * @return ListLookup
	 */
	public function newCachedListLookup( ListLookup $listLookup, $useCache, $cacheExpiry ) {

		$cacheFactory = $this->applicationFactory->newCacheFactory();

		if ( is_int( $useCache ) ) {
			$useCache = true;
		}

		$cacheOptions = $cacheFactory->newCacheOptions( array(
			'useCache' => $useCache,
			'ttl'      => $cacheExpiry
		) );

		$cachedListLookup = new CachedListLookup(
			$listLookup,
			$cacheFactory->newMediaWikiCompositeCache( $cacheFactory->getMainCacheType() ),
			$cacheOptions
		);

		$cachedListLookup->setCachePrefix( $cacheFactory->getCachePrefix() );

		return $cachedListLookup;
	}

	/**
	 * @since 2.4
	 *
	 * @return DeferredCallableUpdate
	 */
	public function newDeferredCallableCachedListLookupUpdate() {

		$deferredTransactionalUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate( function() {
			$this->newPropertyUsageCachedListLookup()->deleteCache();
			$this->newUnusedPropertyCachedListLookup()->deleteCache();
			$this->newUndeclaredPropertyCachedListLookup()->deleteCache();
			$this->newUsageStatisticsCachedListLookup()->deleteCache();
		} );

		return $deferredTransactionalUpdate;
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

		$tableSchemaManager->setFeatureFlags(
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
		return new TraversalPropertyLookup( $this->store );
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertySubjectsLookup
	 */
	public function newPropertySubjectsLookup() {
		return new PropertySubjectsLookup( $this->store );
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertiesLookup
	 */
	public function newPropertiesLookup() {
		return new PropertiesLookup( $this->store );
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
	 * @return IdChanger
	 */
	public function newIdChanger() {

		$idChanger = new IdChanger(
			$this->store
		);

		return $idChanger;
	}

	/**
	 * @since 3.0
	 *
	 * @return UniquenessLookup
	 */
	public function newUniquenessLookup() {

		$uniquenessLookup = new UniquenessLookup(
			$this->store,
			$this->applicationFactory->getIteratorFactory()
		);

		return $uniquenessLookup;
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
	 * @return TableFieldUpdater
	 */
	public function newTableFieldUpdater() {

		$tableFieldUpdater = new TableFieldUpdater(
			$this->store
		);

		return $tableFieldUpdater;
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
	 * @return ProximityPropertyValueLookup
	 */
	public function newProximityPropertyValueLookup() {
		return new ProximityPropertyValueLookup( $this->store );
	}

	/**
	 * @since 3.0
	 *
	 * @return EntityValueUniquenessConstraintChecker
	 */
	public function newEntityValueUniquenessConstraintChecker() {
		return new EntityValueUniquenessConstraintChecker(
			$this->store,
			ApplicationFactory::getInstance()->getIteratorFactory()
		);
	}

	/**
	 * @since 3.0
	 *
	 * @return ServicesContainer
	 */
	public function newServicesContainer() {

		$servicesContainer = new ServicesContainer(
			[
				'ProximityPropertyValueLookup' => [
					'_service' => [ $this, 'newProximityPropertyValueLookup' ],
					'_type'    => ProximityPropertyValueLookup::class
				],
				'EntityValueUniquenessConstraintChecker' => [
					'_service' => [ $this, 'newEntityValueUniquenessConstraintChecker' ],
					'_type'    => EntityValueUniquenessConstraintChecker::class
				]
			]
		);

		return $servicesContainer;
	}

}
