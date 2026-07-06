<?php

namespace SMW\Tests\Unit\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\Formatters\Infolink;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\CsvFileExportPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\CsvFileExportPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CsvFileExportPrinterTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CsvFileExportPrinter::class,
			new CsvFileExportPrinter( 'csv' )
		);
	}

	public function testGetResult_Empty() {
		$queryResult = $this->getMockBuilder( QueryResult::class )
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
		$link = $this->getMockBuilder( Infolink::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult = $this->getMockBuilder( QueryResult::class )
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
