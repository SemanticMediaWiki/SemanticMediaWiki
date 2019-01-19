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
			$instance->newQueryResultDependencyListResolver()
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

	public function testCanConstructQueryReferenceBacklinks() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryReferenceBacklinks',
			$instance->newQueryReferenceBacklinks( $store )
		);
	}

	public function testCanConstructDependencyLinksValidator() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\DependencyLinksValidator',
			$instance->newDependencyLinksValidator()
		);
	}

	public function testCanConstructQueryLinksTableDisposer() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryLinksTableDisposer',
			$instance->newQueryLinksTableDisposer( $store )
		);
	}

}
