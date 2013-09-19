<?php

namespace SMW\Test;

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
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class CsvResultPrinterTest extends QueryPrinterTestCase {

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

		$this->assertEquals( $filename, $instance->getFileName( $this->newMockBuilder()->newObject( 'QueryResult' ) ) );
	}

	/**
	 * @test CsvResultPrinter::getResultText
	 * @dataProvider resultDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetResultText(  $setup, $expected  ) {

		$instance  = $this->newInstance( $setup['parameters'] );
		$reflector = $this->newReflector();

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
	 * @return QueryResult
	 */
	private function buildMockQueryResult( $setup ) {

		$printRequests = array();
		$resultArray   = array();

		foreach ( $setup as $value ) {

			$printRequest = $this->newMockBuilder()->newObject( 'PrintRequest', array(
				'getText'  => $value['printRequest'],
				'getLabel' => $value['printRequest']
			) );

			$printRequests[] = $printRequest;

			$dataItem = $this->newMockBuilder()->newObject( 'DataItem', array(
				'getDIType'  => SMWDataItem::TYPE_WIKIPAGE,
			) );

			$dataValue = $this->newMockBuilder()->newObject( 'DataValue', array(
				'DataValueType'    => 'SMWWikiPageValue',
				'getTypeID'        => '_wpg',
				'getShortWikiText' => $value['dataValue'],
				'getWikiValue'     => $value['dataValue'],
				'getDataItem'      => $dataItem
			) );

			$resultArray[] = $this->newMockBuilder()->newObject( 'ResultArray', array(
				'getText'          => $value['printRequest'],
				'getPrintRequest'  => $printRequest,
				'getNextDataValue' => $dataValue,
				'getNextDataItem'  => $dataItem
			) );

		}

		$queryResult = $this->newMockBuilder()->newObject( 'QueryResult', array(
			'getPrintRequests'  => $printRequests,
			'getNext'           => $resultArray,
			'getLink'           => new \SMWInfolink( true, 'Lala' , 'Lula' ),
			'hasFurtherResults' => true
		) );

		return $queryResult;
	}

}
