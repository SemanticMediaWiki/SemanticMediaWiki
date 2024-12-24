<?php

namespace SMW\Tests\Exception;

use SMW\Exception\JSONParseException;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Exception\JSONParseException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class JSONParseExceptionTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testGetMessage() {
		$json = '{ "test": 123, }';

		$instance = new JSONParseException(
			$json
		);

		$this->assertContains(
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
