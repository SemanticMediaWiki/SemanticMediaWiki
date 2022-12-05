<?php

namespace SMW\Tests\Exception;

use SMW\Exception\RedirectTargetUnresolvableException;

/**
 * @covers \SMW\Exception\RedirectTargetUnresolvableException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RedirectTargetUnresolvableExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new RedirectTargetUnresolvableException();

		$this->assertInstanceof(
			RedirectTargetUnresolvableException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
