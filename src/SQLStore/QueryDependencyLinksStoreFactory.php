<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\Site;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater;
use SMW\SQLStore\QueryDependency\DependencyLinksUpdateJournal;
use SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
use SMW\SQLStore\QueryDependency\QueryReferenceBacklinks;
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
	 * @since 3.0
	 *
	 * @return DependencyLinksUpdateJournal
	 */
	public function newDependencyLinksUpdateJournal() {

		$applicationFactory = ApplicationFactory::getInstance();

		$dependencyLinksUpdateJournal = new DependencyLinksUpdateJournal(
			$applicationFactory->getCache(),
			$applicationFactory->newDeferredCallableUpdate()
		);

		$dependencyLinksUpdateJournal->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		return $dependencyLinksUpdateJournal;
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
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param ChangeOp $changeOp
	 *
	 * @return EntityIdListRelevanceDetectionFilter
	 */
	public function newEntityIdListRelevanceDetectionFilter( Store $store, ChangeOp $changeOp ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$entityIdListRelevanceDetectionFilter = new EntityIdListRelevanceDetectionFilter(
			$store,
			$changeOp
		);

		$entityIdListRelevanceDetectionFilter->setLogger(
			ApplicationFactory::getInstance()->getMediaWikiLogger()
		);

		$entityIdListRelevanceDetectionFilter->setPropertyExemptionList(
			$settings->get( 'smwgQueryDependencyPropertyExemptionList' )
		);

		$entityIdListRelevanceDetectionFilter->setAffiliatePropertyDetectionList(
			$settings->get( 'smwgQueryDependencyAffiliatePropertyDetectionList' )
		);

		return $entityIdListRelevanceDetectionFilter;
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

}
