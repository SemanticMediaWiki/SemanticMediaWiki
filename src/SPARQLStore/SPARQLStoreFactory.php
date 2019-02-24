<?php

namespace SMW\SPARQLStore;

use SMW\ApplicationFactory;
use SMW\Connection\ConnectionManager;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\QueryResultFactory;
use SMW\Store;
use SMW\StoreFactory;
use SMW\Utils\CircularReferenceGuard;

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
	 * @since 2.2
	 *
	 * @param SPARQLStore $store
	 */
	public function __construct( SPARQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $storeClass
	 *
	 * @return Store
	 */
	public function getBaseStore( $storeClass ) {
		return StoreFactory::getStore( $storeClass );
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryEngine
	 */
	public function newMasterQueryEngine() {

		$engineOptions = new EngineOptions();

		$circularReferenceGuard = new CircularReferenceGuard( 'sparql-queryengine' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$conditionBuilder = new ConditionBuilder(
			new DescriptionInterpreterFactory(),
			$engineOptions
		);

		$conditionBuilder->setCircularReferenceGuard(
			$circularReferenceGuard
		);

		$conditionBuilder->setHierarchyLookup(
			ApplicationFactory::getInstance()->newHierarchyLookup()
		);

		$queryEngine = new QueryEngine(
			$this->store->getConnection( 'sparql' ),
			$conditionBuilder,
			new QueryResultFactory( $this->store ),
			$engineOptions
		);

		return $queryEngine;
	}

	/**
	 * @since 2.5
	 *
	 * @return RepositoryRedirectLookup
	 */
	public function newRepositoryRedirectLookup() {
		return new RepositoryRedirectLookup( $this->store->getConnection( 'sparql' ) );
	}

	/**
	 * @since 2.5
	 *
	 * @return TurtleTriplesBuilder
	 */
	public function newTurtleTriplesBuilder() {

		$applicationFactory = ApplicationFactory::getInstance();

		$turtleTriplesBuilder = new TurtleTriplesBuilder(
			$this->newRepositoryRedirectLookup(),
			$applicationFactory->getInMemoryPoolCache()->getPoolCacheById( TurtleTriplesBuilder::POOLCACHE_ID )
		);

		$turtleTriplesBuilder->setTriplesChunkSize( 80 );

		return $turtleTriplesBuilder;
	}

	/**
	 * @since 2.5
	 *
	 * @return ReplicationDataTruncator
	 */
	public function newReplicationDataTruncator() {

		$replicationDataTruncator = new ReplicationDataTruncator();

		$replicationDataTruncator->setPropertyExemptionList(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgSparqlReplicationPropertyExemptionList' )
		);

		return $replicationDataTruncator;
	}

	/**
	 * @since 2.2
	 *
	 * @return ConnectionManager
	 */
	public function getConnectionManager() {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$repositoryConnectionProvider = new RepositoryConnectionProvider(
			$settings->get( 'smwgSparqlRepositoryConnector' ),
			$settings->get( 'smwgSparqlDefaultGraph' ),
			$settings->dotGet( 'smwgSparqlEndpoint.query' ),
			$settings->dotGet( 'smwgSparqlEndpoint.update', '' ),
			$settings->dotGet( 'smwgSparqlEndpoint.data', '' )
		);

		$repositoryConnectionProvider->setHttpVersionTo(
			$settings->get( 'smwgSparqlRepositoryConnectorForcedHttpVersion' )
		);

		$repositoryConnectionProvider = new RepositoryConnectionProvider();

		$connectionManager = $applicationFactory->getConnectionManager();
		$connectionManager->registerConnectionProvider(
			'sparql',
			$repositoryConnectionProvider
		);

		return $connectionManager;
	}

}
