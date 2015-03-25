<?php

namespace SMW\Test;

use SMW\AggregatablePrinter;
use SMW\Tests\Utils\Mock\MockObjectBuilder;
use SMW\Tests\Utils\Mock\CoreMockObjectRepository;

use SMWDataItem;
use SMWDINumber;

use ReflectionClass;
use SMWQueryResult;

/**
 * Tests for the AggregatablePrinter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\AggregatablePrinter
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class AggregatablePrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\AggregatablePrinter';
	}

	/**
	 * Helper method that returns a AggregatablePrinter object
	 *
	 * @return AggregatablePrinter
	 */
	private function newInstance( $parameters = array() ) {
		return $this->setParameters( $this->getMockForAbstractClass( $this->getClass(), array( 'table' ) ), $parameters );
	}

	/**
	 * @test AggregatablePrinter::getResultText
	 * @dataProvider errorMessageProvider
	 *
	 * @since 1.9
	 */
	public function testGetResultTextErrorMessage( $setup, $expected ) {

		$instance    = $this->newInstance( $setup['parameters'] );
		$queryResult = $setup['queryResult'];

		$reflection = new ReflectionClass( '\SMW\AggregatablePrinter' );
		$method = $reflection->getMethod( 'getResultText' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, $queryResult, SMW_OUTPUT_HTML );

		$this->assertEmpty( $result );

		foreach( $queryResult->getErrors() as $error ) {
			$this->assertEquals( $expected['message'], $error );
		}
	}

	/**
	 * @test AggregatablePrinter::addNumbersForDataItem
	 *
	 * @since 1.9
	 */
	public function testAddNumbersForDataItem() {

		$values = array();
		$expected = array();
		$keys = array( 'test', 'foo', 'bar' );

		$reflector = new ReflectionClass( '\SMW\AggregatablePrinter' );
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

			// Invoke the instance
			$result = $method->invokeArgs( $this->newInstance(), array( $dataItem, &$values, $name ) );

			$this->assertInternalType( 'integer', $values[$name] );
			$this->assertEquals( $expected[$name], $values[$name] );
		}
	}

	/**
	 * @test AggregatablePrinter::getNumericResults
	 * @dataProvider numberDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetNumericResults( $setup, $expected ) {

		$instance  = $this->newInstance( $setup['parameters'] );

		$reflector = new ReflectionClass( '\SMW\AggregatablePrinter' );
		$method = $reflector->getMethod( 'getNumericResults' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, $setup['queryResult'], SMW_OUTPUT_HTML );

		$this->assertInternalType(
			'array',
			$result,
			'Asserts that getNumericResults() returns an array'
		);

		$this->assertEquals(
			$expected['result'],
			$result,
			'Asserts that the getNumericResults() output matches the expected result'
		);

	}

	/**
	 * @return array
	 */
	public function errorMessageProvider() {

		$mockBuilder = new MockObjectBuilder();
		$mockBuilder->registerRepository( new CoreMockObjectRepository() );

		$message = wfMessage( 'smw-qp-aggregatable-empty-data' )->inContentLanguage()->text();

		$provider = array();

		$queryResult = $mockBuilder->newObject( 'QueryResult', array(
			'getErrors'  => array( $message )
		) );

		$provider[] = array(
			array(
				'parameters'  => array( 'distribution' => true ),
				'queryResult' => $queryResult
				),
			array(
				'message'     => $message
			)
		);

		// #1
		$provider[] = array(
			array(
				'parameters'  => array( 'distribution' => false ),
				'queryResult' => $queryResult
				),
			array(
				'message'     => $message
			)
		);
		return $provider;
	}

	/**
	 * @return array
	 */
	public function numberDataProvider() {

		$provider = array();

		$setup = array(
			array( 'printRequest' => 'Foo', 'number' => 10, 'dataValue' => 'Quuey' ),
			array( 'printRequest' => 'Bar', 'number' => 20, 'dataValue' => 'Quuey' ),
			array( 'printRequest' => 'Bar', 'number' => 20, 'dataValue' => 'Xuuey' )
		);

		// #0 aggregation = subject
		$parameters = array(
			'headers'     => SMW_HEADERS_PLAIN,
			'offset'      => 0,
			'aggregation' => 'subject',
			'mainlabel'   => ''
		);

		$provider[] = array(
			array(
				'parameters'  => $parameters,
				'queryResult' => $this->buildMockQueryResult( $setup )
				),
			array(
				'result'      => array( 'Quuey' => 50 )
			)
		);

		// #1 aggregation = property
		$parameters = array(
			'headers'     => SMW_HEADERS_PLAIN,
			'offset'      => 0,
			'aggregation' => 'property',
			'mainlabel'   => ''
		);

		$provider[] = array(
			array(
				'parameters'  => $parameters,
				'queryResult' => $this->buildMockQueryResult( $setup )
				),
			array(
				'result'      => array( 'Foo' => 10, 'Bar' => 40 )
			)
		);

		return $provider;
	}

	/**
	 * @return SMWQueryResult
	 */
	private function buildMockQueryResult( $setup ) {

		$mockBuilder = new MockObjectBuilder();
		$mockBuilder->registerRepository( new CoreMockObjectRepository() );

		$printRequests = array();
		$resultArray   = array();

		foreach ( $setup as $value ) {

			$printRequest = $mockBuilder->newObject( 'PrintRequest', array(
				'getText'  => $value['printRequest'],
				'getLabel' => $value['printRequest']
			) );

			$printRequests[] = $printRequest;

			$dataItem = $mockBuilder->newObject( 'DataItem', array(
				'getDIType' => SMWDataItem::TYPE_NUMBER,
				'getNumber' => $value['number']
			) );

			$dataValue = $mockBuilder->newObject( 'DataValue', array(
				'DataValueType'    => 'SMWNumberValue',
				'getTypeID'        => '_num',
				'getShortWikiText' => $value['dataValue'],
				'getDataItem'      => $dataItem
			) );

			$resultArray[] = $mockBuilder->newObject( 'ResultArray', array(
				'getText'          => $value['printRequest'],
				'getPrintRequest'  => $printRequest,
				'getNextDataValue' => $dataValue,
				'getNextDataItem'  => $dataItem
			) );

		}

		$queryResult = $mockBuilder->newObject( 'QueryResult', array(
			'getPrintRequests'  => $printRequests,
			'getNext'           => $resultArray,
			'getLink'           => new \SMWInfolink( true, 'Lala', 'Lula' ),
			'hasFurtherResults' => true
		) );

		return $queryResult;
	}

}
