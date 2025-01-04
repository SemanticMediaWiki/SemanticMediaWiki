<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\TemplateFileExportPrinter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\ResultPrinters\TemplateFileExportPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TemplateFileExportPrinterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TemplateFileExportPrinter::class,
			new TemplateFileExportPrinter( 'templatefile' )
		);
	}

	public function testGetResult_Empty() {
		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$instance = new TemplateFileExportPrinter( 'templatefile' );

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

		$instance = new TemplateFileExportPrinter( 'templatefile' );
		$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI );
	}

}
