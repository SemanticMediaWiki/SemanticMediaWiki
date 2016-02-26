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

	public function canConstructQueryResultDependencyListResolver() {

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver',
			$instance->newQueryResultDependencyListResolver( '' )
		);
	}

	public function canConstructQueryDependencyLinksStore() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryDependency\QueryDependencyLinksStore',
			$instance->newQueryDependencyLinksStore( $store )
		);
	}

}
