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
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use SMW\Utils\CircularReferenceGuard;

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
	 * @return ConditionBuilder
	 */
	public function newConditionBuilder() {

		$settings = ApplicationFactory::getInstance()->getSettings();
		$orderCondition = new OrderCondition();

		$orderCondition->isSupported(
			$settings->isFlagSet( 'smwgQSortFeatures', SMW_QSORT )
		);

		$orderCondition->asUnconditional(
			$settings->isFlagSet( 'smwgQSortFeatures', SMW_QSORT_UNCONDITIONAL )
		);

		$circularReferenceGuard = new CircularReferenceGuard( 'sql-query' );
		$circularReferenceGuard->setMaxRecursionDepth( 2 );

		$conditionBuilder = new ConditionBuilder(
			$this->store,
			$orderCondition,
			new DescriptionInterpreterFactory( $this->store, $circularReferenceGuard ),
			$circularReferenceGuard
		);

		$conditionBuilder->isFilterDuplicates(
			$settings->get( 'smwgQFilterDuplicates' )
		);

		return $conditionBuilder;
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

		$hierarchyTempTableBuilder->setTableDefinitions(
			[
				'property' => [
					'table' => $this->store->findPropertyTableID( new DIProperty( '_SUBP' ) ),
					'depth' => $settings->get( 'smwgQSubpropertyDepth' )
				],
				'class' => [
					'table' => $this->store->findPropertyTableID( new DIProperty( '_SUBC' ) ),
					'depth' => $settings->get( 'smwgQSubcategoryDepth' )
				]

			]
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

		$applicationFactory = ApplicationFactory::getInstance();

		$queryEngine = new QueryEngine(
			$this->store,
			$this->newConditionBuilder(),
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
			$this->newConditionBuilder(),
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
