<?php

namespace SMW\Tests\Query\ResultPrinters;

use ReflectionClass;
use SMW\Query\ResultPrinters\AggregatablePrinter;
use SMWDataItem;
use SMWDINumber;
use SMWQueryResult;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\ResultPrinters\AggregatablePrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class AggregatablePrinterTest extends \PHPUnit_Framework_TestCase {

	private $queryResult;
	private $resultPrinterReflector;
	private $aggregatablePrinter;

	protected function setUp() {
		parent::setUp();

		$this->resultPrinterReflector = TestEnvironment::getUtilityFactory()->newResultPrinterReflector();

		$this->queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$this->aggregatablePrinter = $this->getMockBuilder( AggregatablePrinter::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			AggregatablePrinter::class,
			$this->aggregatablePrinter
		);

		$this->assertInstanceOf(
			'\SMW\ResultPrinter',
			$this->aggregatablePrinter
		);
	}

	/**
	 * @dataProvider errorMessageProvider
	 */
	public function testGetResultTextErrorMessage( $setup, $expected ) {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->setMethods( [ 'getErrors', 'getNext', 'addErrors' ] )
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->will( $this->returnValue( [] ) );

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( [ $expected['message'] ] ) );

		$this->resultPrinterReflector->addParameters(
			$this->aggregatablePrinter,
			$setup['parameters']
		);

		$result = $this->resultPrinterReflector->invoke(
			$this->aggregatablePrinter,
			$queryResult,
			SMW_OUTPUT_HTML
		);

		$this->assertEmpty( $result );

		foreach( $queryResult->getErrors() as $error ) {
			$this->assertEquals( $expected['message'], $error );
		}
	}

	public function testAddNumbersForDataItem() {

		$values = [];
		$expected = [];
		$keys = [ 'test', 'foo', 'bar' ];

		$reflector = new ReflectionClass( $this->aggregatablePrinter );
		$method = $reflector->getMethod( 'addNumbersForDataItem' );
		$method->setAccessible( true );

		for ( $i = 1; $i <= 10; $i++ ) {

			// Select random array key
			$name = $keys[rand(0, 2)];

			// Get a random number
			$random = rand( 10, 500 );

			// Set expected result and create dataItem
			$expected[$name] = isset( $expected[$name] ) ? $expected[$name] + $random : $random;
			$dataItem = new SMWDINumber( $random );

			$this->assertEquals( $random, $dataItem->getNumber() );
			$this->assertEquals( SMWDataItem::TYPE_NUMBER, $dataItem->getDIType() );

			$result = $method->invokeArgs(
				$this->aggregatablePrinter,
				[
					$dataItem,
					&$values,
					$name
				]
			);

			$this->assertInternalType( 'integer', $values[$name] );
			$this->assertEquals( $expected[$name], $values[$name] );
		}
	}

	/**
	 * @dataProvider numberDataProvider
	 */
	public function testGetNumericResults( $setup, $expected ) {

		$this->resultPrinterReflector->addParameters(
			$this->aggregatablePrinter,
			$setup['parameters']
		);

		$reflector = new ReflectionClass( $this->aggregatablePrinter );
		$method = $reflector->getMethod( 'getNumericResults' );
		$method->setAccessible( true );

		$result = $method->invoke(
			$this->aggregatablePrinter,
			$setup['queryResult'],
			SMW_OUTPUT_HTML
		);

		$this->assertInternalType(
			'array',
			$result
		);

		$this->assertEquals(
			$expected['result'],
			$result
		);
	}

	public function numberDataProvider() {

		$provider = [];

		$setup = [
			[ 'printRequest' => 'Foo', 'number' => 10, 'dataValue' => 'Quuey' ],
			[ 'printRequest' => 'Bar', 'number' => 20, 'dataValue' => 'Quuey' ],
			[ 'printRequest' => 'Bar', 'number' => 20, 'dataValue' => 'Xuuey' ]
		];

		// #0 aggregation = subject
		$parameters = [
			'headers'     => SMW_HEADERS_PLAIN,
			'offset'      => 0,
			'aggregation' => 'subject',
			'mainlabel'   => ''
		];

		$provider[] = [
			[
				'parameters'  => $parameters,
				'queryResult' => $this->buildMockQueryResult( $setup )
				],
			[
				'result'      => [ 'Quuey' => 50 ]
			]
		];

		// #1 aggregation = property
		$parameters = [
			'headers'     => SMW_HEADERS_PLAIN,
			'offset'      => 0,
			'aggregation' => 'property',
			'mainlabel'   => ''
		];

		$provider[] = [
			[
				'parameters'  => $parameters,
				'queryResult' => $this->buildMockQueryResult( $setup )
				],
			[
				'result'      => [ 'Foo' => 10, 'Bar' => 40 ]
			]
		];

		return $provider;
	}

	public function errorMessageProvider() {

		$message = wfMessage( 'smw-qp-aggregatable-empty-data' )->inContentLanguage()->text();

		$provider = [];

		$provider[] = [
			[
				'parameters'  => [ 'distribution' => true ],
		//		'queryResult' => $queryResult
				],
			[
				'message'     => $message
			]
		];

		// #1
		$provider[] = [
			[
				'parameters'  => [ 'distribution' => false ],
			//	'queryResult' => $queryResult
				],
			[
				'message'     => $message
			]
		];
		return $provider;
	}

	/**
	 * @return SMWQueryResult
	 */
	private function buildMockQueryResult( $setup ) {

		$printRequests = [];
		$resultArrays   = [];

		foreach ( $setup as $value ) {

			$printRequest = $this->getMockBuilder( '\SMW\Query\PrintRequest' )
				->disableOriginalConstructor()
				->getMock();

			$printRequest->expects( $this->any() )
				->method( 'getText' )
				->will( $this->returnValue( $value['printRequest'] ) );

			$printRequest->expects( $this->any() )
				->method( 'getLabel' )
				->will( $this->returnValue( $value['printRequest'] ) );

			$printRequests[] = $printRequest;

			$dataItem = $this->getMockBuilder( '\SMWDINumber' )
				->disableOriginalConstructor()
				->getMock();

			$dataItem->expects( $this->any() )
				->method( 'getDIType' )
				->will( $this->returnValue( SMWDataItem::TYPE_NUMBER ) );

			$dataItem->expects( $this->any() )
				->method( 'getNumber' )
				->will( $this->returnValue( $value['number'] ) );

			$dataValue = $this->getMockBuilder( '\SMWNumberValue' )
				->disableOriginalConstructor()
				->getMock();

			$dataValue->expects( $this->any() )
				->method( 'getTypeID' )
				->will( $this->returnValue( '_num' ) );

			$dataValue->expects( $this->any() )
				->method( 'getShortWikiText' )
				->will( $this->returnValue( $value['dataValue'] ) );

			$dataValue->expects( $this->any() )
				->method( 'getDataItem' )
				->will( $this->returnValue( $dataItem ) );

			$resultArray = $this->getMockBuilder( '\SMWResultArray' )
				->disableOriginalConstructor()
				->setMethods( [ 'getText', 'getPrintRequest', 'getNextDataValue', 'getNextDataItem' ] )
				->getMock();

			$resultArray->expects( $this->any() )
				->method( 'getText' )
				->will( $this->returnValue( $value['printRequest'] ) );

			$resultArray->expects( $this->any() )
				->method( 'getPrintRequest' )
				->will( $this->returnValue( $printRequest ) );

			$resultArray->expects( $this->any() )
				->method( 'getNextDataValue' )
				->will( $this->onConsecutiveCalls( $dataValue, false ) );

			$resultArray->expects( $this->any() )
				->method( 'getNextDataItem' )
				->will( $this->onConsecutiveCalls( $dataItem, false ) );

			$resultArrays[] = $resultArray;
		}

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getPrintRequests' )
			->will( $this->returnValue( $printRequests ) );

		$queryResult->expects( $this->any() )
			->method( 'getNext' )
			->will( $this->onConsecutiveCalls( $resultArrays, false ) );

		$queryResult->expects( $this->any() )
			->method( 'getLink' )
			->will( $this->returnValue( new \SMWInfolink( true, 'Lala', 'Lula' ) ) );

		$queryResult->expects( $this->any() )
			->method( 'hasFurtherResults' )
			->will( $this->returnValue( true ) );

		return $queryResult;
	}

}
