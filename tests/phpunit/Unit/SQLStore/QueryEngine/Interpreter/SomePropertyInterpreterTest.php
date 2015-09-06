<?php

namespace SMW\Tests\SQLStore\QueryEngine\Interpreter;

use SMW\Tests\Utils\UtilityFactory;

use SMW\SQLStore\QueryEngine\Interpreter\SomePropertyInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\Disjunction;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\SQLStore\QueryEngine\Interpreter\SomePropertyInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class SomePropertyInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $querySegmentValidator;

	protected function setUp() {
		parent::setUp();

		$this->querySegmentValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQuerySegmentValidator();
	}

	public function testCanConstruct() {

		$querySegmentListBuilder = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\QuerySegmentListBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Interpreter\SomePropertyInterpreter',
			new SomePropertyInterpreter( $querySegmentListBuilder )
		);
	}

	public function testinterpretDescriptionForUnknownTablePropertyId() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( '' ) );

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			new ThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 0;

		$instance = new SomePropertyInterpreter( new QuerySegmentListBuilder( $store ) );

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
			->setMethods( array( 'usesIdSubject' ) )
			->getMock();

		$proptable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( false ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $proptable ) ) );

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			new ThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 0;

		$instance = new SomePropertyInterpreter( new QuerySegmentListBuilder( $store ) );

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
			->setMethods( array( 'usesIdSubject' ) )
			->getMock();

		$proptable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $proptable ) ) );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 0;

		$instance = new SomePropertyInterpreter( new QuerySegmentListBuilder( $store ) );

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

		$dataItemHandler = $this->getMockBuilder( '\SMWDataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getIndexField' )
			->will( $this->returnValue( $indexField ) );

		$dataItemHandler->expects( $this->any() )
			->method( 'getTableFields' )
			->will( $this->returnValue( array( 'one', 'two' ) ) );

		$dataItemHandler->expects( $this->any() )
			->method( 'getWhereConds' )
			->will( $this->returnValue( array( $indexField => 'fixedFooWhereCond' ) ) );

		$objectIds = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getSMWPropertyID', 'getSMWPageID' ) )
			->getMock();

		$objectIds->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 42 ) );

		$objectIds->expects( $this->any() )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 91 ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

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

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'findPropertyTableID' )
			->will( $this->returnValue( 'Foo' ) );

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( 'Foo' => $proptable ) ) );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIds ) );

		$store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$querySegmentListBuilder = new QuerySegmentListBuilder( $store );
		$querySegmentListBuilder->setSortKeys( $sortKeys );

		$instance = new SomePropertyInterpreter( $querySegmentListBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);

		$this->querySegmentValidator->assertThatContainerHasProperties(
			$expected,
			$instance->interpretDescription( $description )
		);
	}

	public function descriptionProvider() {

		#0 Blob + wildcard
		$isFixedPropertyTable = false;
		$indexField = '';
		$sortKeys = array();
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id" );
		$expected->sortfields = array();

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#1 WikiPage + SMW_CMP_EQ
		$isFixedPropertyTable = false;
		$indexField = 'wikipageIndex';
		$sortKeys = array();
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id", 2 => "t0.wikipageIndex" );
		$expected->queryNumber = 0;
		$expected->where = '';
		$expected->sortfields = array();

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#2 WikiPage + SMW_CMP_EQ + sort
		$isFixedPropertyTable = false;
		$indexField = 'wikipageIndex';
		$sortKeys = array( 'Foo' => 'DESC' );
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_wpg' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id", 2 => "t0.wikipageIndex" );
		$expected->queryNumber = 0;
		$expected->where = '';
		$expected->sortfields = array( 'Foo' => 'idst0.smw_sortkey' );
		$expected->from = ' INNER JOIN  AS idst0 ON idst0.smw_id=t0.wikipageIndex';

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#3 Blob + SMW_CMP_EQ
		$isFixedPropertyTable = false;
		$indexField = 'blobIndex';
		$sortKeys = array();
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'Bar' ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id" );
		$expected->queryNumber = 0;
		$expected->where = '(t0.blobIndex=fixedFooWhereCond)';
		$expected->sortfields = array();

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#4 Blob + SMW_CMP_EQ + sort
		$isFixedPropertyTable = false;
		$indexField = 'blobIndex';
		$sortKeys = array( 'Foo' => 'ASC' );
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'Bar' ), null, SMW_CMP_EQ )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id" );
		$expected->queryNumber = 0;
		$expected->where = '(t0.blobIndex=fixedFooWhereCond)';
		$expected->sortfields = array( 'Foo' => 't0.blobIndex' );
		$expected->from = '';

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#5 Check SemanticMaps compatibility mode (invokes `getSQLCondition`)
		$isFixedPropertyTable = false;
		$indexField = 'blobIndex';
		$sortKeys = array();
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$valueDescription = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->setMethods( array( 'getSQLCondition', 'getDataItem' ) )
			->getMock();

		$valueDescription->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $property ) );

		$valueDescription->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( new DIBlob( '13,56' ) ) );

		$valueDescription->expects( $this->once() )
			->method( 'getSQLCondition' )
			->will( $this->returnValue( 'foo AND bar' ) );

		$description = new SomeProperty(
			$property,
			$valueDescription
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id" );
		$expected->queryNumber = 0;
		$expected->where = '(foo AND bar)';
		$expected->sortfields = array();
		$expected->from = '';

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		#6, see 556
		$isFixedPropertyTable = false;
		$indexField = '';
		$sortKeys = array();
		$property = new DIProperty( 'Foo' );
		$property->setPropertyTypeId( '_txt' );

		$description = new SomeProperty(
			$property,
			new Disjunction( array(
				new ValueDescription( new DIBlob( 'Bar' ) ),
				new ValueDescription( new DIBlob( 'Baz' ) )
			) )
		);

		$expected = new \stdClass;
		$expected->type = 1;
		$expected->joinTable = 'FooPropTable';
		$expected->components = array( 1 => "t0.p_id" );
		$expected->queryNumber = 0;
		$expected->where = '((t0.=fixedFooWhereCond) OR (t0.=fixedFooWhereCond))';
		$expected->sortfields = array();
		$expected->from = '';

		$provider[] = array(
			$description,
			$isFixedPropertyTable,
			$indexField,
			$sortKeys,
			$expected
		);

		return $provider;
	}

}
