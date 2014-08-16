<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\Tests\Util\Validator\QueryContainerValidator;

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

	private $queryContainerValidator;

	protected function setUp() {
		parent::setUp();

		$this->queryContainerValidator = new QueryContainerValidator();
	}

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
		$instance->buildQueryContainer( $description );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->where = "t0.smw_namespace=";

		$this->assertEquals( 0, $instance->getLastContainerId() );
		$this->assertEmpty( $instance->getErrors() );

		$this->queryContainerValidator->assertThatContainerContains(
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
		$instance->buildQueryContainer( $description );

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

		$this->queryContainerValidator->assertThatContainerContains(
			array( $expectedDisjunction, $expectedHelpNs, $expectedMainNs ),
			$instance->getQueryContainer()
		);
	}

}
