<?php

namespace SMW\Tests\Importer;

use SMW\Importer\JsonContentIterator;
use SMW\Importer\JsonImportContentsFileDirReader;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Importer\JsonContentIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonContentIteratorTest extends \PHPUnit_Framework_TestCase {

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
			->will( $this->returnValue( [] ) );

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
