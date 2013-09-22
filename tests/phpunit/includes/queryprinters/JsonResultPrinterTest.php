<?php

namespace SMW\Test;

use SMW\JsonResultPrinter;
use SMW\ResultPrinter;

/**
 * Tests for the JsonResultPrinter class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
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
	 * Helper method that returns a JsonResultPrinter object
	 *
	 * @return JsonResultPrinter
	 */
	private function newInstance( $parameters = array() ) {
		return $this->setParameters( new JsonResultPrinter( 'json' ), $parameters );
	}

	/**
	 * @test JsonResultPrinter::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test JsonResultPrinter::getMimeType
	 *
	 * @since 1.9
	 */
	public function testGetMimeType() {

		$this->assertEquals(
			'application/json',
			$this->newInstance()->getMimeType( $this->newMockBuilder()->newObject( 'QueryResult' ) ),
			'Asserts that getMimeType() yields an expected result'
		);

	}

	/**
	 * @test JsonResultPrinter::getFileName
	 * @dataProvider filenameDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetFileName( $filename, $expected ) {

		$instance = $this->newInstance( array( 'searchlabel' => $filename ) );

		$this->assertEquals(
			$expected,
			$instance->getFileName( $this->newMockBuilder()->newObject( 'QueryResult' ) ),
			'Asserts that getFileName() yields an expected result');
	}

	/**
	 * @return array
	 */
	public function filenameDataProvider() {

		$provider = array();

		$provider[] = array( 'Lala', 'Lala.json' );
		$provider[] = array( 'Lala Lilu', 'Lala_Lilu.json' );
		$provider[] = array( '' , 'result.json');

		return $provider;


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

		$instance = $this->newInstance( array( 'prettyprint' => false ) );

		$reflector = $this->newReflector();
		$getResultText = $reflector->getMethod( 'getResultText' );
		$getResultText->setAccessible( true );

		$queryResult = $this->newMockBuilder()->newObject( 'QueryResult', array(
			'serializeToArray' => $result,
			'getCount'         => count( $result )
		) );

		$results = $getResultText->invoke( $instance, $queryResult, SMW_OUTPUT_FILE );

		$this->assertInternalType( 'string', $results );
		$this->assertEquals( json_encode( $expected ), $results );

	}

}
