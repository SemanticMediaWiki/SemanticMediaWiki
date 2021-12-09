<?php

namespace SMW\Tests\Exception;

use SMW\Exception\StoreNotFoundException;

/**
 * @covers \SMW\Exception\StoreNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class StoreNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new StoreNotFoundException();

		$this->assertInstanceof(
			'\SMW\Exception\StoreNotFoundException',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
