<?php

namespace SMW\Tests;

use SMW\JsonFileReader;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\JsonFileReader
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class JsonFileReaderTest extends \PHPUnit_Framework_TestCase {

	private $fixturesFileProvider;

	protected function setUp() {
		parent::setUp();

		$this->fixturesFileProvider = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesFileProvider();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\JsonFileReader',
			new JsonFileReader()
		);
	}

	public function testReadDummyJsonFile() {

		$dummyJsonFile = $this->fixturesFileProvider->newDummyJsonFile( 'Foo.json' );

		$instance = new JsonFileReader( $dummyJsonFile->getPath() );

		$this->assertTrue(
			$instance->canRead()
		);

		$this->assertInternalType(
			'array',
			$instance->read()
		);

		$this->assertInternalType(
			'integer',
			$instance->getModificationTime()
		);

		$dummyJsonFile->delete();
	}

	public function testSetDifferentJsonFiles() {

		$dummyJsonFileOne = $this->fixturesFileProvider->newDummyJsonFile( 'Foo.json' );
		$dummyJsonFileTwo = $this->fixturesFileProvider->newDummyJsonFile( 'Bar.json' );

		$instance = new JsonFileReader();

		$this->assertNotSame(
			$dummyJsonFileOne->getPath(),
			$dummyJsonFileTwo->getPath()
		);

		$instance->setFile( $dummyJsonFileOne->getPath() );
		$jsonFileOne = $instance->read();

		$instance->setFile( $dummyJsonFileTwo->getPath() );
		$jsonFileTwo = $instance->read();

		$this->assertSame(
			$jsonFileOne,
			$jsonFileTwo
		);

		$dummyJsonFileOne->delete();
		$dummyJsonFileTwo->delete();
	}

	public function testTryToReadDummyNonJsonFileThrowsException() {

		$dummyJsonFile = $this->fixturesFileProvider->newDummyTextFile( 'Foo.json' );

		$instance = new JsonFileReader();
		$instance->setFile( $dummyJsonFile->getPath() );

		$this->assertTrue(
			$instance->canRead()
		);

		$this->setExpectedException( 'RuntimeException' );

		$this->assertInternalType(
			'array',
			$instance->read()
		);

		$dummyJsonFile->delete();
	}

	public function testTryToReadForInvalidFileThrowsException() {

		$instance = new JsonFileReader( '' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->read();
	}

	public function testTryToGetModificationTimeForInvalidFileThrowsException() {

		$instance = new JsonFileReader( '' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getModificationTime();
	}

}
