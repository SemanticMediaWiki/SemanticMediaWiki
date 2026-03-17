<?php

namespace SMW\Tests\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\EmbeddedResultPrinter;
use SMW\Query\ResultPrinters\ResultPrinter;
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
class EmbeddedResultPrinterTest extends TestCase {

	private $queryResult;
	private $resultPrinterReflector;

	protected function setUp(): void {
		parent::setUp();

		$this->resultPrinterReflector = TestEnvironment::getUtilityFactory()->newResultPrinterReflector();

		$this->queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			EmbeddedResultPrinter::class,
			new EmbeddedResultPrinter( 'embedded' )
		);

		$this->assertInstanceOf(
			ResultPrinter::class,
			new EmbeddedResultPrinter( 'embedded' )
		);
	}

}
