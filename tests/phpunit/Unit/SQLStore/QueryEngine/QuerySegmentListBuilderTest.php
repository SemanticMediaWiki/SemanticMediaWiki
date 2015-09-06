<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\Tests\Utils\UtilityFactory;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\ClassDescription;
use SMW\DIWikiPage;

/**
 * @covers \SMW\SQLStore\QueryEngine\QuerySegmentListBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QuerySegmentListBuilderTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder',
			new QuerySegmentListBuilder( $this->store )
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

		$instance = new QuerySegmentListBuilder( $store );
		$instance->buildQuerySegmentFor( $description );

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->where = "t0.smw_namespace=";

		$this->assertEquals( 0, $instance->getLastQuerySegmentId() );
		$this->assertEmpty( $instance->getErrors() );

		$this->querySegmentValidator->assertThatContainerContains(
			$expected,
			$instance->getQuerySegmentList()
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

		$instance = new QuerySegmentListBuilder( $store );
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
			$instance->getQuerySegmentList()
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

		$instance = new QuerySegmentListBuilder( $store );
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
			$instance->getQuerySegmentList()
		);
	}

	public function testGivenNonInteger_getQuerySegmentThrowsException() {

		$instance = new QuerySegmentListBuilder( $this->store );

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->findQuerySegment( null );
	}

	public function testGivenUnknownId_getQuerySegmentThrowsException() {

		$instance = new QuerySegmentListBuilder( $this->store );

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->findQuerySegment( 1 );
	}

	public function testGivenKnownId_getQuerySegmentReturnsCorrectPart() {

		$instance = new QuerySegmentListBuilder( $this->store );
		$querySegment = new QuerySegment();

		$querySegment->segmentNumber = 1;
		$instance->addQuerySegment($querySegment );

		$this->assertSame(
			$querySegment,
			$instance->findQuerySegment( 1 )
		);
	}

	public function testWhenNoQuerySegments_getQuerySegmentListReturnsEmptyArray() {

		$instance = new QuerySegmentListBuilder( $this->store );

		$this->assertSame(
			array(),
			$instance->getQuerySegmentList()
		);
	}

	public function testWhenSomeQuerySegments_getQuerySegmentListReturnsThemAll() {

		$instance = new QuerySegmentListBuilder( $this->store );

		$firstQuerySegment = new QuerySegment();
		$firstQuerySegment->segmentNumber = 42;

		$instance->addQuerySegment( $firstQuerySegment );

		$secondQuerySegment = new QuerySegment();
		$secondQuerySegment->segmentNumber = 23;

		$instance->addQuerySegment( $secondQuerySegment );

		$expected = array(
			42 => $firstQuerySegment,
			23 => $secondQuerySegment
		);

		$this->assertSame(
			$expected,
			$instance->getQuerySegmentList()
		);
	}

}
