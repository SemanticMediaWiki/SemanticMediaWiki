<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\EmbeddedResultPrinter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\ResultPrinters\EmbeddedResultPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class EmbeddedResultPrinterTest extends \PHPUnit\Framework\TestCase {

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
			EmbeddedResultPrinter::class,
			new EmbeddedResultPrinter( 'embedded' )
		);

		$this->assertInstanceOf(
			'\SMW\ResultPrinter',
			new EmbeddedResultPrinter( 'embedded' )
		);
	}

}
