<?php

namespace SMW\SQLStore;

use SMW\SQLStore\Lookup\UsageStatisticsListLookup;
use SMW\SQLStore\Lookup\PropertyUsageListLookup;
use SMW\SQLStore\Lookup\UnusedPropertyListLookup;
use SMW\SQLStore\Lookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\Lookup\CachedListLookup;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\SQLStore\Lookup\CachedValueLookupStore;
use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\SQLStore\QueryEngine\ConceptQueryResolver;
use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\SQLStore\QueryEngine\EngineOptions;
use Onoi\Cache\Cache;
use SMW\EventHandler;
use Onoi\BlobStore\BlobStore;
use SMW\SQLStore\ConceptCache;
use SMW\ApplicationFactory;
use SMW\CircularReferenceGuard;
use SMWSQLStore3;
use SMWRequestOptions as RequestOptions;
use SMW\DIProperty;

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
	 * @since 2.2
	 *
	 * @param SMWSQLStore3 $store
	 */
	public function __construct( SMWSQLStore3 $store ) {
		$this->store = $store;
		$this->settings = ApplicationFactory::getInstance()->getSettings();
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newMasterQueryEngine() {

		$hierarchyTempTableBuilder = new HierarchyTempTableBuilder(
			$this->store->getConnection( 'mw.db' ),
			$this->newTemporaryIdTableCreator()
		);

		$hierarchyTempTableBuilder->setPropertyHierarchyTableDefinition(
			$this->store->findPropertyTableID( new DIProperty( '_SUBP' ) ),
			$GLOBALS['smwgQSubpropertyDepth']
		);

		$hierarchyTempTableBuilder->setClassHierarchyTableDefinition(
			$this->store->findPropertyTableID( new DIProperty( '_SUBC' ) ),
			$GLOBALS['smwgQSubcategoryDepth']
		);

		$querySegmentListProcessor = new QuerySegmentListProcessor(
			$this->store->getConnection( 'mw.db' ),
			$this->newTemporaryIdTableCreator(),
			$hierarchyTempTableBuilder
		);

		return new QueryEngine(
			$this->store,
			new QuerySegmentListBuilder( $this->store ),
			$querySegmentListProcessor,
			new EngineOptions()
		);
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

		$blobStore = new BlobStore(
			'smw:vl:store',
			$cacheFactory->newMediaWikiCompositeCache( $GLOBALS['smwgValueLookupCacheType'] )
		);

		// If CACHE_NONE is selected, disable the usage
		$blobStore->setUsageState(
			$GLOBALS['smwgValueLookupCacheType'] !== CACHE_NONE
		);

		$blobStore->setExpiryInSeconds(
			$GLOBALS['smwgValueLookupCacheLifetime']
		);

		$blobStore->setNamespacePrefix(
			$cacheFactory->getCachePrefix()
		);

		$cachedValueLookupStore = new CachedValueLookupStore(
			$this->store,
			$blobStore
		);

		$cachedValueLookupStore->setValueLookupFeatures(
			$GLOBALS['smwgValueLookupFeatures']
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

	private function newPropertyStatisticsStore() {

		$propertyStatisticsTable = new PropertyStatisticsTable(
			$this->store->getConnection( 'mw.db' ),
			SMWSQLStore3::PROPERTY_STATISTICS_TABLE
		);

		return $propertyStatisticsTable;
	}

	private function newTemporaryIdTableCreator() {
		return new TemporaryIdTableCreator( $GLOBALS['wgDBtype'] );
	}

}
