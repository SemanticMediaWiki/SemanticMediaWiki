<?php

namespace SMW\Tests\Query\Exception;

use PHPUnit\Framework\TestCase;
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
			'\RuntimeException',
			$instance
		);
	}

}
