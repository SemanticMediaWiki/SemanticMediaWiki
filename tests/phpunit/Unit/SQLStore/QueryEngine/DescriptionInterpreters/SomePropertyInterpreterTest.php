<?php

namespace SMW\Tests\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DataItemFactory;
use SMW\Query\DescriptionFactory;
use SMW\SQLStore\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter;
use SMW\SQLStore\QueryEngineFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class SomePropertyInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $connection;
	private $conditionBuilder;
	private $valueMatchConditionBuilder;
	private $descriptionFactory;
	private $dataItemFactory;

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

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->valueMatchConditionBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->descriptionFactory = new DescriptionFactory();
		$this->dataItemFactory = new DataItemFactory();

		$testEnvironment = new TestEnvironment();
		$this->querySegmentValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SomePropertyInterpreter::class,
			new SomePropertyInterpreter( $this->store, $this->conditionBuilder, $this->valueMatchConditionBuilder )
		);
	}

	public function testinterpretDescriptionForUnknownTablePropertyId() {

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( '' ) );

		$description = $this->descriptionFactory->newSomeProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' ),
			$this->descriptionFactory->newThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 0;

		$queryEngineFactory = new QueryEngineFactory( $this->store );

		$instance = new SomePropertyInterpreter(
			$this->store,
			$queryEngineFactory->newConditionBuilder(),
			$this->valueMatchConditionBuilder
		);

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

	public function testinterpretDescriptionForNonIdSubject() {

		$proptable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'usesIdSubject' ] )
			->getMock();

		$proptable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( false ) );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'Foo' => $proptable ] ) );

		$description = $this->descriptionFactory->newSomeProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' ),
			$this->descriptionFactory->newThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 0;

		$queryEngineFactory = new QueryEngineFactory( $this->store );

		$instance = new SomePropertyInterpreter(
			$this->store,
			$queryEngineFactory->newConditionBuilder(),
			$this->valueMatchConditionBuilder
		);

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

	public function testinterpretDescriptionForNonWikiPageTypeInverseProperty() {

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->once() )
			->method( 'isInverse' )
			->will( $this->returnValue( true ) );

		$property->expects( $this->once() )
			->method( 'findPropertyTypeID' )
			->will( $this->returnValue( '_txt' ) );

		$proptable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'usesIdSubject' ] )
			->getMock();

		$proptable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'Foo' => $proptable ] ) );

		$description = $this->descriptionFactory->newSomeProperty(
			$property,
			$this->descriptionFactory->newThingDescription()
		);
		$expected = new \stdClass;
		$expected->type = 0;

		$queryEngineFactory = new QueryEngineFactory( $this->store );

		$instance = new SomePropertyInterpreter(
			$this->store,
			$queryEngineFactory->newConditionBuilder(),
			$this->valueMatchConditionBuilder
		);

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testinterpretDescription( $description, $isFixedPropertyTable, $indexField, $sortKeys, $expected ) {

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getIndexField' )
			->will( $this->returnValue( $indexField ) );

		$dataItemHandler->expects( $this->any() )
			->method( 'getTableFields' )
			->will( $this->returnValue( [ 'one', 'two' ] ) );

		$dataItemHandler->expects( $this->any() )
			->method( 'getWhereConds' )
			->will( $this->returnValue( [ $indexField => 'fixedFooWhereCond' ] ) );

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getSMWPropertyID', 'getSMWPageID' ] )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 91 ) );

		$this->connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$proptable = $this->getMockBuilder( '\SMWSQLStore3Table' )
			->disableOriginalConstructor()
			->getMock();

		$proptable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$proptable->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'FooPropTable' ) );

		$proptable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( $isFixedPropertyTable ) );

		$this->store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ 'Foo' => $proptable ] ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$this->store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$queryEngineFactory = new QueryEngineFactory( $this->store );

		$conditionBuilder = $queryEngineFactory->newConditionBuilder();
		$conditionBuilder->setSortKeys( $sortKeys );

		$instance = new SomePropertyInterpreter(
			$this->store,
			$conditionBuilder,
			$this->valueMatchConditionBuilder
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

		#0 Blob + wildcard
		$isFixedPropertyTable = false;
		$indexField = '';
		$sortKeys = [];
		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = $descriptionFactory->newSomeProperty(
			$property,
			$descriptionFactory->newThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = [ 1 => "t0.p_id" ];
		$expected->sortfields = [];

		$provider[] = [
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		];

		#1 WikiPage + SMW_CMP_EQ
		$isFixedPropertyTable = false;
		$indexField = 'wikipageIndex';
		$sortKeys = [];
		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = $descriptionFactory->newSomeProperty(
			$property,
			$descriptionFactory->newValueDescription( $dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = [ 1 => "t0.p_id", 2 => "t0.wikipageIndex" ];
		$expected->queryNumber = 0;
		$expected->where = '';
		$expected->sortfields = [];

		$provider[] = [
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		];

		#2 WikiPage + SMW_CMP_EQ + sort
		$isFixedPropertyTable = false;
		$indexField = 'wikipageIndex';
		$sortKeys = [ 'Foo' => 'DESC' ];
		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = $descriptionFactory->newSomeProperty(
			$property,
			$descriptionFactory->newValueDescription( $dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = [ 1 => "t0.p_id", 2 => "t0.wikipageIndex" ];
		$expected->queryNumber = 0;
		$expected->where = '';
		$expected->sortfields = [ 'Foo' => 'idst0.smw_sort' ];
		$expected->from = ' INNER JOIN  AS idst0 ON idst0.smw_id=t0.wikipageIndex';

		$provider[] = [
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		];

		#3 Blob + SMW_CMP_EQ
		$isFixedPropertyTable = false;
		$indexField = 'blobIndex';
		$sortKeys = [];
		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = $descriptionFactory->newSomeProperty(
			$property,
			$descriptionFactory->newValueDescription( $dataItemFactory->newDIBlob( 'Bar' ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = [ 1 => "t0.p_id" ];
		$expected->queryNumber = 0;
		$expected->where = '(t0.blobIndex=fixedFooWhereCond)';
		$expected->sortfields = [];

		$provider[] = [
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		];

		#4 Blob + SMW_CMP_EQ + sort
		$isFixedPropertyTable = false;
		$indexField = 'blobIndex';
		$sortKeys = [ 'Foo' => 'ASC' ];
		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = $descriptionFactory->newSomeProperty(
			$property,
			$descriptionFactory->newValueDescription( $dataItemFactory->newDIBlob( 'Bar' ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = [ 1 => "t0.p_id" ];
		$expected->queryNumber = 0;
		$expected->where = '(t0.blobIndex=fixedFooWhereCond)';
		$expected->sortfields = [ 'Foo' => 't0.blobIndex' ];
		$expected->from = '';

		$provider[] = [
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		];

		#5 Check SemanticMaps compatibility mode (invokes `getSQLCondition`)
		$isFixedPropertyTable = false;
		$indexField = 'blobIndex';
		$sortKeys = [];
		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSQLCondition', 'getDataItem' ] )
			->getMock();

		$valueDescription->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItemFactory->newDIBlob( '13,56' ) ) );

		$valueDescription->expects( $this->once() )
			->method( 'getSQLCondition' )
			->will( $this->returnValue( 'foo AND bar' ) );

		$description = $descriptionFactory->newSomeProperty(
			$property,
			$valueDescription
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = [ 1 => "t0.p_id" ];
		$expected->queryNumber = 0;
		$expected->where = '(foo AND bar)';
		$expected->sortfields = [];
		$expected->from = '';

		$provider[] = [
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		];

		#6, see 556
		$isFixedPropertyTable = false;
		$indexField = '';
		$sortKeys = [];
		$property = $dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = $descriptionFactory->newSomeProperty(
			$property,
			$descriptionFactory->newDisjunction( [
				$descriptionFactory->newValueDescription( $dataItemFactory->newDIBlob( 'Bar' ) ),
				$descriptionFactory->newValueDescription( $dataItemFactory->newDIBlob( 'Baz' ) )
			] )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = [ 1 => "t0.p_id" ];
		$expected->queryNumber = 0;
		$expected->where = '((t0.=fixedFooWhereCond) OR (t0.=fixedFooWhereCond))';
		$expected->sortfields = [];
		$expected->from = '';

		$provider[] = [
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		];

		return $provider;
	}

}
