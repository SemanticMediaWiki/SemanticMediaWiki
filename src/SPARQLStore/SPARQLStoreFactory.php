<?php

namespace SMW\SPARQLStore;

use SMW\ApplicationFactory;
use SMW\Utils\CircularReferenceGuard;
use SMW\ConnectionManager;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\EngineOptions;
use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\QueryResultFactory;
use SMW\Store;
use SMW\StoreFactory;

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

		$compoundConditionBuilder = new CompoundConditionBuilder(
			new DescriptionInterpreterFactory(),
			$engineOptions
		);

		$compoundConditionBuilder->setCircularReferenceGuard(
			$circularReferenceGuard
		);

		$compoundConditionBuilder->setPropertyHierarchyLookup(
			ApplicationFactory::getInstance()->newPropertyHierarchyLookup()
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

		$turtleTriplesBuilder = new TurtleTriplesBuilder(
			$this->newRepositoryRedirectLookup()
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
	public function newConnectionManager() {

		$connectionManager = new ConnectionManager();

		$repositoryConnectionProvider = new RepositoryConnectionProvider();
		$repositoryConnectionProvider->setHttpVersionTo(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgSparqlRepositoryConnectorForcedHttpVersion' )
		);

		$connectionManager->registerConnectionProvider(
			'sparql',
			$repositoryConnectionProvider
		);

		return $connectionManager;
	}

}
