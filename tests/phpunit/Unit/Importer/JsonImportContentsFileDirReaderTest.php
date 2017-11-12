<?php

namespace SMW\TestsImporter;

use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Importer\ContentModeller;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Importer\JsonImportContentsFileDirReader
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonImportContentsFileDirReaderTest extends \PHPUnit_Framework_TestCase {

	private $contentModeller;
	private $testEnvironment;
	private $fixtures;

	protected function setUp() {
		parent::setUp();

		$this->contentModeller = new ContentModeller();

		$this->testEnvironment = new TestEnvironment();
		$this->fixtures = __DIR__ . '/Fixtures';
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			JsonImportContentsFileDirReader::class,
			new JsonImportContentsFileDirReader( $this->contentModeller, $this->fixtures )
		);
	}

	public function testGetContentList() {

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			[ $this->fixtures . '/ValidTextContent' ]
		);

		$contents = $instance->getContentList();

		$this->assertArrayHasKey(
			'content.json',
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

	public function testGetContentListOnFalseImportFormat() {

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			[ $this->fixtures . '/NoImportFormat' ]
		);

		$this->assertEmpty(
			$instance->getContentList()
		);
	}

	public function testGetContentListOnMissingSections() {

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			[ $this->fixtures . '/MissingSections' ]
		);

		$contents = $instance->getContentList();

		$this->assertArrayHasKey(
			'error.json',
			$contents
		);
	}

	public function testGetContentListWithInvalidPath() {

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			[ __DIR__ . '/InvalidPath' ]
		);

		$this->assertEmpty(
			$instance->getContentList()
		);
	}

	public function testGetContentListOnInvalidJsonThrowsException() {

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			[ $this->fixtures . '/InvalidJsonContent' ]
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getContentList();
	}

}
