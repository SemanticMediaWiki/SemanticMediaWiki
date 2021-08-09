<?php

namespace SMW\Tests\Elastic\Exception;

use SMW\Elastic\Exception\ClientBuilderNotFoundException;
use RuntimeException;

/**
 * @covers \SMW\Elastic\Exception\ClientBuilderNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ClientBuilderNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RuntimeException::class,
			new ClientBuilderNotFoundException()
		);
	}

}
