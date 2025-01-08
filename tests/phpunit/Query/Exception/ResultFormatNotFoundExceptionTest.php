<?php

namespace SMW\Tests\Query\Exception;

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
class ResultFormatNotFoundExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new ResultFormatNotFoundException();

		$this->assertInstanceof(
			'\SMW\Query\Exception\ResultFormatNotFoundException',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
