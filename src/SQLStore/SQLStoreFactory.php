<?php

namespace SMW\SQLStore;

use Onoi\Cache\Cache;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\NullMessageReporter;
use SMW\MediaWiki\Collator;
use SMW\ApplicationFactory;
use SMW\ChangePropListener;
use SMW\DIWikiPage;
use SMW\Options;
use SMW\Site;
use SMW\SortLetter;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\DataItemHandlerFactory;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\EntityStore\CacheWarmer;
use SMW\SQLStore\EntityStore\IdEntityFinder;
use SMW\SQLStore\EntityStore\EntityIdFinder;
use SMW\SQLStore\EntityStore\SequenceMapFinder;
use SMW\SQLStore\EntityStore\IdChanger;
use SMW\SQLStore\EntityStore\DuplicateFinder;
use SMW\SQLStore\EntityStore\EntityLookup;
use SMW\SQLStore\EntityStore\SemanticDataLookup;
use SMW\SQLStore\EntityStore\SubobjectListFinder;
use SMW\SQLStore\EntityStore\TraversalPropertyLookup;
use SMW\SQLStore\EntityStore\PropertySubjectsLookup;
use SMW\SQLStore\EntityStore\PropertiesLookup;
use SMW\SQLStore\EntityStore\PrefetchCache;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\PropertyTable\PropertyTableHashes;
use SMW\SQLStore\Lookup\CachedListLookup;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\SQLStore\Lookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;
use SMW\SQLStore\Lookup\MissingRedirectLookup;
use SMW\SQLStore\Lookup\MonolingualTextLookup;
use SMW\SQLStore\Lookup\DisplayTitleLookup;
use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\SQLStore\Lookup\EntityUniquenessLookup;
use SMW\SQLStore\Lookup\TableStatisticsLookup;
use SMW\SQLStore\Lookup\SingleEntityQueryLookup;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\SQLStore\TableBuilder\TableSchemaManager;
use SMW\SQLStore\TableBuilder\TableBuildExaminer;
use SMW\SQLStore\TableBuilder\TableBuildExaminerFactory;
use SMW\SQLStore\Rebuilder\EntityValidator;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\Utils\CircularReferenceGuard;
use SMWRequestOptions as RequestOptions;
use SMW\Services\ServicesContainer;

/**
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class SQLStoreFactory {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var QueryEngineFactory
	 */
	private $queryEngineFactory;

	/**
	 * @since 2.2
	 *
	 * @param SQLStore $store
	 * @param MessageReporter|null $messageReporter
	 */
	public function __construct( SQLStore $store, MessageReporter $messageReporter = null ) {
		$this->store = $store;
		$this->messageReporter = $messageReporter;

		if ( $this->messageReporter === null ) {
			$this->messageReporter = new NullMessageReporter();
		}

		$this->queryEngineFactory = new QueryEngineFactory( $store );
	}

	/**
	 * @since 3.1
	 *
	 * @return SQLStoreUpdater
	 */
	public function newUpdater() {
		return new SQLStoreUpdater( $this->store, $this );
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
	public function newEntityIdManager() {
		return new EntityIdManager( $this->store, $this );
	}

	/**
	 * @since 3.0
	 *
	 * @return PropertyTableUpdater
	 */
	public function newPropertyTableUpdater() {
		return new PropertyTableUpdater( $this->store, $this->newPropertyStatisticsStore() );
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
	public function newPropertyUsageCachedListLookup( RequestOptions $requestOptions = null ) {

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
	public function newUnusedPropertyCachedListLookup( RequestOptions $requestOptions = null ) {

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
	public function newUndeclaredPropertyCachedListLookup( RequestOptions $requestOptions = null ) {

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
	 * @param boolean $useCache
	 * @param integer $cacheExpiry
	 *
	 * @return ListLookup
	 */
	public function newCachedListLookup( ListLookup $listLookup, $useCache, $cacheExpiry ) {

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
	 * @return Rebuilder
	 */
	public function newRebuilder() {

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

		$rebuilder = new Rebuilder(
			$this->store,
			$applicationFactory->newTitleFactory(),
			$entityValidator
		);

		return $rebuilder;
	}

	/**
	 * @since 2.5
	 *
	 * @return EntityLookup
	 */
	public function newEntityLookup() {
		return new EntityLookup( $this->store, $this );
	}

	/**
	 * @since 2.3
	 *
	 * @return PropertyTableInfoFetcher
	 */
	public function newPropertyTableInfoFetcher() {

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
	public function newPropertyTableIdReferenceFinder() {

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
	public function newPropertyTableHashes( IdCacheManager $idCacheManager ) {

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
	public function newInstaller() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$tableBuilder = TableBuilder::factory(
			$this->store->getConnection( DB_MASTER )
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

		$installer = new Installer(
			$tableSchemaManager,
			$tableBuilder,
			$tableBuildExaminer
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
	public function newDataItemHandlerFactory() {

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
	public function newIdEntityFinder( IdCacheManager $idCacheManager ) {

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
	 * @return IdEntityFinder
	 */
	public function newEntityIdFinder( IdCacheManager $idCacheManager, PropertyTableHashes $propertyTableHashes = null ) {

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
	public function newSequenceMapFinder( IdCacheManager $idCacheManager ) {

		$sequenceMapFinder = new SequenceMapFinder(
			$this->store->getConnection( 'mw.db'),
			$idCacheManager
		);

		return $sequenceMapFinder;
	}

	/**
	 * @since 3.1
	 *
	 * @param IdCacheManager $idCacheManager
	 *
	 * @return CacheWarmer
	 */
	public function newCacheWarmer( IdCacheManager $idCacheManager ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$cacheWarmer = new CacheWarmer(
			$this->store,
			$idCacheManager
		);

		$cacheWarmer->setDisplayTitleFinder(
			$applicationFactory->singleton( 'DisplayTitleFinder', $this->store )
		);

		return $cacheWarmer;
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
	 * @since 3.1
	 *
	 * @return RedirectUpdater
	 */
	public function newRedirectUpdater() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$redirectUpdater = new RedirectUpdater(
			$this->store,
			$this->newIdChanger(),
			$this->newTableFieldUpdater(),
			$this->newPropertyStatisticsStore()
		);

		$redirectUpdater->setEqualitySupportFlag(
			$settings->get( 'smwgQEqualitySupport' )
		);

		return $redirectUpdater;
	}

	/**
	 * @since 3.0
	 *
	 * @return DuplicateFinder
	 */
	public function newDuplicateFinder() {

		$duplicateFinder = new DuplicateFinder(
			$this->store,
			$this->getIteratorFactory()
		);

		return $duplicateFinder;
	}

	/**
	 * @since 3.0
	 *
	 * @return HierarchyLookup
	 */
	public function newHierarchyLookup() {
		return ApplicationFactory::getInstance()->newHierarchyLookup();
	}

	/**
	 * @since 3.0
	 *
	 * @return SubobjectListFinder
	 */
	public function newSubobjectListFinder() {

		$subobjectListFinder = new SubobjectListFinder(
			$this->store,
			$this->getIteratorFactory()
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

		$redirectStore->setCommandLineMode(
			Site::isCommandLineMode()
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
	 * @return EntityUniquenessLookup
	 */
	public function newEntityUniquenessLookup() {
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
	public function newQueryDependencyLinksStoreFactory() {
		return new QueryDependencyLinksStoreFactory();
	}

	/**
	 * @since 3.1
	 *
	 * @return SortLetter
	 */
	public function newSortLetter() {
		return new SortLetter( $this->store, Collator::singleton() );
	}

	/**
	 * @since 3.1
	 *
	 * @return PropertyTableIdReferenceDisposer
	 */
	public function newPropertyTableIdReferenceDisposer() {
		return new PropertyTableIdReferenceDisposer(
			$this->store,
			$this->getIteratorFactory()
		);
	}

	/**
	 * @since 3.1
	 *
	 * @return MissingRedirectLookup
	 */
	public function newMissingRedirectLookup() {
		return new MissingRedirectLookup( $this->store );
	}

	/**
	 * @since 3.1
	 *
	 * @return MonolingualTextLookup
	 */
	public function newMonolingualTextLookup() {
		return new MonolingualTextLookup( $this->store );
	}

	/**
	 * @since 3.1
	 *
	 * @return DisplayTitleLookup
	 */
	public function newDisplayTitleLookup() {
		return new DisplayTitleLookup( $this->store );
	}

	/**
	 * @since 3.1
	 *
	 * @return PrefetchItemLookup
	 */
	public function newPrefetchItemLookup() {
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
	public function newPrefetchCache() {
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
	public function newPropertyTypeFinder() {
		return new PropertyTypeFinder( $this->store->getConnection( 'mw.db' ) );
	}

	/**
	 * @since 3.1
	 *
	 * @return TableStatisticsLookup
	 */
	public function newTableStatisticsLookup() {

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
	public function newSingleEntityQueryLookup() {

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
	public function newErrorLookup() {

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
	public function newServicesContainer() {

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
				'PropertyTypeFinder' => [
					'_service' => [ $this, 'newPropertyTypeFinder' ],
					'_type'    => PropertyTypeFinder::class
				],
				'PropertyTableIdReferenceFinder' => function() {
					static $singleton;
					return $singleton = $singleton === null ? $this->newPropertyTableIdReferenceFinder() : $singleton;
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

	private function getIteratorFactory() {
		return ApplicationFactory::getInstance()->getIteratorFactory();
	}

}
