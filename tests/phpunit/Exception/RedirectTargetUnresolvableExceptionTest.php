<?php

namespace SMW\Tests\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\RedirectTargetUnresolvableException;

/**
 * @covers \SMW\Exception\RedirectTargetUnresolvableException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RedirectTargetUnresolvableExceptionTest extends TestCase {

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
