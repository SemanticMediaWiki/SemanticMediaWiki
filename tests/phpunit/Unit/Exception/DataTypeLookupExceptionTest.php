<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\DataTypeLookupException;

/**
 * @covers \SMW\Exception\DataTypeLookupException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DataTypeLookupExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new DataTypeLookupException();

		$this->assertInstanceof(
			DataTypeLookupException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
