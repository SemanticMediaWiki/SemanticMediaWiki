<?php

namespace SMW\Tests\Elastic\Exception;

use RuntimeException;
use SMW\Elastic\Exception\MissingEndpointConfigException;

/**
 * @covers \SMW\Elastic\Exception\MissingEndpointConfigException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MissingEndpointConfigExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RuntimeException::class,
			new MissingEndpointConfigException()
		);
	}

}
