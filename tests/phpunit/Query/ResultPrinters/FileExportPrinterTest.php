<?php

namespace SMW\Tests\Query\ResultPrinters;

/**
 * @covers \SMW\Query\ResultPrinters\FileExportPrinter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FileExportPrinterTest extends \PHPUnit\Framework\TestCase {

	public function testOutputAsFile_AccessSequence() {
		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$fileExportPrinter = $this->getMockBuilder( '\SMW\Query\ResultPrinters\FileExportPrinter' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getFileResult', 'getMimeType', 'getFileName' ] )
			->getMockForAbstractClass();

		// #4375 (needs to be accessed first)
		$fileExportPrinter->expects( $this->at( 0 ) )
			->method( 'getFileResult' )
			->with( $queryResult )
			->willReturn( __METHOD__ );

		$fileExportPrinter->expects( $this->at( 1 ) )
			->method( 'getMimeType' );

		$fileExportPrinter->expects( $this->at( 2 ) )
			->method( 'getFileName' )
			->willReturn( 'test' );

		$fileExportPrinter->disableHttpHeader();

		$this->expectOutputString( __METHOD__ );

		$fileExportPrinter->outputAsFile( $queryResult, [] );
	}

}
