<?php

namespace SMW\Tests\Query\ResultPrinters;

use ReflectionClass;
use SMW\Query\ResultPrinters\JsonResultPrinter;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\ResultPrinters\JsonResultPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class JsonResultPrinterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $queryResult;
	private $resultPrinterReflector;

	protected function setUp(): void {
		parent::setUp();

		$this->resultPrinterReflector = TestEnvironment::getUtilityFactory()->newResultPrinterReflector();

		$this->queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			JsonResultPrinter::class,
			new JsonResultPrinter( 'json' )
		);

		$this->assertInstanceOf(
			'\SMW\ResultPrinter',
			new JsonResultPrinter( 'json' )
		);
	}

	public function testGetMimeType() {
		$instance = new JsonResultPrinter( 'json' );

		$this->assertEquals(
			'application/json',
			$instance->getMimeType( $this->queryResult )
		);
	}

	/**
	 * @dataProvider filenameDataProvider
	 */
	public function testGetFileName( $filename, $expected ) {
		$instance = new JsonResultPrinter( 'json' );

		$this->resultPrinterReflector->addParameters(
			$instance,
			[ 'filename' => $filename ]
		);

		$this->assertEquals(
			$expected,
			$instance->getFileName( $this->queryResult )
		);
	}

	public function testGetResultText() {
		$res = [
			'lala' => __METHOD__,
			'lula' => 999388383838
		];

		$expected = array_merge( $res, [ 'rows' => count( $res ) ] );

		$this->queryResult->expects( $this->any() )
			->method( 'serializeToArray' )
			->willReturn( $res );

		$this->queryResult->expects( $this->any() )
			->method( 'getCount' )
			->willReturn( count( $res ) );

		$instance = new JsonResultPrinter( 'json' );

		$results = $this->resultPrinterReflector->invoke(
			$instance,
			$this->queryResult,
			SMW_OUTPUT_FILE
		);

		$this->assertIsString(

			$results
		);

		$this->assertEquals(
			json_encode( $expected ),
			$results
		);
	}

	public function filenameDataProvider() {
		$provider = [];

		$provider[] = [ 'Lala', 'Lala.json' ];
		$provider[] = [ 'Lala Lilu', 'Lala_Lilu.json' ];
		$provider[] = [ 'Foo.jso', 'Foo.jso.json' ];
		$provider[] = [ '', 'result.json' ];

		return $provider;
	}

}
