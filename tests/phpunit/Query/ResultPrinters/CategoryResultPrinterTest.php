<?php

namespace SMW\Tests\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\CategoryResultPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\CategoryResultPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CategoryResultPrinterTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CategoryResultPrinter::class,
			new CategoryResultPrinter( 'category' )
		);
	}

	public function testGetResult_Empty() {
		$queryResult = $this->getMockBuilder( QueryResult::class )
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
