<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
use SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver;
use SMW\Store;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryDependencyLinksStoreFactory {

	/**
	 * @var $applicationFactory
	 */
	private $applicationFactory;

	/**
	 * @since 2.4
	 */
	public function __construct() {
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @since 2.4
	 *
	 * @param QueryResult|string $queryResult
	 *
	 * @return QueryResultDependencyListResolver
	 */
	public function newQueryResultDependencyListResolver( $queryResult ) {

		$queryResultDependencyListResolver = new QueryResultDependencyListResolver(
			$queryResult,
			$this->applicationFactory->newPropertyHierarchyLookup()
		);

		$queryResultDependencyListResolver->setPropertyDependencyExemptionlist(
			$this->applicationFactory->getSettings()->get( 'smwgPropertyDependencyExemptionlist' )
		);

		return $queryResultDependencyListResolver;
	}

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 *
	 * @return QueryDependencyLinksStore
	 */
	public function newQueryDependencyLinksStore( $store ) {

		$queryDependencyLinksStore = new QueryDependencyLinksStore(
			new DependencyLinksTableUpdater( $store )
		);

		$queryDependencyLinksStore->setEnabledState(
			$this->applicationFactory->getSettings()->get( 'smwgEnabledQueryDependencyLinksStore' )
		);

		return $queryDependencyLinksStore;
	}

}

