<?php

namespace SMW\SQLStore;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\NullMessageReporter;
use Psr\Log\LoggerInterface;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\Listener\ChangeListener\ChangeListeners\CallableChangeListener;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\Lookup\CachedListLookup;
use SMW\Lookup\ListLookup;
use SMW\MediaWiki\Collator;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\RequestOptions;
use SMW\Services\ServicesContainer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Site;
use SMW\SortLetter;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\EntityStore\AuxiliaryFields;
use SMW\SQLStore\EntityStore\CacheWarmer;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\DataItemHandlerFactory;
use SMW\SQLStore\EntityStore\DuplicateFinder;
use SMW\SQLStore\EntityStore\EntityIdFinder;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\EntityLookup;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\EntityStore\IdChanger;
use SMW\SQLStore\EntityStore\IdEntityFinder;
use SMW\SQLStore\EntityStore\PrefetchCache;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\SQLStore\EntityStore\PropertiesLookup;
use SMW\SQLStore\EntityStore\PropertySubjectsLookup;
use SMW\SQLStore\EntityStore\SemanticDataLookup;
use SMW\SQLStore\EntityStore\SequenceMapFinder;
use SMW\SQLStore\EntityStore\SubobjectListFinder;
use SMW\SQLStore\EntityStore\TraversalPropertyLookup;
use SMW\SQLStore\Installer\TableOptimizer;
use SMW\SQLStore\Installer\VersionExaminer;
use SMW\SQLStore\Lookup\ByGroupPropertyValuesLookup;
use SMW\SQLStore\Lookup\DisplayTitleLookup;
use SMW\SQLStore\Lookup\EntityUniquenessLookup;
use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\SQLStore\Lookup\MissingRedirectLookup;
use SMW\SQLStore\Lookup\MonolingualTextLookup;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\SQLStore\Lookup\SingleEntityQueryLookup;
use SMW\SQLStore\Lookup\TableStatisticsLookup;
use SMW\SQLStore\Lookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\SQLStore\PropertyTable\PropertyTableHashes;
use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\SQLStore\Rebuilder\EntityValidator;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\SQLStore\TableBuilder\TableBuildExaminer;
use SMW\SQLStore\TableBuilder\TableBuildExaminerFactory;
use SMW\SQLStore\TableBuilder\TableSchemaManager;

/**
 * @license GPL-2.0-or-later
 * @since   2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class SQLStoreFactory {

	private QueryEngineFactory $queryEngineFactory;

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly SQLStore $store,
		private ?MessageReporter $messageReporter = null,
	) {
		if ( $this->messageReporter === null ) {
			$this->messageReporter = new NullMessageReporter();
		}

		$this->queryEngineFactory = new QueryEngineFactory( $this->store );
	}

	/**
	 * @since 3.1
	 *
	 * @return SQLStoreUpdater
	 */
	public function newUpdater(): SQLStoreUpdater {
		return new SQLStoreUpdater( $this->store, $this );
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newMasterQueryEngine(): QueryEngine {
		return $this->queryEngineFactory->newQueryEngine();
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newSlaveQueryEngine(): QueryEngine {
		return $this->newMasterQueryEngine();
	}

	/**
	 * @since 2.5
	 *
	 * @return EntityIdManager
	 */
	public function newEntityIdManager(): EntityIdManager {
		$settings = ApplicationFactory::getInstance()->getSettings();

		$entityIdManager = new EntityIdManager(
			$this->store,
			$this
		);

		$entityIdManager->setEqualitySupport(
			$settings->get( 'smwgQEqualitySupport' )
		);

		$callableChangeListener = $this->newCallableChangeListener(
			[
				'smwgQEqualitySupport' => [ $entityIdManager, 'applyChangesFromListener' ]
			]
		);

		$settings->registerChangeListener( $callableChangeListener );

		return $entityIdManager;
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertyTableUpdater
	 */
	public function newPropertyTableUpdater(): PropertyTableUpdater {
		return new PropertyTableUpdater( $this->store, $this->newPropertyStatisticsStore() );
	}

	/**
	 * @since 2.2
	 *
	 * @return ConceptCache
	 */
	public function newMasterConceptCache(): ConceptCache {
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
	public function newSlaveConceptCache(): ConceptCache {
		return $this->newMasterConceptCache();
	}

	/**
	 * @since 2.2
	 *
	 * @return CachedListLookup
	 */
	public function newUsageStatisticsCachedListLookup(): CachedListLookup {
		$settings = ApplicationFactory::getInstance()->getSettings();

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
	public function newPropertyUsageCachedListLookup( ?RequestOptions $requestOptions = null ): CachedListLookup {
		$settings = ApplicationFactory::getInstance()->getSettings();

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
	public function newUnusedPropertyCachedListLookup( ?RequestOptions $requestOptions = null ): CachedListLookup {
		$settings = ApplicationFactory::getInstance()->getSettings();

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
	public function newUndeclaredPropertyCachedListLookup( ?RequestOptions $requestOptions = null ): CachedListLookup {
		$settings = ApplicationFactory::getInstance()->getSettings();

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
	 * @param bool $useCache
	 * @param int $cacheExpiry
	 *
	 * @return CachedListLookup
	 */
	public function newCachedListLookup( ListLookup $listLookup, $useCache, $cacheExpiry ): CachedListLookup {
		$cacheFactory = ApplicationFactory::getInstance()->newCacheFactory();

		if ( is_int( $useCache ) ) {
			$useCache = true;
		}

		$cacheOptions = $cacheFactory->newCacheOptions( [
			'useCache' => $useCache,
			'ttl'      => $cacheExpiry
		] );

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
	 * @return TransactionalCallableUpdate
	 */
	public function newDeferredCallableCachedListLookupUpdate() {
		$deferredTransactionalUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate( function (): void {
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
	 * @return Rebuilder
	 */
	public function newRebuilder(): Rebuilder {
		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$entityValidator = new EntityValidator(
			$this->store,
			$applicationFactory->getNamespaceExaminer()
		);

		$entityValidator->setPropertyInvalidCharacterList(
			$settings->get( 'smwgPropertyInvalidCharacterList' )
		);

		$entityValidator->setPropertyRetiredList(
			$settings->get( 'smwgPropertyRetiredList' )
		);

		$entityValidator->setRevisionGuard(
			$applicationFactory->singleton( 'RevisionGuard' )
		);

		$rebuilder = new Rebuilder(
			$this->store,
			$applicationFactory->newTitleFactory(),
			$entityValidator,
			$this->newPropertyTableIdReferenceDisposer()
		);

		return $rebuilder;
	}

	/**
	 * @since 2.5
	 *
	 * @return EntityLookup
	 */
	public function newEntityLookup(): EntityLookup {
		return new EntityLookup( $this->store, $this );
	}

	/**
	 * @since 2.3
	 *
	 * @return PropertyTableInfoFetcher
	 */
	public function newPropertyTableInfoFetcher(): PropertyTableInfoFetcher {
		$settings = ApplicationFactory::getInstance()->getSettings();

		$propertyTableInfoFetcher = new PropertyTableInfoFetcher(
			$this->newPropertyTypeFinder()
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
	public function newPropertyTableIdReferenceFinder(): PropertyTableIdReferenceFinder {
		$propertyTableIdReferenceFinder = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$propertyTableIdReferenceFinder->isCapitalLinks(
			Site::isCapitalLinks()
		);

		return $propertyTableIdReferenceFinder;
	}

	/**
	 * @since 3.1
	 *
	 * @param IdCacheManager $idCacheManager
	 *
	 * @return PropertyTableHashes
	 */
	public function newPropertyTableHashes( IdCacheManager $idCacheManager ): PropertyTableHashes {
		$propertyTableHashes = new PropertyTableHashes(
			$this->store->getConnection( 'mw.db' ),
			$idCacheManager
		);

		return $propertyTableHashes;
	}

	/**
	 * @since 2.5
	 *
	 * @return Installer
	 */
	public function newInstaller(): Installer {
		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$connection = $this->store->getConnection( DB_PRIMARY );

		$tableBuilder = TableBuilder::factory(
			$connection
		);

		$tableBuilder->setMessageReporter(
			$this->messageReporter
		);

		$tableBuildExaminer = new TableBuildExaminer(
			$this->store,
			new TableBuildExaminerFactory()
		);

		$tableSchemaManager = new TableSchemaManager(
			$this->store
		);

		$tableSchemaManager->setOptions(
			[
				'smwgEnabledFulltextSearch' => $settings->get( 'smwgEnabledFulltextSearch' ),
				'smwgFulltextSearchTableOptions' => $settings->get( 'smwgFulltextSearchTableOptions' )
			]
		);

		$tableSchemaManager->setFeatureFlags(
			$settings->get( 'smwgFieldTypeFeatures' )
		);

		$setupFile = $applicationFactory->singleton( 'SetupFile' );

		$versionExaminer = new VersionExaminer(
			$connection
		);

		$versionExaminer->setSetupFile(
			$setupFile
		);

		$tableOptimizer = new TableOptimizer(
			$tableBuilder
		);

		$tableOptimizer->setSetupFile(
			$setupFile
		);

		$installer = new Installer(
			$tableSchemaManager,
			$tableBuilder,
			$tableBuildExaminer,
			$versionExaminer,
			$tableOptimizer
		);

		$installer->setHookDispatcher(
			$applicationFactory->getHookDispatcher()
		);

		$installer->setSetupFile(
			$setupFile
		);

		$installer->setMessageReporter(
			$this->messageReporter
		);

		return $installer;
	}

	/**
	 * @since 2.5
	 *
	 * @return DataItemHandlerFactory
	 */
	public function newDataItemHandlerFactory(): DataItemHandlerFactory {
		$settings = ApplicationFactory::getInstance()->getSettings();

		$dataItemHandlerFactory = new DataItemHandlerFactory(
			$this->store
		);

		$dataItemHandlerFactory->setFieldTypeFeatures(
			$settings->get( 'smwgFieldTypeFeatures' )
		);

		return $dataItemHandlerFactory;
	}

	/**
	 * @since 2.5
	 *
	 * @return LoggerInterface
	 */
	public function getLogger(): LoggerInterface {
		return ApplicationFactory::getInstance()->getMediaWikiLogger();
	}

	/**
	 * @since 3.0
	 *
	 * @return TraversalPropertyLookup
	 */
	public function newTraversalPropertyLookup(): TraversalPropertyLookup {
		return new TraversalPropertyLookup( $this->store );
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertySubjectsLookup
	 */
	public function newPropertySubjectsLookup(): PropertySubjectsLookup {
		return new PropertySubjectsLookup( $this->store );
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertiesLookup
	 */
	public function newPropertiesLookup(): PropertiesLookup {
		return new PropertiesLookup( $this->store );
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertyStatisticsStore
	 */
	public function newPropertyStatisticsStore(): PropertyStatisticsStore {
		$propertyStatisticsStore = new PropertyStatisticsStore(
			$this->store->getConnection( 'mw.db' )
		);

		$propertyStatisticsStore->setLogger(
			$this->getLogger()
		);

		$propertyStatisticsStore->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$propertyStatisticsStore->waitOnTransactionIdle();

		return $propertyStatisticsStore;
	}

	/**
	 * @since 3.0
	 *
	 * @return IdCacheManager
	 */
	public function newIdCacheManager( $id, array $config ): IdCacheManager {
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
	public function newPropertyTableRowDiffer(): PropertyTableRowDiffer {
		$settings = ApplicationFactory::getInstance()->getSettings();

		$propertyTableRowMapper = new PropertyTableRowMapper(
			$this->store
		);

		$propertyTableRowDiffer = new PropertyTableRowDiffer(
			$this->store,
			$propertyTableRowMapper
		);

		$propertyTableRowDiffer->checkRemnantEntities(
			$settings->is( 'smwgCheckForRemnantEntities', true )
		);

		return $propertyTableRowDiffer;
	}

	/**
	 * @since 3.0
	 *
	 * @param IdCacheManager $idCacheManager
	 *
	 * @return IdEntityFinder
	 */
	public function newIdEntityFinder( IdCacheManager $idCacheManager ): IdEntityFinder {
		$idMatchFinder = new IdEntityFinder(
			$this->store,
			$this->getIteratorFactory(),
			$idCacheManager
		);

		return $idMatchFinder;
	}

	/**
	 * @since 3.1
	 *
	 * @param IdCacheManager $idCacheManager
	 * @param PropertyTableHashes|null $propertyTableHashes
	 *
	 * @return EntityIdFinder
	 */
	public function newEntityIdFinder( IdCacheManager $idCacheManager, ?PropertyTableHashes $propertyTableHashes = null ): EntityIdFinder {
		if ( $propertyTableHashes === null ) {
			$propertyTableHashes = $this->newPropertyTableHashes( $idCacheManager );
		}

		$entityIdFinder = new EntityIdFinder(
			$this->store->getConnection( 'mw.db' ),
			$propertyTableHashes,
			$idCacheManager
		);

		return $entityIdFinder;
	}

	/**
	 * @since 3.1
	 *
	 * @param IdCacheManager $idCacheManager
	 *
	 * @return SequenceMapFinder
	 */
	public function newSequenceMapFinder( IdCacheManager $idCacheManager ): SequenceMapFinder {
		$sequenceMapFinder = new SequenceMapFinder(
			$this->store->getConnection( 'mw.db' ),
			$idCacheManager
		);

		return $sequenceMapFinder;
	}

	/**
	 * @since 3.2
	 *
	 * @param IdCacheManager $idCacheManager
	 *
	 * @return AuxiliaryFields
	 */
	public function newAuxiliaryFields( IdCacheManager $idCacheManager ): AuxiliaryFields {
		$auxiliaryFields = new AuxiliaryFields(
			$this->store->getConnection( 'mw.db' ),
			$idCacheManager
		);

		return $auxiliaryFields;
	}

	/**
	 * @since 3.1
	 *
	 * @param IdCacheManager $idCacheManager
	 *
	 * @return CacheWarmer
	 */
	public function newCacheWarmer( IdCacheManager $idCacheManager ): CacheWarmer {
		$applicationFactory = ApplicationFactory::getInstance();

		$cacheWarmer = new CacheWarmer(
			$this->store,
			$idCacheManager
		);

		$cacheWarmer->setDisplayTitleFinder(
			$applicationFactory->singleton( 'DisplayTitleFinder', $this->store )
		);

		$cacheWarmer->setThresholdLimit( 1 );

		return $cacheWarmer;
	}

	/**
	 * @since 3.2
	 *
	 * @param IdCacheManager $idCacheManager
	 *
	 * @return redirectTargetLookup
	 */
	public function newRedirectTargetLookup( IdCacheManager $idCacheManager ): RedirectTargetLookup {
		$redirectTargetLookup = new RedirectTargetLookup(
			$this->store,
			$idCacheManager
		);

		return $redirectTargetLookup;
	}

	/**
	 * @since 3.0
	 *
	 * @return IdChanger
	 */
	public function newIdChanger(): IdChanger {
		$idChanger = new IdChanger(
			$this->store
		);

		return $idChanger;
	}

	/**
	 * @since 3.1
	 *
	 * @return RedirectUpdater
	 */
	public function newRedirectUpdater(): RedirectUpdater {
		$settings = ApplicationFactory::getInstance()->getSettings();

		$redirectUpdater = new RedirectUpdater(
			$this->store,
			$this->newIdChanger(),
			$this->newTableFieldUpdater(),
			$this->newPropertyStatisticsStore()
		);

		$redirectUpdater->setEqualitySupport(
			$settings->get( 'smwgQEqualitySupport' )
		);

		$callableChangeListener = $this->newCallableChangeListener(
			[
				'smwgQEqualitySupport' => [ $redirectUpdater, 'applyChangesFromListener' ]
			]
		);

		$settings->registerChangeListener( $callableChangeListener );

		return $redirectUpdater;
	}

	/**
	 * @since 3.0
	 *
	 * @return DuplicateFinder
	 */
	public function newDuplicateFinder(): DuplicateFinder {
		$duplicateFinder = new DuplicateFinder(
			$this->store,
			$this->getIteratorFactory()
		);

		return $duplicateFinder;
	}

	/**
	 * @since 3.0
	 *
	 * @return SubobjectListFinder
	 */
	public function newSubobjectListFinder(): SubobjectListFinder {
		$subobjectListFinder = new SubobjectListFinder(
			$this->store,
			$this->getIteratorFactory()
		);

		return $subobjectListFinder;
	}

	/**
	 * @since 3.0
	 *
	 * @return CachingSemanticDataLookup
	 */
	public function newSemanticDataLookup(): CachingSemanticDataLookup {
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
	public function newTableFieldUpdater(): TableFieldUpdater {
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
	public function newRedirectStore(): RedirectStore {
		$settings = ApplicationFactory::getInstance()->getSettings();

		$redirectStore = new RedirectStore(
			$this->store
		);

		$redirectStore->setCommandLineMode(
			Site::isCommandLineMode()
		);

		$redirectStore->setEqualitySupport(
			$settings->get( 'smwgQEqualitySupport' )
		);

		$callableChangeListener = $this->newCallableChangeListener(
			[
				'smwgQEqualitySupport' => [ $redirectStore, 'applyChangesFromListener' ]
			]
		);

		$settings->registerChangeListener( $callableChangeListener );

		return $redirectStore;
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertyChangeListener
	 */
	public function newPropertyChangeListener(): PropertyChangeListener {
		$applicationFactory = ApplicationFactory::getInstance();

		$propertyChangeListener = new PropertyChangeListener(
			$this->store
		);

		$propertyChangeListener->setLogger(
			$this->getLogger()
		);

		$propertyChangeListener->setHookDispatcher(
			$applicationFactory->getHookDispatcher()
		);

		$propertyChangeListener->loadListeners();

		$hierarchyLookup = $applicationFactory->newHierarchyLookup();

		// #2698
		$hierarchyLookup->registerPropertyChangeListener(
			$propertyChangeListener
		);

		$schemaFactory = $applicationFactory->singleton( 'SchemaFactory' );
		$schemaFinder = $schemaFactory->newSchemaFinder();

		$schemaFinder->registerPropertyChangeListener(
			$propertyChangeListener
		);

		$protectionValidator = $applicationFactory->singleton( 'ProtectionValidator' );

		$protectionValidator->registerPropertyChangeListener(
			$propertyChangeListener
		);

		return $propertyChangeListener;
	}

	/**
	 * @since 3.0
	 *
	 * @param WikiPage $subject
	 *
	 * @return ChangeOp
	 */
	public function newChangeOp( WikiPage $subject ): ChangeOp {
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
	public function newProximityPropertyValueLookup(): ProximityPropertyValueLookup {
		return new ProximityPropertyValueLookup( $this->store );
	}

	/**
	 * @since 3.0
	 *
	 * @return EntityUniquenessLookup
	 */
	public function newEntityUniquenessLookup(): EntityUniquenessLookup {
		return new EntityUniquenessLookup(
			$this->store,
			$this->getIteratorFactory()
		);
	}

	/**
	 * @since 3.1
	 *
	 * @return QueryDependencyLinksStoreFactory
	 */
	public function newQueryDependencyLinksStoreFactory(): QueryDependencyLinksStoreFactory {
		return new QueryDependencyLinksStoreFactory();
	}

	/**
	 * @since 3.1
	 *
	 * @return SortLetter
	 */
	public function newSortLetter(): SortLetter {
		return new SortLetter( $this->store, Collator::singleton() );
	}

	/**
	 * @since 3.1
	 *
	 * @return PropertyTableIdReferenceDisposer
	 */
	public function newPropertyTableIdReferenceDisposer(): PropertyTableIdReferenceDisposer {
		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$propertyTableIdReferenceDisposer = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$propertyTableIdReferenceDisposer->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		$propertyTableIdReferenceDisposer->setFulltextTableUsage(
			$settings->get( 'smwgEnabledFulltextSearch' )
		);

		$propertyTableIdReferenceDisposer->setNamespacesWithSemanticLinks(
			$settings->get( 'smwgNamespacesWithSemanticLinks' )
		);

		return $propertyTableIdReferenceDisposer;
	}

	/**
	 * @since 3.1
	 *
	 * @return MissingRedirectLookup
	 */
	public function newMissingRedirectLookup(): MissingRedirectLookup {
		return new MissingRedirectLookup( $this->store );
	}

	/**
	 * @since 3.1
	 *
	 * @return MonolingualTextLookup
	 */
	public function newMonolingualTextLookup(): MonolingualTextLookup {
		return new MonolingualTextLookup( $this->store );
	}

	/**
	 * @since 3.1
	 *
	 * @return DisplayTitleLookup
	 */
	public function newDisplayTitleLookup(): DisplayTitleLookup {
		return new DisplayTitleLookup( $this->store );
	}

	/**
	 * @since 3.2
	 *
	 * @return ByGroupPropertyValuesLookup
	 */
	public function newByGroupPropertyValuesLookup(): ByGroupPropertyValuesLookup {
		return new ByGroupPropertyValuesLookup( $this->store );
	}

	/**
	 * @since 3.1
	 *
	 * @return PrefetchItemLookup
	 */
	public function newPrefetchItemLookup(): PrefetchItemLookup {
		return new PrefetchItemLookup(
			$this->store,
			$this->newSemanticDataLookup(),
			$this->newPropertySubjectsLookup()
		);
	}

	/**
	 * @since 3.1
	 *
	 * @return PrefetchCache
	 */
	public function newPrefetchCache(): PrefetchCache {
		return new PrefetchCache(
			$this->store,
			$this->newPrefetchItemLookup()
		);
	}

	/**
	 * @since 3.1
	 *
	 * @return PropertyTypeFinder
	 */
	public function newPropertyTypeFinder(): PropertyTypeFinder {
		return new PropertyTypeFinder( $this->store->getConnection( 'mw.db' ) );
	}

	/**
	 * @since 3.1
	 *
	 * @return TableStatisticsLookup
	 */
	public function newTableStatisticsLookup(): TableStatisticsLookup {
		$tableStatisticsLookup = new TableStatisticsLookup(
			$this->store
		);

		return $tableStatisticsLookup;
	}

	/**
	 * @since 3.1
	 *
	 * @return SingleEntityQueryLookup
	 */
	public function newSingleEntityQueryLookup(): SingleEntityQueryLookup {
		$singleEntityQueryLookup = new SingleEntityQueryLookup(
			$this->store
		);

		return $singleEntityQueryLookup;
	}

	/**
	 * @since 3.1
	 *
	 * @return ErrorLookup
	 */
	public function newErrorLookup(): ErrorLookup {
		$errorLookup = new ErrorLookup(
			$this->store
		);

		return $errorLookup;
	}

	/**
	 * @since 3.0
	 *
	 * @return ServicesContainer
	 */
	public function newServicesContainer(): ServicesContainer {
		$servicesContainer = new ServicesContainer(
			[
				'ProximityPropertyValueLookup' => [
					'_service' => [ $this, 'newProximityPropertyValueLookup' ],
					'_type'    => ProximityPropertyValueLookup::class
				],
				'EntityUniquenessLookup' => [
					'_service' => [ $this, 'newEntityUniquenessLookup' ],
					'_type'    => EntityUniquenessLookup::class
				],
				'PropertyTableIdReferenceDisposer' => [
					'_service' => [ $this, 'newPropertyTableIdReferenceDisposer' ],
					'_type'    => PropertyTableIdReferenceDisposer::class
				],
				'QueryDependencyLinksStoreFactory' => [
					'_service' => [ $this, 'newQueryDependencyLinksStoreFactory' ],
					'_type'    => QueryDependencyLinksStoreFactory::class
				],
				'SortLetter' => [
					'_service' => [ $this, 'newSortLetter' ],
					'_type'    => SortLetter::class
				],
				'MissingRedirectLookup' => [
					'_service' => [ $this, 'newMissingRedirectLookup' ],
					'_type'    => MissingRedirectLookup::class
				],
				'MonolingualTextLookup' => [
					'_service' => [ $this, 'newMonolingualTextLookup' ],
					'_type'    => MonolingualTextLookup::class
				],
				'DisplayTitleLookup' => [
					'_service' => [ $this, 'newDisplayTitleLookup' ],
					'_type'    => DisplayTitleLookup::class
				],
				'ByGroupPropertyValuesLookup' => [
					'_service' => [ $this, 'newByGroupPropertyValuesLookup' ],
					'_type'    => ByGroupPropertyValuesLookup::class
				],
				'PropertyTypeFinder' => [
					'_service' => [ $this, 'newPropertyTypeFinder' ],
					'_type'    => PropertyTypeFinder::class
				],
				'PropertyTableIdReferenceFinder' => function () {
					static $singleton;
					$singleton = $singleton === null ? $this->newPropertyTableIdReferenceFinder() : $singleton;
					return $singleton;
				},
				'PrefetchCache' => [
					'_service' => [ $this, 'newPrefetchCache' ],
					'_type'    => PrefetchCache::class
				],
				'PrefetchItemLookup' => [
					'_service' => [ $this, 'newPrefetchItemLookup' ],
					'_type'    => PrefetchItemLookup::class
				],
				'ErrorLookup' => [
					'_service' => [ $this, 'newErrorLookup' ],
					'_type'    => ErrorLookup::class
				],
				'TableStatisticsLookup' => [
					'_service' => [ $this, 'newTableStatisticsLookup' ],
					'_type'    => TableStatisticsLookup::class
				],
				'SingleEntityQueryLookup' => [
					'_service' => [ $this, 'newSingleEntityQueryLookup' ],
					'_type'    => SingleEntityQueryLookup::class
				],
			]
		);

		return $servicesContainer;
	}

	private function getIteratorFactory(): IteratorFactory {
		return ApplicationFactory::getInstance()->getIteratorFactory();
	}

	private function newCallableChangeListener( array $args ): CallableChangeListener {
		$callableChangeListener = new CallableChangeListener( $args );

		$callableChangeListener->setLogger(
			$this->getLogger()
		);

		return $callableChangeListener;
	}
}
