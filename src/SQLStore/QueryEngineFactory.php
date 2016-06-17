<?php

namespace SMW\SQLStore;

use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SQLStore\QueryEngine\EngineOptions;
use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\SQLStore\QueryEngine\QueryEngine;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\SQLStore\QueryEngine\QuerySegmentListProcessor;

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
	 * @var Settings
	 */
	private $settings;

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->settings = ApplicationFactory::getInstance()->getSettings();
	}

	/**
	 * @since 2.4
	 *
	 * @return QuerySegmentListBuilder
	 */
	public function newQuerySegmentListBuilder() {
		return new QuerySegmentListBuilder(
			$this->store,
			new DescriptionInterpreterFactory()
		);
	}

	/**
	 * @since 2.4
	 *
	 * @return QuerySegmentListProcessor
	 */
	public function newQuerySegmentListProcessor() {

		$connection = $this->store->getConnection( 'mw.db.queryengine' );

		$hierarchyTempTableBuilder = new HierarchyTempTableBuilder(
			$connection,
			$this->newTemporaryIdTableCreator()
		);

		$hierarchyTempTableBuilder->setPropertyHierarchyTableDefinition(
			$this->store->findPropertyTableID( new DIProperty( '_SUBP' ) ),
			$this->settings->get( 'smwgQSubpropertyDepth' )
		);

		$hierarchyTempTableBuilder->setClassHierarchyTableDefinition(
			$this->store->findPropertyTableID( new DIProperty( '_SUBC' ) ),
			$this->settings->get( 'smwgQSubcategoryDepth' )
		);

		$querySegmentListProcessor = new QuerySegmentListProcessor(
			$connection,
			$this->newTemporaryIdTableCreator(),
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
		return new QueryEngine(
			$this->store,
			$this->newQuerySegmentListBuilder(),
			$this->newQuerySegmentListProcessor(),
			new EngineOptions()
		);
	}

	private function newTemporaryIdTableCreator() {
		return new TemporaryIdTableCreator( $GLOBALS['wgDBtype'] );
	}

}
