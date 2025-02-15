<?php

namespace SMW\Tests\Exception;

use SMW\Exception\DataItemDeserializationException;

/**
 * @covers \SMW\Exception\DataItemDeserializationException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DataItemDeserializationExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new DataItemDeserializationException();

		$this->assertInstanceof(
			'\SMW\Exception\DataItemDeserializationException',
			$instance
		);

		$this->assertInstanceof(
			'\SMW\Exception\DataItemException',
			$instance
		);
	}

}
