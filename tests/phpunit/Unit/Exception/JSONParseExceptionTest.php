<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\JSONParseException;

/**
 * @covers \SMW\Exception\JSONParseException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class JSONParseExceptionTest extends TestCase {

	public function testGetMessage() {
		$json = '{ "test": 123, }';

		$instance = new JSONParseException(
			$json
		);

		$this->assertStringContainsString(
			"Expected: 'STRING' - It appears you have an extra trailing comma",
			$instance->getMessage()
		);
	}

	public function testGetTidyMessage() {
		$json = '{ "test": 123, }';

		$instance = new JSONParseException(
			$json
		);

		$this->assertIsString(

			$instance->getTidyMessage()
		);
	}

}
