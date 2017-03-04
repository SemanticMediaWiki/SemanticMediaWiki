<?php

namespace SMW\TestsImporter;

use SMW\Importer\JsonImportContentsIterator;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Importer\JsonImportContentsIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonImportContentsIteratorTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $jsonImportContentsFileDirReader;

	protected function setUp() {
		parent::setUp();

		$this->jsonImportContentsFileDirReader = $this->getMockBuilder( JsonImportContentsFileDirReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Importer\JsonImportContentsIterator',
			new JsonImportContentsIterator( $this->jsonImportContentsFileDirReader )
		);

		$this->assertInstanceOf(
			'\SMW\Importer\ImportContentsIterator',
			new JsonImportContentsIterator( $this->jsonImportContentsFileDirReader )
		);
	}

	public function testGetIterator() {

		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getContents' )
			->will( $this->returnValue( array() ) );

		$instance = new JsonImportContentsIterator(
			$this->jsonImportContentsFileDirReader
		);

		$this->assertInstanceOf(
			'\Iterator',
			$instance->getIterator()
		);
	}

	public function testGetDescription() {

		$instance = new JsonImportContentsIterator(
			$this->jsonImportContentsFileDirReader
		);

		$instance->setDescription( 'Foo' );

		$this->assertSame(
			'Foo',
			$instance->getDescription()
		);
	}

}
