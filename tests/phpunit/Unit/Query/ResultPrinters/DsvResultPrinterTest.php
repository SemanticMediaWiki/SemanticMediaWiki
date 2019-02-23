<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\DsvResultPrinter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\ResultPrinters\DsvResultPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DsvResultPrinterTest extends \PHPUnit_Framework_TestCase {

	private $queryResult;
	private $resultPrinterReflector;

	protected function setUp() {
		parent::setUp();

		$this->resultPrinterReflector = TestEnvironment::getUtilityFactory()->newResultPrinterReflector();

		$this->queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DsvResultPrinter::class,
			new DsvResultPrinter( 'dsv' )
		);

		$this->assertInstanceOf(
			'\SMW\ResultPrinter',
			new DsvResultPrinter( 'dsv' )
		);
	}

	public function testGetMimeType() {

		$instance = new DsvResultPrinter( 'dsv' );

		$this->assertEquals(
			'text/dsv',
			$instance->getMimeType( $this->queryResult )
		);
	}

	/**
	 * @dataProvider filenameDataProvider
	 */
	public function testGetFileName( $filename, $expected ) {

		$instance = new DsvResultPrinter( 'dsv' );

		$this->resultPrinterReflector->addParameters(
			$instance,
			[ 'filename' => $filename ]
		);

		$this->assertEquals(
			$expected,
			$instance->getFileName( $this->queryResult )
		);
	}

	public function filenameDataProvider() {

		$provider = [];

		$provider[] = [ 'Lala', 'Lala.dsv' ];
		$provider[] = [ 'Lala Lilu', 'Lala_Lilu.dsv' ];
		$provider[] = [ 'Foo.jso' , 'Foo.jso.dsv'];
		$provider[] = [ '' , 'result.dsv'];

		return $provider;
	}

}
