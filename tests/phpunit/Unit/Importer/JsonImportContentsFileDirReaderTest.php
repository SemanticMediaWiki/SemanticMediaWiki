<?php

namespace SMW\TestsImporter;

use SMW\Importer\JsonImportContentsFileDirReader;
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

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Importer\JsonImportContentsFileDirReader',
			new JsonImportContentsFileDirReader( $this->testEnvironment->getFixturesLocation() )
		);
	}

	public function testGetContents() {

		$instance = new JsonImportContentsFileDirReader(
			$this->testEnvironment->getFixturesLocation( 'Importer/ValidContent' )
		);

		$contents = $instance->getContents();

		$this->assertArrayHasKey(
			'content.json',
			$contents
		);

		foreach ( $contents as $content ) {
			foreach ( $content as $importContents ) {
				$this->assertNotEmpty(
					$importContents->getContents()
				);
			}
		}
	}

	public function testGetContentsOnFalseImportFormat() {

		$instance = new JsonImportContentsFileDirReader(
			$this->testEnvironment->getFixturesLocation( 'Importer/NoImportFormat' )
		);

		$this->assertEmpty(
			$instance->getContents()
		);
	}

	public function testGetContentsOnMissingSections() {

		$instance = new JsonImportContentsFileDirReader(
			$this->testEnvironment->getFixturesLocation( 'Importer/MissingSections' )
		);

		$contents = $instance->getContents();

		$this->assertArrayHasKey(
			'error.json',
			$contents
		);
	}

	public function testGetContentsFromInvalidPathThrowsException() {

		$instance = new JsonImportContentsFileDirReader(
			__DIR__ . '/InvalidPath'
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getContents();
	}

	public function testGetContentsOnInvalidJsonThrowsException() {

		$instance = new JsonImportContentsFileDirReader(
			$this->testEnvironment->getFixturesLocation( 'Importer/InvalidJsonContent' )
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getContents();
	}

}
