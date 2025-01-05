<?php

namespace SMW\Tests\Elastic\Exception;

use RuntimeException;
use SMW\Elastic\Exception\ClientBuilderNotFoundException;

/**
 * @covers \SMW\Elastic\Exception\ClientBuilderNotFoundException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ClientBuilderNotFoundExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RuntimeException::class,
			new ClientBuilderNotFoundException()
		);
	}

}
