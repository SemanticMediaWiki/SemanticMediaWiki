<?php

namespace SMW\Tests\Services\Exception;

use SMW\Services\Exception\ServiceNotFoundException;

/**
 * @covers \SMW\Services\Exception\ServiceNotFoundException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ServiceNotFoundExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new ServiceNotFoundException( 'foo' );

		$this->assertInstanceof(
			ServiceNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\InvalidArgumentException',
			$instance
		);
	}

}
