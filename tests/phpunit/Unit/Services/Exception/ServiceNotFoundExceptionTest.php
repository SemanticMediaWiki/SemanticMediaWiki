<?php

namespace SMW\Tests\Services\Exception;

use SMW\Services\Exception\ServiceNotFoundException;

/**
 * @covers \SMW\Services\Exception\ServiceNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ServiceNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

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
