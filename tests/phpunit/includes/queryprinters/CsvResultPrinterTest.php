<?php

namespace SMW\Test;

use ReflectionClass;
use SMW\CsvResultPrinter;
use SMWDataItem;

/**
 * Tests for the CsvResultPrinter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\CsvResultPrinter
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class CsvResultPrinterTest extends QueryPrinterTestCase {

	protected function setUp() {
		parent::setUp();
	}

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\CsvResultPrinter';
	}

	/**
	 * Helper method that returns a CsvResultPrinter object
	 *
	 * @return CsvResultPrinter
	 */
	private function newInstance( $parameters = array() ) {
		return $this->setParameters( new CsvResultPrinter( 'csv' ), $parameters );
	}

	/**
	 * @test CsvResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test CsvResultPrinter::getFileName
	 *
	 * @since 1.9
	 */
	public function testGetFileName() {

		$filename = 'FooQueey';
		$instance = $this->newInstance( array( 'filename' => $filename ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertEquals(
			$filename,
			$instance->getFileName( $queryResult ) );
	}

	/**
	 * @test CsvResultPrinter::getResultText
	 * @dataProvider resultDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetResultText(  $setup, $expected  ) {

		$instance  = $this->newInstance( $setup['parameters'] );
		$reflector = new ReflectionClass( '\SMW\CsvResultPrinter' );

		$property = $reflector->getProperty( 'fullParams' );
		$property->setAccessible( true );
		$property->setValue( $instance, array() );

		$method = $reflector->getMethod( 'linkFurtherResults' );
		$method->setAccessible( true );
		$method->invoke( $instance, $setup['queryResult'] );

		$method = $reflector->getMethod( 'getResultText' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, $setup['queryResult'], $setup['outputMode'] );

		$this->assertInternalType(
			'string',
			$result,
			'Asserts that the result always returns a string'
		);

		$this->assertEquals(
			$expected['result'],
			$result,
			'Asserts that getResultText() yields the expected result'
		);

	}

	/**
	 * @return array
	 */
	public function resultDataProvider() {

		$provider = array();

		$setup = array(
			array( 'printRequest' => 'Foo', 'dataValue' => 'Quuey' ),
			array( 'printRequest' => 'Bar', 'dataValue' => 'Quuey' ),
			array( 'printRequest' => 'Bam', 'dataValue' => 'Xuuey' )
		);

		// #0
		$parameters = array(
			'headers'   => SMW_HEADERS_PLAIN,
			'format'    => 'csv',
			'sep'       => ',',
			'showsep'   => false,
			'offset'    => 0
		);

		$provider[] = array(
			array(
				'parameters'  => $parameters,
				'queryResult' => $this->buildMockQueryResult( $setup ),
				'outputMode'  => SMW_OUTPUT_FILE
			),
			array(
				'result'     => implode( ',', array(  'Foo', 'Bar', 'Bam' ) ) . "\n" .  implode( ',', array(  'Quuey', 'Quuey', 'Xuuey' ) ) . "\n"
			)
		);

		return $provider;

	}

	/**
	 * @return SMWQueryResult
	 */
	private function buildMockQueryResult( $setup ) {

		$printRequests = array();
		$resultArrays   = array();

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

			$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
				->disableOriginalConstructor()
				->getMock();

			$dataItem->expects( $this->any() )
				->method( 'getDIType' )
				->will( $this->returnValue( SMWDataItem::TYPE_WIKIPAGE ) );

			$dataValue = $this->getMockBuilder( '\SMWWikiPageValue' )
				->disableOriginalConstructor()
				->getMock();

			$dataValue->expects( $this->any() )
				->method( 'getTypeID' )
				->will( $this->returnValue( '_wpg' ) );

			$dataValue->expects( $this->any() )
				->method( 'getShortWikiText' )
				->will( $this->returnValue( $value['dataValue'] ) );

			$dataValue->expects( $this->any() )
				->method( 'getWikiValue' )
				->will( $this->returnValue( $value['dataValue'] ) );

			$dataValue->expects( $this->any() )
				->method( 'getDataItem' )
				->will( $this->returnValue( $dataItem ) );

			$resultArray = $this->getMockBuilder( '\SMWResultArray' )
				->disableOriginalConstructor()
				->setMethods( array( 'getText', 'getPrintRequest', 'getNextDataValue', 'getNextDataItem' ) )
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
