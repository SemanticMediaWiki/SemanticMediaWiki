<?php

namespace SMW\Tests\Exception;

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
class DataItemExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new DataItemException();

		$this->assertInstanceof(
			'\SMW\Exception\DataItemException',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
