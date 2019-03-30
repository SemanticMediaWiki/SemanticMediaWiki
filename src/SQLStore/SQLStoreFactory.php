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
use SMW\SQLStore\EntityStore\IdChanger;
use SMW\SQLStore\EntityStore\UniquenessLookup;
use SMW\SQLStore\EntityStore\EntityLookup;
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
use SMW\SQLStore\Lookup\MissingRedirectLookup;
use SMW\SQLStore\Lookup\MonolingualTextLookup;
use SMW\SQLStore\TableBuilder\TableBuilder;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\SQLStore\TableBuilder\Examiner\FixedProperties;
use SMW\SQLStore\TableBuilder\Examiner\TouchedField;
use SMW\SQLStore\TableBuilder\Examiner\IdBorder;
use SMW\SQLStore\Rebuilder\EntityValidator;
use SMW\SQLStore\Rebuilder\Rebuilder;
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
		$entityLookup = new EntityLookup( $this->store );
		return $entityLookup;
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
			$this->store,
			new HashField( $this->store ),
			new FixedProperties( $this->store ),
			new TouchedField( $this->store ),
			new IdBorder( $this->store )
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
	 *
	 * @return CacheWarmer
	 */
	public function newCacheWarmer( IdCacheManager $idCacheManager ) {

		$cacheWarmer = new CacheWarmer(
			$this->store,
			$idCacheManager
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
	 * @since 3.0
	 *
	 * @return UniquenessLookup
	 */
	public function newUniquenessLookup() {

		$uniquenessLookup = new UniquenessLookup(
			$this->store,
			$this->getIteratorFactory()
		);

		return $uniquenessLookup;
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
	 * @return PrefetchItemLookup
	 */
	public function newPrefetchItemLookup() {
		return new PrefetchItemLookup(
			$this->store,
			$this->newSemanticDataLookup()
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
				'PropertyTypeFinder' => [
					'_service' => [ $this, 'newPropertyTypeFinder' ],
					'_type'    => PropertyTypeFinder::class
				],
				'PropertyTableIdReferenceFinder' => function() {
					static $singleton;
					return $singleton = $singleton === null ? $this->newPropertyTableIdReferenceFinder() : $singleton;
				},
				'PrefetchItemLookup' => [
					'_service' => [ $this, 'newPrefetchItemLookup' ],
					'_type'    => PrefetchItemLookup::class
				]
			]
		);

		return $servicesContainer;
	}

	private function getIteratorFactory() {
		return ApplicationFactory::getInstance()->getIteratorFactory();
	}

}
