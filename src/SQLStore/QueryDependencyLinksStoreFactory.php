<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\Site;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater;
use SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
use SMW\SQLStore\QueryDependency\QueryReferenceBacklinks;
use SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver;
use SMW\SQLStore\QueryDependency\QueryLinksTableDisposer;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
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
	 * @since 3.1
	 *
	 * @return DependencyLinksValidator
	 */
	public function newDependencyLinksValidator() {

		$applicationFactory = ApplicationFactory::getInstance();

		$dependencyLinksValidator = new DependencyLinksValidator(
			$applicationFactory->getStore()
		);

		$dependencyLinksValidator->setCheckDependencies(
			$applicationFactory->getSettings()->get( 'smwgEnabledQueryDependencyLinksStore' )
		);

		$dependencyLinksValidator->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		return $dependencyLinksValidator;
	}

	/**
	 * @since 2.4
	 *
	 * @return QueryResultDependencyListResolver
	 */
	public function newQueryResultDependencyListResolver() {

		$applicationFactory = ApplicationFactory::getInstance();

		$queryResultDependencyListResolver = new QueryResultDependencyListResolver(
			$applicationFactory->newHierarchyLookup()
		);

		$queryResultDependencyListResolver->setPropertyDependencyExemptionlist(
			$applicationFactory->getSettings()->get( 'smwgQueryDependencyPropertyExemptionList' )
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
	public function newQueryDependencyLinksStore( Store $store ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$logger = $applicationFactory->getMediaWikiLogger();

		$dependencyLinksTableUpdater = new DependencyLinksTableUpdater(
			$store
		);

		$dependencyLinksTableUpdater->setLogger(
			$logger
		);

		$queryDependencyLinksStore = new QueryDependencyLinksStore(
			$this->newQueryResultDependencyListResolver(),
			$dependencyLinksTableUpdater
		);

		$queryDependencyLinksStore->setLogger(
			$logger
		);

		$queryDependencyLinksStore->setEnabled(
			$applicationFactory->getSettings()->get( 'smwgEnabledQueryDependencyLinksStore' )
		);

		$queryDependencyLinksStore->isCommandLineMode(
			Site::isCommandLineMode()
		);

		return $queryDependencyLinksStore;
	}

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 *
	 * @return QueryReferenceBacklinks
	 */
	public function newQueryReferenceBacklinks( Store $store ) {
		return new QueryReferenceBacklinks( $this->newQueryDependencyLinksStore( $store ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return QueryLinksTableDisposer
	 */
	public function newQueryLinksTableDisposer( Store $store ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$queryLinksTableDisposer = new QueryLinksTableDisposer(
			$store,
			$applicationFactory->getIteratorFactory()
		);

		return $queryLinksTableDisposer;
	}

}
