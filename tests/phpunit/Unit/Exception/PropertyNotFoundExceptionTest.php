<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\PropertyNotFoundException;

/**
 * @covers \SMW\Exception\PropertyNotFoundException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyNotFoundExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new PropertyNotFoundException();

		$this->assertInstanceof(
			PropertyNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
