<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\CircularReferenceGuard;
use SMW\SQLStore\Lookup\CachedListLookup;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\SQLStore\Lookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\SQLStore\QueryEngine\ConceptQueryResolver;
use SMWRequestOptions as RequestOptions;
use SMWSQLStore3;
use SMW\SQLStore\TableBuilder\TableBuilder;
use Onoi\MessageReporter\MessageReporterFactory;
use SMWSql3SmwIds as IdTableManager;
use SMW\SQLStore\EntityStore\DataItemHandlerDispatcher;
use SMW\SQLStore\EntityStore\PersistentCachedEntityLookup;
use SMW\SQLStore\EntityStore\DirectEntityLookup;

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
	 */
	public function __construct( SMWSQLStore3 $store ) {
		$this->store = $store;
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
		return $this->queryEngineFactory->newQueryEngine();
	}

	/**
	 * @since 2.5
	 *
	 * @return IdTableManager
	 */
	public function newIdTableManager() {

		$idToDataItemMatchFinder = new IdToDataItemMatchFinder(
			$this->store->getConnection( 'mw.db' ),
			$this->applicationFactory->getIteratorFactory()
		);

		return new IdTableManager( $this->store, $idToDataItemMatchFinder );
	}

	/**
	 * @since 2.2
	 *
	 * @return ConceptCache
	 */
	public function newMasterConceptCache() {

		$conceptQueryResolver = new ConceptQueryResolver(
			$this->newMasterQueryEngine()
		);

		$conceptQueryResolver->setConceptFeatures(
			$GLOBALS['smwgQConceptFeatures']
		);

		$conceptCache = new ConceptCache(
			$this->store,
			$conceptQueryResolver
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
			$settings->get( 'smwgStatisticsCache' ),
			$settings->get( 'smwgStatisticsCacheExpiry' )
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
			$settings->get( 'smwgPropertiesCache' ),
			$settings->get( 'smwgPropertiesCacheExpiry' )
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
			$settings->get( 'smwgUnusedPropertiesCache' ),
			$settings->get( 'smwgUnusedPropertiesCacheExpiry' )
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
			$settings->get( 'smwgWantedPropertiesCache' ),
			$settings->get( 'smwgWantedPropertiesCacheExpiry' )
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

		// PHP 5.3
		$factory = $this;

		$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredCallableUpdate( function() use( $factory ) {
			wfDebugLog( 'smw', 'DeferredCachedListLookupUpdate' );

			$factory->newPropertyUsageCachedListLookup()->deleteCache();
			$factory->newUnusedPropertyCachedListLookup()->deleteCache();
			$factory->newUndeclaredPropertyCachedListLookup()->deleteCache();
			$factory->newUsageStatisticsCachedListLookup()->deleteCache();

		} );

		return $deferredCallableUpdate;
	}

	/**
	 * @since 2.3
	 *
	 * @return ByIdDataRebuildDispatcher
	 */
	public function newByIdDataRebuildDispatcher() {
		return new ByIdDataRebuildDispatcher( $this->store );
	}

	/**
	 * @since 2.5
	 *
	 * @return EntityLookup
	 */
	public function newEntityLookup() {

		$settings = $this->applicationFactory->getSettings();
		$directEntityLookup = new DirectEntityLookup( $this->store );

		if ( $settings->get( 'smwgValueLookupFeatures' ) === CACHE_NONE ) {
			return $directEntityLookup;
		}

		$circularReferenceGuard = new CircularReferenceGuard( 'vl:store' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$cacheFactory = $this->applicationFactory->newCacheFactory();

		$blobStore = $cacheFactory->newBlobStore(
			'smw:vl:store',
			$settings->get( 'smwgValueLookupCacheType' ),
			$settings->get( 'smwgValueLookupCacheLifetime' )
		);

		$persistentCachedEntityLookup = new PersistentCachedEntityLookup(
			$directEntityLookup,
			new RedirectTargetLookup( $this->store, $circularReferenceGuard ),
			$blobStore
		);

		$persistentCachedEntityLookup->setCachedLookupFeatures(
			$settings->get( 'smwgValueLookupFeatures' )
		);

		return $persistentCachedEntityLookup;
	}

	/**
	 * @since 2.3
	 *
	 * @return RequestOptionsProcessor
	 */
	public function newRequestOptionsProcessor() {
		return new RequestOptionsProcessor( $this->store );
	}

	/**
	 * @since 2.3
	 *
	 * @return PropertyTableInfoFetcher
	 */
	public function newPropertyTableInfoFetcher() {

		$settings = $this->applicationFactory->getSettings();

		$propertyTableInfoFetcher = new PropertyTableInfoFetcher();

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

		$propertyTableIdReferenceFinder->usesCapitalLinks(
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

		$messageReporter = MessageReporterFactory::getInstance()->newNullMessageReporter();

		$tableBuilder = TableBuilder::factory(
			$this->store->getConnection( DB_MASTER )
		);

		$tableBuilder->setMessageReporter(
			$messageReporter
		);

		$tableIntegrityChecker = new TableIntegrityChecker(
			$this->store
		);

		$tableBuilder->setMessageReporter(
			$messageReporter
		);

		$tableSchemaManager = new TableSchemaManager(
			$this->store,
			$tableBuilder,
			$tableIntegrityChecker
		);

		return new Installer( $tableSchemaManager );
	}

	/**
	 * @since 2.5
	 *
	 * @return DataItemHandlerDispatcher
	 */
	public function newdataItemHandlerDispatcher() {
		return new DataItemHandlerDispatcher( $this->store );
	}

	private function newPropertyStatisticsStore() {

		$propertyStatisticsTable = new PropertyStatisticsTable(
			$this->store->getConnection( 'mw.db' ),
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE
		);

		return $propertyStatisticsTable;
	}


}
