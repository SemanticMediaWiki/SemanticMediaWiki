<?php

namespace SMW\Tests;

use SMW\JsonFileReader;

/**
 * @uses \SMW\JsonFileReader
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-unit
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class JsonFileReaderTest extends \PHPUnit_Framework_TestCase {

	protected $validJsonFile = null;
	protected $malformedJsonFile = null;

	protected function setUp() {

		$this->malformedJsonFile = new JsonFileReader(
			__DIR__ . '/../Util/Fixture/malformedJsonFileWithMetaRecord.json'
		);

		$this->validJsonFile = new JsonFileReader(
			__DIR__ . '/../Util/Fixture/validJsonFileWithMetaRecord.json'
		);
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\JsonFileReader',
			new JsonFileReader()
		);
	}

	public function testGetContentsOnValidJsonFile() {

		$this->assertInternalType(
			'array',
			$this->validJsonFile->getContents()
		);
	}

	public function testGetContentsOnValidJsonFileWithRemovedMetaRecord() {

		$this->assertFalse(
			array_key_exists( '@metadata', $this->validJsonFile->getContents() )
		);
	}

	public function testGetModificationTime() {

		$this->assertInternalType(
			'integer',
			$this->validJsonFile->getModificationTime()
		);
	}

	public function testInaccessibleJsonFileThrowsExeception() {

		$instance = new JsonFileReader( 'foo' );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getContents();
	}

	public function testGetContentsOnMalformedJsonFileThrowsException() {

		$this->setExpectedException( 'RuntimeException' );
		$this->malformedJsonFile->getContents();
	}

	public function testGetModificationTimeOnMalformedJsonFileThrowsException() {

		$this->setExpectedException( 'UnexpectedValueException' );
		$this->malformedJsonFile->getContents();
	}

}
