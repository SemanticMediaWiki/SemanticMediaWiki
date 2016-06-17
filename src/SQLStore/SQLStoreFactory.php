<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\CircularReferenceGuard;
use SMW\SQLStore\Lookup\CachedListLookup;
use SMW\SQLStore\Lookup\CachedValueLookupStore;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\SQLStore\Lookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\SQLStore\QueryEngine\ConceptQueryResolver;
use SMWRequestOptions as RequestOptions;
use SMWSQLStore3;

/**
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SQLStoreFactory {

	/**
	 * @var SMWSQLStore3
	 */
	private $store;

	/**
	 * @var Settings
	 */
	private $settings;

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
		$this->settings = ApplicationFactory::getInstance()->getSettings();
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

		$usageStatisticsListLookup = new UsageStatisticsListLookup(
			$this->store,
			$this->newPropertyStatisticsStore()
		);

		return $this->newCachedListLookup(
			$usageStatisticsListLookup,
			$this->settings->get( 'smwgStatisticsCache' ),
			$this->settings->get( 'smwgStatisticsCacheExpiry' )
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

		$propertyUsageListLookup = new PropertyUsageListLookup(
			$this->store,
			$this->newPropertyStatisticsStore(),
			$requestOptions
		);

		return $this->newCachedListLookup(
			$propertyUsageListLookup,
			$this->settings->get( 'smwgPropertiesCache' ),
			$this->settings->get( 'smwgPropertiesCacheExpiry' )
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

		$unusedPropertyListLookup = new UnusedPropertyListLookup(
			$this->store,
			$this->newPropertyStatisticsStore(),
			$requestOptions
		);

		return $this->newCachedListLookup(
			$unusedPropertyListLookup,
			$this->settings->get( 'smwgUnusedPropertiesCache' ),
			$this->settings->get( 'smwgUnusedPropertiesCacheExpiry' )
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

		$undeclaredPropertyListLookup = new UndeclaredPropertyListLookup(
			$this->store,
			$this->settings->get( 'smwgPDefaultType' ),
			$requestOptions
		);

		return $this->newCachedListLookup(
			$undeclaredPropertyListLookup,
			$this->settings->get( 'smwgWantedPropertiesCache' ),
			$this->settings->get( 'smwgWantedPropertiesCacheExpiry' )
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
	 * @since 2.3
	 *
	 * @return CachedValueLookupStore
	 */
	public function newCachedValueLookupStore() {

		$circularReferenceGuard = new CircularReferenceGuard( 'vl:store' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$cacheFactory = ApplicationFactory::getInstance()->newCacheFactory();

		$blobStore = $cacheFactory->newBlobStore(
			'smw:vl:store',
			$this->settings->get( 'smwgValueLookupCacheType' ),
			$this->settings->get( 'smwgValueLookupCacheLifetime' )
		);

		$cachedValueLookupStore = new CachedValueLookupStore(
			$this->store,
			$blobStore
		);

		$cachedValueLookupStore->setValueLookupFeatures(
			$this->settings->get( 'smwgValueLookupFeatures' )
		);

		$cachedValueLookupStore->setCircularReferenceGuard(
			$circularReferenceGuard
		);

		return $cachedValueLookupStore;
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

		$propertyTableInfoFetcher = new PropertyTableInfoFetcher();

		$propertyTableInfoFetcher->setCustomFixedPropertyList(
			$this->settings->get( 'smwgFixedProperties' )
		);

		$propertyTableInfoFetcher->setCustomSpecialPropertyList(
			$this->settings->get( 'smwgPageSpecialProperties' )
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

	private function newPropertyStatisticsStore() {

		$propertyStatisticsTable = new PropertyStatisticsTable(
			$this->store->getConnection( 'mw.db' ),
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE
		);

		return $propertyStatisticsTable;
	}


}
