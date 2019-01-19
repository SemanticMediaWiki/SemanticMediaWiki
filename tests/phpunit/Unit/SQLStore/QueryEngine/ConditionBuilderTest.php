<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\DIWikiPage;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\QueryEngine\ConditionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ConditionBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $connection;
	private $orderCondition;
	private $circularReferenceGuard;
	private $descriptionInterpreterFactory;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->orderCondition = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\OrderCondition' )
			->disableOriginalConstructor()
			->getMock();

		$this->circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->descriptionInterpreterFactory = new DescriptionInterpreterFactory(
			$this->store,
			$this->circularReferenceGuard
		);

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$descriptionInterpreterFactory = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\DescriptionInterpreterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ConditionBuilder::class,
			new ConditionBuilder( $this->store, $this->orderCondition, $descriptionInterpreterFactory, $this->circularReferenceGuard )
		);
	}

	public function testBuildCondition() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$this->orderCondition->expects( $this->once() )
			->method( 'addConditions' );

		$instance = new ConditionBuilder(
			$this->store,
			$this->orderCondition,
			$this->descriptionInterpreterFactory,
			$this->circularReferenceGuard
		);

		$instance->buildCondition( $query );
	}

	public function testNamespaceDescription() {

		$description = new NamespaceDescription( NS_HELP );

		$instance = new ConditionBuilder(
			$this->store,
			$this->orderCondition,
			$this->descriptionInterpreterFactory,
			$this->circularReferenceGuard
		);

		$instance->buildFromDescription( $description );

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

		$description = new Disjunction();
		$description->addDescription( new NamespaceDescription( NS_HELP ) );
		$description->addDescription( new NamespaceDescription( NS_MAIN ) );

		$instance = new ConditionBuilder(
			$this->store,
			$this->orderCondition,
			$this->descriptionInterpreterFactory,
			$this->circularReferenceGuard
		);

		$instance->buildFromDescription( $description );

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

		$expected = [
			$expectedDisjunction,
			$expectedHelpNs,
			$expectedMainNs
		];

		$this->querySegmentValidator->assertThatContainerContains(
			$expected,
			$instance->getQuerySegmentList()
		);
	}

	public function testClassDescription() {

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getSMWPageID' ] )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->once() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$description = new ClassDescription( new DIWikiPage( 'Foo', NS_CATEGORY ) );

		$instance = new ConditionBuilder(
			$this->store,
			$this->orderCondition,
			$this->descriptionInterpreterFactory,
			$this->circularReferenceGuard
		);

		$instance->buildFromDescription( $description );

		$expectedClass = new \stdClass;
		$expectedClass->type = 1;
		$expectedClass->alias = "t0";
		$expectedClass->queryNumber = 0;

		$expectedHierarchy = new \stdClass;
		$expectedHierarchy->type = 5;
		$expectedHierarchy->joinfield = [ 0 => 42 ];
		$expectedHierarchy->alias = "t1";
		$expectedHierarchy->queryNumber = 1;

		$this->assertEquals(
			0,
			$instance->getLastQuerySegmentId()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);

		$expected = [
			$expectedClass,
			$expectedHierarchy
		];

		$this->querySegmentValidator->assertThatContainerContains(
			$expected,
			$instance->getQuerySegmentList()
		);
	}

	public function testGivenNonInteger_getQuerySegmentThrowsException() {

		$instance = new ConditionBuilder(
			$this->store,
			$this->orderCondition,
			$this->descriptionInterpreterFactory,
			$this->circularReferenceGuard
		);

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->findQuerySegment( null );
	}

	public function testGivenUnknownId_getQuerySegmentThrowsException() {

		$instance = new ConditionBuilder(
			$this->store,
			$this->orderCondition,
			$this->descriptionInterpreterFactory,
			$this->circularReferenceGuard
		);

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->findQuerySegment( 1 );
	}

	public function testGivenKnownId_getQuerySegmentReturnsCorrectPart() {

		$instance = new ConditionBuilder(
			$this->store,
			$this->orderCondition,
			$this->descriptionInterpreterFactory,
			$this->circularReferenceGuard
		);

		$querySegment = new QuerySegment();

		$instance->addQuerySegment( $querySegment );

		$this->assertSame(
			$querySegment,
			$instance->findQuerySegment( $querySegment->queryNumber )
		);
	}

	public function testWhenNoQuerySegments_getQuerySegmentListReturnsEmptyArray() {

		$instance = new ConditionBuilder(
			$this->store,
			$this->orderCondition,
			$this->descriptionInterpreterFactory,
			$this->circularReferenceGuard
		);

		$this->assertSame(
			[],
			$instance->getQuerySegmentList()
		);
	}

	public function testWhenSomeQuerySegments_getQuerySegmentListReturnsThemAll() {

		$instance = new ConditionBuilder(
			$this->store,
			$this->orderCondition,
			$this->descriptionInterpreterFactory,
			$this->circularReferenceGuard
		);

		$firstQuerySegment = new QuerySegment();
		$instance->addQuerySegment( $firstQuerySegment );

		$secondQuerySegment = new QuerySegment();
		$instance->addQuerySegment( $secondQuerySegment );

		$expected = [
			0 => $firstQuerySegment,
			1 => $secondQuerySegment
		];

		$this->assertSame(
			$expected,
			$instance->getQuerySegmentList()
		);
	}

}
