<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\SQLStore\QueryDependency\DependencyLinksTableUpdater;
use SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
use SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver;
use SMW\SQLStore\QueryDependency\QueryReferenceBacklinks;
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
	 * @since 2.4
	 *
	 * @return QueryResultDependencyListResolver
	 */
	public function newQueryResultDependencyListResolver() {

		$queryResultDependencyListResolver = new QueryResultDependencyListResolver(
			ApplicationFactory::getInstance()->newPropertyHierarchyLookup()
		);

		$queryResultDependencyListResolver->setPropertyDependencyExemptionlist(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgQueryDependencyPropertyExemptionList' )
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

		$logger = ApplicationFactory::getInstance()->getMediaWikiLogger();
		$dependencyLinksTableUpdater = new DependencyLinksTableUpdater( $store );

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
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgEnabledQueryDependencyLinksStore' )
		);

		$queryDependencyLinksStore->isCommandLineMode( $GLOBALS['wgCommandLineMode'] );

		return $queryDependencyLinksStore;
	}

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 *
	 * @return EntityIdListRelevanceDetectionFilter
	 */
	public function newEntityIdListRelevanceDetectionFilter( Store $store, CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$entityIdListRelevanceDetectionFilter = new EntityIdListRelevanceDetectionFilter(
			$store,
			$compositePropertyTableDiffIterator
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

