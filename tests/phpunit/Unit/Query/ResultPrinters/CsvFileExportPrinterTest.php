<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\CsvFileExportPrinter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\ResultPrinters\CsvFileExportPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CsvFileExportPrinterTest extends \PHPUnit_Framework_TestCase {

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
			->will( $this->returnValue( [] ) );

		$instance = new CsvFileExportPrinter( 'csv' );

		$this->assertInternalType(
			'string',
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
			->will( $this->returnValue( $link ) );

		$queryResult->expects( $this->any() )
			->method( 'getCount' )
			->will( $this->returnValue( 1 ) );

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$instance = new CsvFileExportPrinter( 'csv' );
		$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI );
	}

}
