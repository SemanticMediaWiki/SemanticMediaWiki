<?php

namespace SMW\Tests\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\TemplateFileExportPrinter;
use SMWInfolink;

/**
 * @covers \SMW\Query\ResultPrinters\TemplateFileExportPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TemplateFileExportPrinterTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TemplateFileExportPrinter::class,
			new TemplateFileExportPrinter( 'templatefile' )
		);
	}

	public function testGetResult_Empty() {
		$queryResult = $this->getMockBuilder( QueryResult::class )
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
		$link = $this->getMockBuilder( SMWInfolink::class )
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

		$instance = new TemplateFileExportPrinter( 'templatefile' );
		$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI );
	}

}
