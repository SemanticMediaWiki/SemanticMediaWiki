<?php

namespace SMW\Tests\Unit\Services\Exception;

use PHPUnit\Framework\TestCase;
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
class ServiceNotFoundExceptionTest extends TestCase {

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
