<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SQLStore\QueryEngine\SqlQueryPart;
use SMW\SQLStore\QueryEngine\QueryBuilder;

use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ClassDescription;

use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryBuilder
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QueryBuilderTest extends \PHPUnit_Framework_TestCase {

	private $queryContainerValidator;

	protected function setUp() {
		parent::setUp();

		$this->queryContainerValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSqlQueryPartValidator();
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
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$description = new NamespaceDescription( NS_HELP );

		$instance = new QueryBuilder( $store );
		$instance->buildSqlQueryPartFor( $description );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->where = "t0.smw_namespace=";

		$this->assertEquals( 0, $instance->getLastSqlQueryPartId() );
		$this->assertEmpty( $instance->getErrors() );

		$this->queryContainerValidator->assertThatContainerContains(
			$expected,
			$instance->getSqlQueryPart()
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
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$description = new Disjunction();
		$description->addDescription( new NamespaceDescription( NS_HELP ) );
		$description->addDescription( new NamespaceDescription( NS_MAIN ) );

		$instance = new QueryBuilder( $store );
		$instance->buildSqlQueryPartFor( $description );

		$expectedDisjunction = new \stdClass;
		$expectedDisjunction->type = 3;

		$expectedHelpNs = new \stdClass;
		$expectedHelpNs->type = 1;
		$expectedHelpNs->where = "t1.smw_namespace=";

		$expectedMainNs = new \stdClass;
		$expectedMainNs->type = 1;
		$expectedMainNs->where = "t2.smw_namespace=";

		$this->assertEquals(
			0,
			$instance->getLastSqlQueryPartId()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);

		$expected = array(
			$expectedDisjunction,
			$expectedHelpNs,
			$expectedMainNs
		);

		$this->queryContainerValidator->assertThatContainerContains(
			$expected,
			$instance->getSqlQueryPart()
		);
	}

	public function testClassDescription() {

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPageID' ) )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->once() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$description = new ClassDescription( new DIWikiPage( 'Foo', NS_CATEGORY ) );

		$instance = new QueryBuilder( $store );
		$instance->buildSqlQueryPartFor( $description );

		$expectedClass = new \stdClass;
		$expectedClass->type = 1;
		$expectedClass->alias = "t0";
		$expectedClass->queryNumber = 0;

		$expectedHierarchy = new \stdClass;
		$expectedHierarchy->type = 5;
		$expectedHierarchy->joinfield = array( 0 => 42 );
		$expectedHierarchy->alias = "t1";
		$expectedHierarchy->queryNumber = 1;

		$this->assertEquals(
			0,
			$instance->getLastSqlQueryPartId()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);

		$expected = array(
			$expectedClass,
			$expectedHierarchy
		);

		$this->queryContainerValidator->assertThatContainerContains(
			$expected,
			$instance->getSqlQueryPart()
		);
	}

}
