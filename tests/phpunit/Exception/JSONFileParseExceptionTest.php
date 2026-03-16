<?php

namespace SMW\Tests\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\JSONFileParseException;

/**
 * @covers \SMW\Exception\JSONFileParseException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class JSONFileParseExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new JSONFileParseException(
			\SMW_PHPUNIT_DIR . '/Fixtures/Exception/invalid.trailing.comma.json'
		);

		$this->assertStringContainsString(
			"Expected: 'STRING' - It appears you have an extra trailing comma",
			$instance->getMessage()
		);
	}

	public function testCanConstruct_FileNotReadable() {
		$instance = new JSONFileParseException(
			'Foo'
		);

		$this->assertStringContainsString(
			"Foo is not readable!",
			$instance->getMessage()
		);
	}

}
