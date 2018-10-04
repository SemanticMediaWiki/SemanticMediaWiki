<?php

namespace SMW\Tests\Serializers;

use SMW\DataItemFactory;
use SMW\Serializers\QueryResultSerializer;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\CoreMockObjectRepository;
use SMW\Tests\Utils\Mock\MediaWikiMockObjectRepository;
use SMW\Tests\Utils\Mock\MockObjectBuilder;
use SMWDataItem as DataItem;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Serializers\QueryResultSerializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class QueryResultSerializerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstructor() {

		$this->assertInstanceOf(
			'\SMW\Serializers\QueryResultSerializer',
			new QueryResultSerializer()
		);
	}

	public function testSerializeOutOfBoundsException() {

		$this->setExpectedException( 'OutOfBoundsException' );

		$instance = new QueryResultSerializer();
		$instance->serialize( 'Foo' );
	}

	/**
	 * @dataProvider numberDataProvider
	 */
	public function testQueryResultSerializerOnMock( $setup, $expected ) {

		$instance = new QueryResultSerializer();
		$results = $instance->serialize( $setup['queryResult'] );

		$this->assertInternalType(
			'array',
			$results
		);

		$this->assertEquals(
			$expected['printrequests'],
			$results['printrequests']
		);
	}

	public function testQueryResultSerializerForRecordType() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIProperty( 'Foobar' ) ] ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN ) ] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'BarList1;BarList2' ) ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$property = \SMW\DIProperty::newFromUserLabel( 'Foo' );
		$property->setPropertyTypeId( '_rec' );

		$printRequestFactory = new \SMW\Query\PrintRequestFactory();

		$serialization = QueryResultSerializer::getSerialization(
			\SMW\DIWikiPage::newFromText( 'ABC' ),
			$printRequestFactory->newFromProperty( $property )
		);

		$expected = [
			'BarList1' => [
				'label'  => 'BarList1',
				'typeid' => '_wpg',
				'item'   => [],
				'key'    => 'BarList1'
			],
			'BarList2' => [
				'label'  => 'BarList2',
				'typeid' => '_wpg',
				'item'   => [],
				'key'    => 'BarList2'
			]
		];

		$this->assertEquals(
			$expected,
			$serialization
		);
	}

	public function testSerializeFormatForTimeValue() {

		$property = \SMW\DIProperty::newFromUserLabel( 'Foo' );
		$property->setPropertyTypeId( '_dat' );

		$printRequestFactory = new \SMW\Query\PrintRequestFactory();

		$serialization = QueryResultSerializer::getSerialization(
			\SMWDITime::doUnserialize( '2/1393/1/1' ),
			$printRequestFactory->newFromProperty( $property )
		);

		$expected = [
			'timestamp' => '-18208281600',
			'raw' => '2/1393/1/1'
		];

		$this->assertEquals(
			$expected,
			$serialization
		);
	}

	public function testQueryResultSerializerOnMockOnDIWikiPageNonTitle() {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$dataItem = $this->newMockBuilder()->newObject( 'DataItem', [
			'getDIType' => DataItem::TYPE_WIKIPAGE,
			'getTitle'  => null
		] );

		$queryResult = $this->newMockBuilder()->newObject( 'QueryResult', [
			'getPrintRequests'  => [],
			'getResults'        => [ $dataItem ],
			'getQuery'          => $query
		] );

		$instance = new QueryResultSerializer();
		$results = $instance->serialize( $queryResult );

		$this->assertInternalType( 'array', $results );
		$this->assertEmpty( $results['printrequests'] );
		$this->assertEmpty( $results['results'] );
	}

	/**
	 * @return array
	 */
	public function numberDataProvider() {

		$provider = [];

		$setup = [
			[ 'printRequest' => 'Foo-1', 'typeId' => '_num', 'number' => 10, 'dataValue' => 'Quuey' ],
			[ 'printRequest' => 'Foo-2', 'typeId' => '_num', 'number' => 20, 'dataValue' => 'Vey' ],
		];

		$provider[] = [
			[
				'queryResult' => $this->buildMockQueryResult( $setup )
				],
			[
				'printrequests' => [
					[ 'label' => 'Foo-1', 'typeid' => '_num', 'mode' => 2, 'format' => false, 'key' => '', 'redi' => '' ],
					[ 'label' => 'Foo-2', 'typeid' => '_num', 'mode' => 2, 'format' => false, 'key' => '', 'redi' => '' ]
				],
			]
		];

		return $provider;
	}

	/**
	 * @return QueryResult
	 */
	private function buildMockQueryResult( $setup ) {

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$printRequests = [];
		$resultArray   = [];
		$getResults    = [];

		foreach ( $setup as $value ) {

			$printRequest = $this->newMockBuilder()->newObject( 'PrintRequest', [
				'getText'   => $value['printRequest'],
				'getLabel'  => $value['printRequest'],
				'getTypeID' => $value['typeId'],
				'getOutputFormat' => false
			] );

			$printRequests[] = $printRequest;
			$getResults[] = \SMW\DIWikiPage::newFromTitle( new \Title( NS_MAIN, $value['printRequest'] ) );

			$dataItem = $this->newMockBuilder()->newObject( 'DataItem', [
				'getDIType' => DataItem::TYPE_NUMBER,
				'getNumber' => $value['number']
			] );

			$dataValue = $this->newMockBuilder()->newObject( 'DataValue', [
				'DataValueType'    => 'SMWNumberValue',
				'getTypeID'        => '_num',
				'getShortWikiText' => $value['dataValue'],
				'getDataItem'      => $dataItem
			] );

			$resultArray[] = $this->newMockBuilder()->newObject( 'ResultArray', [
				'getText'          => $value['printRequest'],
				'getPrintRequest'  => $printRequest,
				'getNextDataValue' => $dataValue,
				'getNextDataItem'  => $dataItem,
				'getContent'       => $dataItem
			] );

		}

		$queryResult = $this->newMockBuilder()->newObject( 'QueryResult', [
			'getPrintRequests'  => $printRequests,
			'getNext'           => $resultArray,
			'getResults'        => $getResults,
			'getQuery'          => $query,
			'getStore'          => $this->newMockBuilder()->newObject( 'Store' ),
			'getLink'           => new \SMWInfolink( true, 'Lala', 'Lula' ),
			'hasFurtherResults' => true
		] );

		return $queryResult;
	}

	private function newMockBuilder() {

		$builder = new MockObjectBuilder();
		$builder->registerRepository( new CoreMockObjectRepository() );
		$builder->registerRepository( new MediaWikiMockObjectRepository() );

		return $builder;
	}

}
