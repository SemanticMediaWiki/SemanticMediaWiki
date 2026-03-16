<?php

namespace SMW\Tests\Elastic\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SMW\Elastic\Exception\MissingEndpointConfigException;

/**
 * @covers \SMW\Elastic\Exception\MissingEndpointConfigException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class MissingEndpointConfigExceptionTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RuntimeException::class,
			new MissingEndpointConfigException()
		);
	}

}
