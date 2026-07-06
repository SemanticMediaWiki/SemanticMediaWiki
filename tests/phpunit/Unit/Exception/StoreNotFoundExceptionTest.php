<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\StoreNotFoundException;

/**
 * @covers \SMW\Exception\StoreNotFoundException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class StoreNotFoundExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new StoreNotFoundException();

		$this->assertInstanceof(
			StoreNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
