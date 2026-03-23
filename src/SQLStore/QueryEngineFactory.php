<?php

namespace SMW\SQLStore;

use SMW\DataItems\Property;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SQLStore\QueryEngine\EngineOptions;
use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\SQLStore\QueryEngine\OrderCondition;
use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use SMW\Utils\CircularReferenceGuard;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class QueryEngineFactory {

	/**
	 * @since 2.4
	 */
	public function __construct( private readonly SQLStore $store ) {
	}

	/**
	 * @since 2.4
	 *
	 * @return ConditionBuilder
	 */
	public function newConditionBuilder(): ConditionBuilder {
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
	public function newQuerySegmentListProcessor(): QuerySegmentListProcessor {
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
					'table' => $this->store->findPropertyTableID( new Property( '_SUBP' ) ),
					'depth' => $settings->get( 'smwgQSubpropertyDepth' )
				],
				'class' => [
					'table' => $this->store->findPropertyTableID( new Property( '_SUBC' ) ),
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
	public function newQueryEngine(): QueryEngine {
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
	public function newConceptQuerySegmentBuilder(): ConceptQuerySegmentBuilder {
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

	private function newTemporaryTableBuilder(): TemporaryTableBuilder {
		$temporaryTableBuilder = new TemporaryTableBuilder(
			$this->store->getConnection( 'mw.db.queryengine' )
		);

		$temporaryTableBuilder->setAutoCommitFlag(
			ApplicationFactory::getInstance()->getSettings()->get( 'smwgQTemporaryTablesAutoCommitMode' )
		);

		return $temporaryTableBuilder;
	}

}
