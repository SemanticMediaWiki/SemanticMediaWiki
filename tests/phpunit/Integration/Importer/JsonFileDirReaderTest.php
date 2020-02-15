<?php

namespace SMW\Tests\Importer\Integration;

use SMW\Importer\ContentModeller;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Utils\FileFetcher;
use SMW\Utils\File;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonFileDirReaderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $contentModeller;
	private $fileFetcher;
	private $file;

	protected function setUp() : void {
		parent::setUp();

		$this->contentModeller = new ContentModeller();
		$this->fileFetcher = new FileFetcher();
		$this->file = new File();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			JsonImportContentsFileDirReader::class,
			new JsonImportContentsFileDirReader( $this->contentModeller, $this->fileFetcher, $this->file )
		);
	}

	public function testGetContentList() {

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			$this->fileFetcher,
			$this->file,
			[ SMW_PHPUNIT_DIR . '/Fixtures/Importer/Others/ValidTextContent' ]
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
			$this->fileFetcher,
			$this->file,
			[ SMW_PHPUNIT_DIR . '/Fixtures/Importer/Others/NoImportFormat' ]
		);

		$this->assertEmpty(
			$instance->getContentList()
		);
	}

	public function testGetContentListOnMissingSections() {

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			$this->fileFetcher,
			$this->file,
			[ SMW_PHPUNIT_DIR . '/Fixtures/Importer/Others/MissingSections' ]
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
			$this->fileFetcher,
			$this->file,
			[ SMW_PHPUNIT_DIR . '/Fixtures/Importer/Others/InvalidPath' ]
		);

		$this->assertEmpty(
			$instance->getContentList()
		);
	}

	public function testGetContentListOnInvalidJson_Error() {

		$instance = new JsonImportContentsFileDirReader(
			$this->contentModeller,
			$this->fileFetcher,
			$this->file,
			[ SMW_PHPUNIT_DIR . '/Fixtures/Importer/Others/InvalidJsonContent' ]
		);

		$instance->getContentList();

		$this->assertContains(
			'JSON error in file',
			implode( '', $instance->getErrors() )
		);
	}

}
