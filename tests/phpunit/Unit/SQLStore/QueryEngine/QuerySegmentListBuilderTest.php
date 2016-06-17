<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\DIWikiPage;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\Tests\TestEnvironment;

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
	private $descriptionInterpreterFactory;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->descriptionInterpreterFactory = new DescriptionInterpreterFactory();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$descriptionInterpreterFactory = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder',
			new QuerySegmentListBuilder( $this->store, $descriptionInterpreterFactory )
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

		$instance = new QuerySegmentListBuilder(
			$store,
			$this->descriptionInterpreterFactory
		);

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

		$instance = new QuerySegmentListBuilder(
			$store,
			$this->descriptionInterpreterFactory
		);

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

		$instance = new QuerySegmentListBuilder(
			$store,
			$this->descriptionInterpreterFactory
		);

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

		$instance = new QuerySegmentListBuilder(
			$this->store,
			$this->descriptionInterpreterFactory
		);

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->findQuerySegment( null );
	}

	public function testGivenUnknownId_getQuerySegmentThrowsException() {

		$instance = new QuerySegmentListBuilder(
			$this->store,
			$this->descriptionInterpreterFactory
		);

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->findQuerySegment( 1 );
	}

	public function testGivenKnownId_getQuerySegmentReturnsCorrectPart() {

		$instance = new QuerySegmentListBuilder(
			$this->store,
			$this->descriptionInterpreterFactory
		);

		$querySegment = new QuerySegment();

	//	$querySegment->segmentNumber = 1;
		$instance->addQuerySegment( $querySegment );

		$this->assertSame(
			$querySegment,
			$instance->findQuerySegment( $querySegment->queryNumber )
		);
	}

	public function testWhenNoQuerySegments_getQuerySegmentListReturnsEmptyArray() {

		$instance = new QuerySegmentListBuilder(
			$this->store,
			$this->descriptionInterpreterFactory
		);

		$this->assertSame(
			array(),
			$instance->getQuerySegmentList()
		);
	}

	public function testWhenSomeQuerySegments_getQuerySegmentListReturnsThemAll() {

		$instance = new QuerySegmentListBuilder(
			$this->store,
			$this->descriptionInterpreterFactory
		);

		$firstQuerySegment = new QuerySegment();
		$instance->addQuerySegment( $firstQuerySegment );

		$secondQuerySegment = new QuerySegment();
		$instance->addQuerySegment( $secondQuerySegment );

		$expected = array(
			0 => $firstQuerySegment,
			1 => $secondQuerySegment
		);

		$this->assertSame(
			$expected,
			$instance->getQuerySegmentList()
		);
	}

}
