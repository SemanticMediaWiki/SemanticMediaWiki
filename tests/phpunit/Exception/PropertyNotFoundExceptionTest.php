<?php

namespace SMW\Tests\Exception;

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
class PropertyNotFoundExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new PropertyNotFoundException();

		$this->assertInstanceof(
			'\SMW\Exception\PropertyNotFoundException',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
