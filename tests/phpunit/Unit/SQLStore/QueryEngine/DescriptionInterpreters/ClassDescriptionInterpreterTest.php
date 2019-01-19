<?php

namespace SMW\Tests\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DataItemFactory;
use SMW\Query\DescriptionFactory;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ClassDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentValidator;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter',
			new ClassDescriptionInterpreter( $this->store, $conditionBuilder )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testCompileDescription( $description, $pageId, $expected ) {

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getSMWPageID' ] )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( $pageId ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$queryEngineFactory = new QueryEngineFactory( $this->store );

		$instance = new ClassDescriptionInterpreter(
			$this->store,
			$queryEngineFactory->newConditionBuilder()
		);

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

	public function descriptionProvider() {

		$descriptionFactory = new DescriptionFactory();
		$dataItemFactory = new DataItemFactory();

		#0
		$pageId = 42;

		$description = $descriptionFactory->newClassDescription(
			$dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->components = [ 1 => "t0.o_id" ];
		$expected->joinfield = "t0.s_id";

		$provider[] = [
			$description,
			$pageId,
			$expected
		];

		#1 Empty
		$pageId = 0;

		$description = $descriptionFactory->newClassDescription(
			$dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY )
		);

		$expected = new \stdClass;
		$expected->type = 2;
		$expected->components = [];
		$expected->joinfield = "";

		$provider[] = [
			$description,
			$pageId,
			$expected
		];

		return $provider;
	}

}
