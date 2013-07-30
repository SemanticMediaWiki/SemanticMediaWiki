<?php

namespace SMW\Test;

use SMW\JsonResultPrinter;
use SMW\ResultPrinter;

use ReflectionClass;

/**
 * Tests for the JsonResultPrinter class
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMW\JsonResultPrinter
 *
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class JsonResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\JsonResultPrinter';
	}

	/**
	 * Helper method that returns a SMWQueryResult object
	 *
	 * @since 1.9
	 *
	 * @return SMWQueryResult
	 */
	private function getMockQueryResult( $result = array() ) {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getCount' )
			->will( $this->returnValue( count( $result ) ) );

		$queryResult->expects( $this->any() )
			->method( 'serializeToArray' )
			->will( $this->returnValue( $result ) );

		return $queryResult;
	}

	/**
	 * Helper method that returns a JsonResultPrinter object
	 *
	 * @return JsonResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new JsonResultPrinter( 'json' ), $parameters );
	}

	/**
	 * @test JsonResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test JsonResultPrinter::getFileName
	 *
	 * @since 1.9
	 */
	public function testGetFileName() {

		$filename = $this->getRandomString() . ' ' . $this->getRandomString();
		$expected = str_replace( ' ', '_', $filename ) . '.json';
		$instance = $this->getInstance( array( 'searchlabel' => $filename ) );

		$this->assertEquals( $expected, $instance->getFileName( $this->getMockQueryResult() ) );
	}

	/**
	 * @test JsonResultPrinter::getResultText
	 *
	 * @since 1.9
	 */
	public function testGetResultText() {

		$result = array(
			'lala' => $this->getRandomString(),
			'lula' => $this->getRandomString()
		);

		$expected = array_merge( $result, array( 'rows' => count( $result ) ) );

		$instance = $this->getInstance( array( 'prettyprint' => false ) );

		$reflector = new ReflectionClass( $this->getClass() );
		$getResultText = $reflector->getMethod( 'getResultText' );
		$getResultText->setAccessible( true );

		$results = $getResultText->invoke( $instance, $this->getMockQueryResult( $result ), SMW_OUTPUT_FILE );

		$this->assertInternalType( 'string', $results );
		$this->assertEquals( json_encode( $expected ), $results );

	}

}
