<?php

namespace SMW\Tests\Exception;

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
class DataTypeLookupExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new DataTypeLookupException();

		$this->assertInstanceof(
			'\SMW\Exception\DataTypeLookupException',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
