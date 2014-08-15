<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\QueryContainer;
use SMW\SQLStore\QueryEngine\QueryBuilder;

use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryBuilder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QueryBuilder',
			new QueryBuilder( $store )
		);
	}

	public function testNamespaceDescription() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $connection ) );

		$description = new NamespaceDescription( NS_HELP );

		$instance = new QueryBuilder( $store );
		$instance->setToInitialBuildState()->buildQueryContainer( $description );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->where = "t0.smw_namespace=";

		$this->assertEquals( 0, $instance->getLastContainerId() );
		$this->assertEmpty( $instance->getErrors() );

		$this->assertOrderedQueryContainer(
			$expected,
			$instance->getQueryContainer()
		);
	}

	public function testDisjunctiveNamespaceDescription() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $connection ) );

		$description = new Disjunction();
		$description->addDescription( new NamespaceDescription( NS_HELP ) );
		$description->addDescription( new NamespaceDescription( NS_MAIN ) );

		$instance = new QueryBuilder( $store );
		$instance->setToInitialBuildState()->buildQueryContainer( $description );

		$expectedDisjunction = new \stdClass;
		$expectedDisjunction->type = 3;

		$expectedHelpNs = new \stdClass;
		$expectedHelpNs->type = 1;
		$expectedHelpNs->where = "t1.smw_namespace=";

		$expectedMainNs = new \stdClass;
		$expectedMainNs->type = 1;
		$expectedMainNs->where = "t2.smw_namespace=";

		$this->assertEquals( 0, $instance->getLastContainerId() );
		$this->assertEmpty( $instance->getErrors() );

		$this->assertOrderedQueryContainer(
			array( $expectedDisjunction, $expectedHelpNs, $expectedMainNs ),
			$instance->getQueryContainer()
		);
	}

	// Move to QueryContainerValidator
	private function assertOrderedQueryContainer( $expected, array $queryContainer ) {

		$expected = is_array( $expected ) ? $expected : array( $expected );

		$this->assertEquals( count( $expected ), count( $queryContainer ) );

		foreach ( $queryContainer as $key => $container ) {
			$this->assertInstanceOf(
				'\SMW\SQLStore\QueryEngine\QueryContainer',
				$container
			);

			$this->assertSingleQueryContainer( $expected[ $key ], $container );
		}
	}

	private function assertSingleQueryContainer( $expected, $queryContainer ) {

		$typeCondition = true;
		$whereCondition = true;

		if ( isset( $expected->type ) ) {
			$typeCondition = $expected->type == $queryContainer->type;
		}

		if ( isset( $expected->where ) ) {
			$whereCondition = $expected->where == $queryContainer->where;
		}

		$this->assertTrue( $typeCondition );
		$this->assertTrue( $whereCondition );
	}

}
