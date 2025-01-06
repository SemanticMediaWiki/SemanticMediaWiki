<?php

namespace SMW\Tests\Importer;

use SMW\Importer\JsonContentIterator;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Importer\JsonContentIterator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class JsonContentIteratorTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $jsonImportContentsFileDirReader;

	protected function setUp(): void {
		parent::setUp();

		$this->jsonImportContentsFileDirReader = $this->getMockBuilder( JsonImportContentsFileDirReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment = new TestEnvironment();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\Importer\JsonContentIterator',
			new JsonContentIterator( $this->jsonImportContentsFileDirReader )
		);

		$this->assertInstanceOf(
			'\SMW\Importer\ContentIterator',
			new JsonContentIterator( $this->jsonImportContentsFileDirReader )
		);
	}

	public function testGetIterator() {
		$this->jsonImportContentsFileDirReader->expects( $this->atLeastOnce() )
			->method( 'getContentList' )
			->willReturn( [] );

		$instance = new JsonContentIterator(
			$this->jsonImportContentsFileDirReader
		);

		$this->assertInstanceOf(
			'\Iterator',
			$instance->getIterator()
		);
	}

	public function testGetDescription() {
		$instance = new JsonContentIterator(
			$this->jsonImportContentsFileDirReader
		);

		$instance->setDescription( 'Foo' );

		$this->assertSame(
			'Foo',
			$instance->getDescription()
		);
	}

}
