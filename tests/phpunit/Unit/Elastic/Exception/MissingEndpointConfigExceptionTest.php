<?php

namespace SMW\Tests\Elastic\Exception;

use SMW\Elastic\Exception\MissingEndpointConfigException;
use RuntimeException;

/**
 * @covers \SMW\Elastic\Exception\MissingEndpointConfigException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MissingEndpointConfigExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RuntimeException::class,
			new MissingEndpointConfigException()
		);
	}

}
