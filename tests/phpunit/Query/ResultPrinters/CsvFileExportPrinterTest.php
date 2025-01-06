<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\CsvFileExportPrinter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\ResultPrinters\CsvFileExportPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CsvFileExportPrinterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CsvFileExportPrinter::class,
			new CsvFileExportPrinter( 'csv' )
		);
	}

	public function testGetResult_Empty() {
		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new CsvFileExportPrinter( 'csv' );

		$this->assertIsString(

			$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI )
		);
	}

	public function testLink() {
		$link = $this->getMockBuilder( '\SMWInfolink' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getQueryLink' )
			->willReturn( $link );

		$queryResult->expects( $this->any() )
			->method( 'getCount' )
			->willReturn( 1 );

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new CsvFileExportPrinter( 'csv' );
		$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI );
	}

}
