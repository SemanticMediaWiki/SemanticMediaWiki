<?php

namespace SMW\Tests\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\DataItemDeserializationException;
use SMW\Exception\DataItemException;

/**
 * @covers \SMW\Exception\DataItemDeserializationException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DataItemDeserializationExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new DataItemDeserializationException();

		$this->assertInstanceof(
			DataItemDeserializationException::class,
			$instance
		);

		$this->assertInstanceof(
			DataItemException::class,
			$instance
		);
	}

}
