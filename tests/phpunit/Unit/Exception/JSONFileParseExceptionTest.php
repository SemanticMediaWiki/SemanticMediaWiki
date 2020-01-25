<?php

namespace SMW\Tests\Exception;

use SMW\Exception\JSONFileParseException;

/**
 * @covers \SMW\Exception\JSONFileParseException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class JSONFileParseExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new JSONFileParseException(
			SMW_PHPUNIT_DIR . '/Fixtures/Exception/invalid.trailing.comma.json'
		);

		$this->assertContains(
			"Expected: 'STRING' - It appears you have an extra trailing comma",
			$instance->getMessage()
		);
	}

	public function testCanConstruct_FileNotReadable() {

		$instance = new JSONFileParseException(
			'Foo'
		);

		$this->assertContains(
			"Foo is not readable!",
			$instance->getMessage()
		);
	}

}
