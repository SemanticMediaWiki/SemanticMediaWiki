<?php

namespace SMW\Test;

use SMW\JsonResultPrinter;
use SMW\ResultPrinter;

/**
 * @covers \SMW\JsonResultPrinter
 *
 * @ingroup QueryPrinterTest
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class JsonResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\JsonResultPrinter';
	}

	/**
	 * @since 1.9
	 *
	 * @return JsonResultPrinter
	 */
	private function newInstance( $parameters = array() ) {
		return $this->setParameters( new JsonResultPrinter( 'json' ), $parameters );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
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
	 * @since 1.9
	 */
	public function testGetResultText() {

		$result = array(
			'lala' => $this->newRandomString(),
			'lula' => $this->newRandomString()
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
