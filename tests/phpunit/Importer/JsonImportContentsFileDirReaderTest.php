<?php

namespace SMW\Tests\Importer;

use SMW\Importer\ContentModeller;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Utils\FileFetcher;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Importer\JsonImportContentsFileDirReader
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonImportContentsFileDirReaderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $contentModeller;
	private $fileFetcher;
	private $file;

	protected function setUp(): void {
		parent::setUp();

		$this->contentModeller = $this->getMockBuilder( '\SMW\Importer\ContentModeller' )
			->disableOriginalConstructor()
			->getMock();

		$this->fileFetcher = $this->getMockBuilder( '\SMW\Utils\FileFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			JsonImportContentsFileDirReader::class,
			new JsonImportContentsFileDirReader( $this->contentModeller, $this->fileFetcher, $this->file )
		);
	}

	public function tesGetContentList() {
		$importContents = $this->getMockBuilder( '\SMW\Importer\ImportContents' )
			->disableOriginalConstructor()
			->getMock();

		$this->contentModeller->expects( $this->atLeastOnce() )
			->method( 'makeContentList' )
			->willReturn( $importContents );

		$this->fileFetcher->expects( $this->atLeastOnce() )
			->method( 'findByExtension' )
			->willReturn( [ 'FooFile' => [] ] );

		$this->file->expects( $this->atLeastOnce() )
			->method( 'read' )
			->with( $this->stringContains( 'FooFile' ) )
			->willReturn( json_encode( [ 'Foo' ] ) );

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			$this->fileFetcher,
			$this->file
		);

		$contents = $instance->getContentList();

		$this->assertArrayHasKey(
			'FooFile',
			$contents
		);

		foreach ( $contents as $content ) {
			foreach ( $content as $importContents ) {
				$this->assertInstanceOf(
					'\SMW\Importer\ImportContents',
					$importContents
				);
			}
		}
	}

	public function testGetContentList_WithError() {
		$importContents = $this->getMockBuilder( '\SMW\Importer\ImportContents' )
			->disableOriginalConstructor()
			->getMock();

		$this->fileFetcher->expects( $this->atLeastOnce() )
			->method( 'findByExtension' )
			->willReturn( [ 'FooFile' => [] ] );

		$this->file->expects( $this->atLeastOnce() )
			->method( 'read' )
			->with( $this->stringContains( 'FooFile' ) )
			->willReturn( 'Foo' );

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			$this->fileFetcher,
			$this->file,
			[ 'Foo' ]
		);

		$instance->getContentList();

		$this->assertContains(
			'FooFile is not readable',
			implode( '', $instance->getErrors() )
		);
	}

}
