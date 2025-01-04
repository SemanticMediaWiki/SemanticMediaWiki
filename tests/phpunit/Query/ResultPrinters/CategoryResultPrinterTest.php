<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\CategoryResultPrinter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\ResultPrinters\CategoryResultPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CategoryResultPrinterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CategoryResultPrinter::class,
			new CategoryResultPrinter( 'category' )
		);
	}

	public function testGetResult_Empty() {
		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new CategoryResultPrinter( 'category' );

		$this->assertIsString(

			$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI )
		);
	}

}
