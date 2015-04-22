<?php

namespace SMW\SQLStore;

use SMW\SQLStore\ListLookup\UsageStatisticsListLookup;
use SMW\SQLStore\ListLookup\PropertyUsageListLookup;
use SMW\SQLStore\ListLookup\UnusedPropertyListLookup;
use SMW\SQLStore\ListLookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\ListLookup\CachedListLookup;
use SMW\SQLStore\ListLookup;
use SMW\SQLStore\QueryEngine\ResolverOptions;
use SMW\SQLStore\QueryEngine\QuerySegmentListResolver;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\ConceptQueryResolver;
use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\SQLStore\QueryEngine\EngineOptions;
use SMW\ApplicationFactory;
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
	 * @since 2.2
	 *
	 * @param SMWSQLStore3 $store
	 */
	public function __construct( SMWSQLStore3 $store ) {
		$this->store = $store;
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newMasterQueryEngine() {

		$resolverOptions = new ResolverOptions();

		$resolverOptions->set(
			'hierarchytables',
			array(
				'_SUBP' => $this->store->findPropertyTableID( new DIProperty( '_SUBP' ) ),
				'_SUBC' => $this->store->findPropertyTableID( new DIProperty( '_SUBC' ) )
			)
		);

		$querySegmentListResolver = new QuerySegmentListResolver(
			$this->store->getConnection( 'mw.db' ),
			$this->newTemporaryIdTableCreator(),
			$resolverOptions
		);

		return new QueryEngine(
			$this->store,
			new QueryBuilder( $this->store ),
			$querySegmentListResolver,
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
	 * @return UsageStatisticsListLookup
	 */
	public function newUsageStatisticsListLookup() {

		$propertyStatisticsStore = new PropertyStatisticsTable(
			$this->store->getConnection( 'mw.db' ),
			$this->store->getStatisticsTable()
		);

		return new UsageStatisticsListLookup( $this->store, $propertyStatisticsStore );
	}

	/**
	 * @since 2.2
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return PropertyUsageListLookup
	 */
	public function newPropertyUsageListLookup( RequestOptions $requestOptions = null ) {

		$propertyStatisticsStore = new PropertyStatisticsTable(
			$this->store->getConnection( 'mw.db' ),
			$this->store->getStatisticsTable()
		);

		return new PropertyUsageListLookup(
			$this->store,
			$propertyStatisticsStore,
			$requestOptions
		);
	}

	/**
	 * @since 2.2
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return UnusedPropertyListLookup
	 */
	public function newUnusedPropertyListLookup( RequestOptions $requestOptions = null ) {

		$propertyStatisticsStore = new PropertyStatisticsTable(
			$this->store->getConnection( 'mw.db' ),
			$this->store->getStatisticsTable()
		);

		return new UnusedPropertyListLookup(
			$this->store,
			$propertyStatisticsStore,
			$requestOptions
		);
	}

	/**
	 * @since 2.2
	 *
	 * @param RequestOptions|null $requestOptions
	 * @param string $defaultPropertyType
	 *
	 * @return UndeclaredPropertyListLookup
	 */
	public function newUndeclaredPropertyListLookup( RequestOptions $requestOptions = null, $defaultPropertyType ) {

		return new UndeclaredPropertyListLookup(
			$this->store,
			$defaultPropertyType,
			$requestOptions
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

	private function newTemporaryIdTableCreator() {
		return new TemporaryIdTableCreator( $GLOBALS['wgDBtype'] );
	}

}
