<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SQLStore\QueryEngine\EngineOptions;
use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder;
use SMW\SQLStore\QueryEngine\OrderConditionsComplementor;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuildManager;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryEngineFactory {

	/**
	 * @var SMWSQLStore3
	 */
	private $store;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	/**
	 * @since 2.4
	 *
	 * @return QuerySegmentListBuilder
	 */
	public function newQuerySegmentListBuilder() {

		$querySegmentListBuilder = new QuerySegmentListBuilder(
			$this->store,
			new DescriptionInterpreterFactory()
		);

		$querySegmentListBuilder->isFilterDuplicates(
			$this->applicationFactory->getSettings()->get( 'smwgQFilterDuplicates' )
		);

		return $querySegmentListBuilder;
	}

	/**
	 * @since 2.4
	 *
	 * @return QuerySegmentListProcessor
	 */
	public function newQuerySegmentListProcessor() {

		$connection = $this->store->getConnection( 'mw.db.queryengine' );
		$temporaryTableBuilder = $this->newTemporaryTableBuilder();

		$hierarchyTempTableBuilder = new HierarchyTempTableBuilder(
			$connection,
			$temporaryTableBuilder
		);

		$hierarchyTempTableBuilder->setPropertyHierarchyTableDefinition(
			$this->store->findPropertyTableID( new DIProperty( '_SUBP' ) ),
			$this->applicationFactory->getSettings()->get( 'smwgQSubpropertyDepth' )
		);

		$hierarchyTempTableBuilder->setClassHierarchyTableDefinition(
			$this->store->findPropertyTableID( new DIProperty( '_SUBC' ) ),
			$this->applicationFactory->getSettings()->get( 'smwgQSubcategoryDepth' )
		);

		$querySegmentListProcessor = new QuerySegmentListProcessor(
			$connection,
			$temporaryTableBuilder,
			$hierarchyTempTableBuilder
		);

		return $querySegmentListProcessor;
	}

	/**
	 * @since 2.4
	 *
	 * @return QueryEngine
	 */
	public function newQueryEngine() {

		$querySegmentListBuilder = $this->newQuerySegmentListBuilder();

		$orderConditionsComplementor = new OrderConditionsComplementor(
			$querySegmentListBuilder
		);

		$orderConditionsComplementor->isSupported(
			$this->applicationFactory->getSettings()->get( 'smwgQSortingSupport' )
		);

		$querySegmentListBuildManager = new QuerySegmentListBuildManager(
			$this->store->getConnection( 'mw.db.queryengine' ),
			$querySegmentListBuilder,
			$orderConditionsComplementor
		);

		$queryEngine = new QueryEngine(
			$this->store,
			$querySegmentListBuildManager,
			$this->newQuerySegmentListProcessor(),
			new EngineOptions()
		);

		$queryEngine->setLogger(
			$this->applicationFactory->getMediaWikiLogger()
		);

		return $queryEngine;
	}

	/**
	 * @since 2.5
	 *
	 * @return ConceptQuerySegmentBuilder
	 */
	public function newConceptQuerySegmentBuilder() {

		$conceptQuerySegmentBuilder = new ConceptQuerySegmentBuilder(
			$this->newQuerySegmentListBuilder(),
			$this->newQuerySegmentListProcessor()
		);

		$conceptQuerySegmentBuilder->setConceptFeatures(
			$this->applicationFactory->getSettings()->get( 'smwgQConceptFeatures' )
		);

		return $conceptQuerySegmentBuilder;
	}

	private function newTemporaryTableBuilder() {

		$temporaryTableBuilder = new TemporaryTableBuilder(
			$this->store->getConnection( 'mw.db.queryengine' )
		);

		$temporaryTableBuilder->withAutoCommit(
			$this->applicationFactory->getSettings()->get( 'smwgQTemporaryTablesAutoCommitMode' )
		);

		return $temporaryTableBuilder;
	}

}
