<?php

namespace SMW\Tests\Unit\Importer;

use PHPUnit\Framework\TestCase;
use SMW\Importer\ContentModeller;
use SMW\Importer\ImportContents;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Utils\File;
use SMW\Utils\FileFetcher;

/**
 * @covers \SMW\Importer\JsonImportContentsFileDirReader
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class JsonImportContentsFileDirReaderTest extends TestCase {

	private $contentModeller;
	private $fileFetcher;
	private $file;

	protected function setUp(): void {
		parent::setUp();

		$this->contentModeller = $this->getMockBuilder( ContentModeller::class )
			->disableOriginalConstructor()
			->getMock();

		$this->fileFetcher = $this->getMockBuilder( FileFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->file = $this->getMockBuilder( File::class )
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
		$importContents = $this->getMockBuilder( ImportContents::class )
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
					ImportContents::class,
					$importContents
				);
			}
		}
	}

	public function testGetContentList_WithError() {
		$importContents = $this->getMockBuilder( ImportContents::class )
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

		$this->assertStringContainsString(
			'FooFile is not readable',
			implode( '', $instance->getErrors() )
		);
	}

}
