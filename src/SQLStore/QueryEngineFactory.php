<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SQLStore\QueryEngine\EngineOptions;
use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\SQLStore\QueryEngine\OrderCondition;
use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuildManager;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;

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
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
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
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgQFilterDuplicates' )
		);

		return $querySegmentListBuilder;
	}

	/**
	 * @since 2.4
	 *
	 * @return QuerySegmentListProcessor
	 */
	public function newQuerySegmentListProcessor() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$connection = $this->store->getConnection( 'mw.db.queryengine' );
		$temporaryTableBuilder = $this->newTemporaryTableBuilder();

		$hierarchyTempTableBuilder = new HierarchyTempTableBuilder(
			$connection,
			$temporaryTableBuilder
		);

		$hierarchyTempTableBuilder->setPropertyHierarchyTableDefinition(
			$this->store->findPropertyTableID( new DIProperty( '_SUBP' ) ),
			$settings->get( 'smwgQSubpropertyDepth' )
		);

		$hierarchyTempTableBuilder->setClassHierarchyTableDefinition(
			$this->store->findPropertyTableID( new DIProperty( '_SUBC' ) ),
			$settings->get( 'smwgQSubcategoryDepth' )
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
		$applicationFactory = ApplicationFactory::getInstance();

		$settings = $applicationFactory->getSettings();

		$orderCondition = new OrderCondition(
			$querySegmentListBuilder
		);

		$orderCondition->isSupported(
			$settings->isFlagSet( 'smwgQSortFeatures', SMW_QSORT )
		);

		$orderCondition->asUnconditional(
			$settings->isFlagSet( 'smwgQSortFeatures', SMW_QSORT_UNCONDITIONAL )
		);

		$querySegmentListBuildManager = new QuerySegmentListBuildManager(
			$this->store->getConnection( 'mw.db.queryengine' ),
			$querySegmentListBuilder,
			$orderCondition
		);

		$queryEngine = new QueryEngine(
			$this->store,
			$querySegmentListBuildManager,
			$this->newQuerySegmentListProcessor(),
			new EngineOptions()
		);

		$queryEngine->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		return $queryEngine;
	}

	/**
	 * @since 2.5
	 *
	 * @return ConceptQuerySegmentBuilder
	 */
	public function newConceptQuerySegmentBuilder() {

		$pplicationFactory = ApplicationFactory::getInstance();

		$conceptQuerySegmentBuilder = new ConceptQuerySegmentBuilder(
			$this->newQuerySegmentListBuilder(),
			$this->newQuerySegmentListProcessor()
		);

		$conceptQuerySegmentBuilder->setQueryParser(
			$pplicationFactory->getQueryFactory()->newQueryParser(
				$pplicationFactory->getSettings()->get( 'smwgQConceptFeatures' )
			)
		);

		return $conceptQuerySegmentBuilder;
	}

	private function newTemporaryTableBuilder() {

		$temporaryTableBuilder = new TemporaryTableBuilder(
			$this->store->getConnection( 'mw.db.queryengine' )
		);

		$temporaryTableBuilder->setAutoCommitFlag(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgQTemporaryTablesAutoCommitMode' )
		);

		return $temporaryTableBuilder;
	}

}
