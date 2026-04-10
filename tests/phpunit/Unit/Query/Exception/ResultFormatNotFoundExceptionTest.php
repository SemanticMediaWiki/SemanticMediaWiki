<?php

namespace SMW\Tests\Unit\Query\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SMW\Query\Exception\ResultFormatNotFoundException;

/**
 * @covers \SMW\Query\Exception\ResultFormatNotFoundException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ResultFormatNotFoundExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new ResultFormatNotFoundException();

		$this->assertInstanceof(
			ResultFormatNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			RuntimeException::class,
			$instance
		);
	}

}
