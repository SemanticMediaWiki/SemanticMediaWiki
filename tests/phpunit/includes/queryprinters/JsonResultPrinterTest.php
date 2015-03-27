<?php

namespace SMW\Test;

use SMW\Tests\Utils\Mock\MockObjectBuilder;
use SMW\Tests\Utils\Mock\CoreMockObjectRepository;

use SMW\JsonResultPrinter;
use SMW\ResultPrinter;

use ReflectionClass;

/**
 * @covers \SMW\JsonResultPrinter
 *
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

	protected $mockBuilder;

	protected function setUp() {
		parent::setUp();

		$this->mockBuilder = new MockObjectBuilder();
		$this->mockBuilder->registerRepository( new CoreMockObjectRepository() );
	}

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
			$this->newInstance()->getMimeType( $this->mockBuilder->newObject( 'QueryResult' ) ),
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
			$instance->getFileName( $this->mockBuilder->newObject( 'QueryResult' ) ),
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
			'lala' => __METHOD__,
			'lula' => 999388383838
		);

		$expected = array_merge( $result, array( 'rows' => count( $result ) ) );

		$instance = $this->newInstance( array( 'prettyprint' => false ) );

		$reflector = new ReflectionClass( '\SMW\JsonResultPrinter' );
		$getResultText = $reflector->getMethod( 'getResultText' );
		$getResultText->setAccessible( true );

		$queryResult = $this->mockBuilder->newObject( 'QueryResult', array(
			'serializeToArray' => $result,
			'getCount'         => count( $result )
		) );

		$results = $getResultText->invoke( $instance, $queryResult, SMW_OUTPUT_FILE );

		$this->assertInternalType( 'string', $results );
		$this->assertEquals( json_encode( $expected ), $results );

	}

}
