<?php

namespace SMW\Tests\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DataItemFactory;
use SMW\Query\DescriptionFactory;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ValueDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $conditionBuilder;
	private $descriptionFactory;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->descriptionFactory = new DescriptionFactory();
		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ValueDescriptionInterpreter::Class,
			new ValueDescriptionInterpreter( $this->store, $this->conditionBuilder )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testInterpretDescription( $description, $expected ) {

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getSMWPageID' ] )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 42 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$queryEngineFactory = new QueryEngineFactory( $this->store );

		$instance = new ValueDescriptionInterpreter(
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

		#0 SMW_CMP_EQ
		$description = $descriptionFactory->newValueDescription(
			$dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_EQ
		);

		$expected = new \stdClass;
		$expected->type = 2;
		$expected->alias = "t0";
		$expected->joinfield = [ 42 ];

		$provider[] = [
			$description,
			$expected
		];

		#1 SMW_CMP_LEQ
		$description = $descriptionFactory->newValueDescription(
			$dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_LEQ
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->alias = "t0";
		$expected->joinfield = "t0.smw_id";
		$expected->where = "t0.smw_sortkey<=Foo";

		$provider[] = [
			$description,
			$expected
		];

		#2 SMW_CMP_LIKE
		$description = $descriptionFactory->newValueDescription(
			$dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ), null, SMW_CMP_LIKE
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->alias = "t0";
		$expected->joinfield = "t0.smw_id";
		$expected->where = "t0.smw_sortkey LIKE Foo";

		$provider[] = [
			$description,
			$expected
		];

		#3 not a DIWikiPage
		$description = $descriptionFactory->newValueDescription(
			$dataItemFactory->newDIBLob( 'Foo' )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinfield = "";
		$expected->where = "";

		$provider[] = [
			$description,
			$expected
		];

		return $provider;
	}

}
