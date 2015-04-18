<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\Tests\Utils\UtilityFactory;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ClassDescription;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\QueryEngine\QueryBuilder
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QueryBuilderTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentValidator;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->querySegmentValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QueryBuilder',
			new QueryBuilder( $this->store )
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
		$instance->buildQuerySegmentFor( $description );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->where = "t0.smw_namespace=";

		$this->assertEquals( 0, $instance->getLastQuerySegmentId() );
		$this->assertEmpty( $instance->getErrors() );

		$this->querySegmentValidator->assertThatContainerContains(
			$expected,
			$instance->getQuerySegments()
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
		$instance->buildQuerySegmentFor( $description );

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
			$instance->getLastQuerySegmentId()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);

		$expected = array(
			$expectedDisjunction,
			$expectedHelpNs,
			$expectedMainNs
		);

		$this->querySegmentValidator->assertThatContainerContains(
			$expected,
			$instance->getQuerySegments()
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
		$instance->buildQuerySegmentFor( $description );

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
			$instance->getLastQuerySegmentId()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);

		$expected = array(
			$expectedClass,
			$expectedHierarchy
		);

		$this->querySegmentValidator->assertThatContainerContains(
			$expected,
			$instance->getQuerySegments()
		);
	}

	public function testGivenNonInteger_getQuerySegmentThrowsException() {

		$instance = new QueryBuilder( $this->store );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->findQuerySegment( null );
	}

	public function testGivenUnknownId_getQuerySegmentThrowsException() {

		$instance = new QueryBuilder( $this->store );

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->findQuerySegment( 1 );
	}

	public function testGivenKnownId_getQuerySegmentReturnsCorrectPart() {

		$instance = new QueryBuilder( $this->store );
		$querySegment = new QuerySegment();

		$instance->addQuerySegmentForId( 1, $querySegment );

		$this->assertSame(
			$querySegment,
			$instance->findQuerySegment( 1 )
		);
	}

	public function testWhenNoQuerySegments_getQuerySegmentsReturnsEmptyArray() {

		$instance = new QueryBuilder( $this->store );

		$this->assertSame(
			array(),
			$instance->getQuerySegments()
		);
	}

	public function testWhenSomeQuerySegments_getQuerySegmentsReturnsThemAll() {

		$instance = new QueryBuilder( $this->store );

		$firstQuerySegment = new QuerySegment();
		$secondQuerySegment = new QuerySegment();
		$instance->addQuerySegmentForId( 42, $firstQuerySegment );
		$instance->addQuerySegmentForId( 23, $secondQuerySegment );

		$this->assertSame(
			array( 42 => $firstQuerySegment, 23 => $secondQuerySegment ),
			$instance->getQuerySegments()
		);
	}

}
