<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\RdfResultPrinter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\ResultPrinters\RdfResultPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RdfResultPrinterTest extends \PHPUnit_Framework_TestCase {

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
			RdfResultPrinter::class,
			new RdfResultPrinter( 'rdf' )
		);

		$this->assertInstanceOf(
			'\SMW\ResultPrinter',
			new RdfResultPrinter( 'rdf' )
		);
	}

	public function testGetMimeType() {

		$instance = new RdfResultPrinter( 'json' );

		$this->assertEquals(
			'application/xml',
			$instance->getMimeType( $this->queryResult )
		);
	}

}
