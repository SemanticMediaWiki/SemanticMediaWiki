<?php

namespace SMW\Tests\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\DataItemException;

/**
 * @covers \SMW\Exception\DataItemException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DataItemExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new DataItemException();

		$this->assertInstanceof(
			DataItemException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
