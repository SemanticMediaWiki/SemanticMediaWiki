<?php

namespace SMW\Tests\Query\ResultPrinters;

use SMW\Query\ResultPrinters\TemplateFileExportPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\TemplateFileExportPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TemplateFileExportPrinterTest extends \PHPUnit_Framework_TestCase {

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
			->will( $this->returnValue( [] ) );

		$instance = new TemplateFileExportPrinter( 'templatefile' );

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

		$instance = new TemplateFileExportPrinter( 'templatefile' );
		$instance->getResult( $queryResult, [], SMW_OUTPUT_WIKI );
	}

}
