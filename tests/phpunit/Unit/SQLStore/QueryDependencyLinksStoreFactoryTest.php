<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\QueryDependencyLinksStoreFactory;

/**
 * @covers \SMW\SQLStore\QueryDependencyLinksStoreFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryDependencyLinksStoreFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependencyLinksStoreFactory',
			new QueryDependencyLinksStoreFactory()
		);
	}

	public function testCanConstructQueryResultDependencyListResolver() {

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver',
			$instance->newQueryResultDependencyListResolver( '' )
		);
	}

	public function testCanConstructQueryDependencyLinksStore() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore',
			$instance->newQueryDependencyLinksStore( $store )
		);
	}

	public function testCanConstructEntityIdListRelevanceDetectionFilter() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$compositePropertyTableDiffIterator = $this->getMockBuilder( '\SMW\SQLStore\CompositePropertyTableDiffIterator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\EntityIdListRelevanceDetectionFilter',
			$instance->newEntityIdListRelevanceDetectionFilter( $store, $compositePropertyTableDiffIterator )
		);
	}

}
