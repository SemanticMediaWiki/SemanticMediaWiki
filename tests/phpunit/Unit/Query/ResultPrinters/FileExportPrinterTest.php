<?php

namespace SMW\Tests\Unit\Query\ResultPrinters;

use PHPUnit\Framework\TestCase;
use SMW\Query\QueryResult;
use SMW\Query\ResultPrinters\FileExportPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\FileExportPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FileExportPrinterTest extends TestCase {

	public function testOutputAsFile_AccessSequence() {
		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$fileExportPrinter = $this->getMockBuilder( FileExportPrinter::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getFileResult', 'getMimeType', 'getFileName' ] )
			->getMockForAbstractClass();

		// #4375 (needs to be accessed first)
		$fileExportPrinter->expects( $this->once() )
			->method( 'getFileResult' )
			->with( $queryResult )
			->willReturn( __METHOD__ );

		$fileExportPrinter->expects( $this->once() )
			->method( 'getMimeType' );

		$fileExportPrinter->expects( $this->once() )
			->method( 'getFileName' )
			->willReturn( 'test' );

		$fileExportPrinter->disableHttpHeader();

		$this->expectOutputString( __METHOD__ );

		$fileExportPrinter->outputAsFile( $queryResult, [] );
	}

}
