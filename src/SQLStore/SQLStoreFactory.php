<?php

namespace SMW\SQLStore;

use SMW\SQLStore\ListLookup\UsageStatisticsListLookup;
use SMW\SQLStore\ListLookup\PropertyUsageListLookup;
use SMW\SQLStore\ListLookup\UnusedPropertyListLookup;
use SMW\SQLStore\ListLookup\UndeclaredPropertyListLookup;
use SMW\SQLStore\ListLookup\CachedListLookup;
use SMW\SQLStore\ListLookup;
use Onoi\Cache\Cache;
use Doctrine\DBAL\Connection;
use SMW\SQLStore\QueryEngine\ConceptCache;
use SMW\ApplicationFactory;
use SMWSQLStore3;
use SMWSQLStore3QueryEngine;
use SMWRequestOptions as RequestOptions;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SQLStoreFactory {

	/**
	 * @var SMWSQLStore3
	 */
	private $store;

	/**
	 * @var Connection|null
	 */
	private $dbalConnection = null;

	public function __construct( SMWSQLStore3 $store ) {
		$this->store = $store;
	}

	public function newSalveQueryEngine() {
		return new SMWSQLStore3QueryEngine(
			$this->store,
			$this->newTemporaryIdTableCreator()
		);
	}

	public function newMasterQueryEngine() {
		return new SMWSQLStore3QueryEngine(
			$this->store,
			$this->newTemporaryIdTableCreator()
		);
	}

	private function newTemporaryIdTableCreator() {
		return new TemporaryIdTableCreator( $GLOBALS['wgDBtype'] );
	}

	public function newSlaveConceptCache() {
		return new ConceptCache(
			$this->newSalveQueryEngine(),
			$this->store
		);
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

	private function getConnection() {
		if ( $this->dbalConnection === null ) {
			$builder = new ConnectionBuilder( $GLOBALS );
			$this->dbalConnection = $builder->newConnection();
		}

		return $this->dbalConnection;
	}

}
