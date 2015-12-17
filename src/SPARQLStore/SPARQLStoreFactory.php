<?php

namespace SMW\SPARQLStore;

use SMW\Store;
use SMW\StoreFactory;
use SMW\ConnectionManager;
use SMW\ApplicationFactory;
use SMW\PropertyHierarchyLookup;
use SMW\CircularReferenceGuard;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\QueryResultFactory;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class SPARQLStoreFactory {

	/**
	 * @var SPARQLStore
	 */
	private $store;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @since 2.2
	 *
	 * @param SPARQLStore $store
	 */
	public function __construct( SPARQLStore $store ) {
		$this->store = $store;
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @since 2.2
	 *
	 * @return Store
	 */
	public function newBaseStore( $storeId ) {
		return StoreFactory::getStore( $storeId );
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newMasterQueryEngine() {

		$engineOptions = new EngineOptions();

		$propertyHierarchyLookup = new PropertyHierarchyLookup(
			$this->store,
			$this->applicationFactory->newCacheFactory()->newFixedInMemoryCache( 500 )
		);

		$propertyHierarchyLookup->setSubcategoryDepth(
			$this->applicationFactory->getSettings()->get( 'smwgQSubcategoryDepth' )
		);

		$propertyHierarchyLookup->setSubpropertyDepth(
			$this->applicationFactory->getSettings()->get( 'smwgQSubpropertyDepth' )
		);

		$circularReferenceGuard = new CircularReferenceGuard( 'sparql-query' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$compoundConditionBuilder = new CompoundConditionBuilder(
			$engineOptions
		);

		$compoundConditionBuilder->setCircularReferenceGuard(
			$circularReferenceGuard
		);

		$compoundConditionBuilder->setPropertyHierarchyLookup(
			$propertyHierarchyLookup
		);

		$queryEngine = new QueryEngine(
			$this->store->getConnection( 'sparql' ),
			$compoundConditionBuilder,
			new QueryResultFactory( $this->store ),
			$engineOptions
		);

		return $queryEngine;
	}

	/**
	 * @since 2.2
	 *
	 * @return ConnectionManager
	 */
	public function newConnectionManager() {

		$connectionManager = new ConnectionManager();

		$repositoryConnectionProvider = new RepositoryConnectionProvider();
		$repositoryConnectionProvider->setHttpVersionTo(
			$this->applicationFactory->getSettings()->get( 'smwgSparqlRepositoryConnectorForcedHttpVersion' )
		);

		$connectionManager->registerConnectionProvider(
			'sparql',
			$repositoryConnectionProvider
		);

		return $connectionManager;
	}

}
