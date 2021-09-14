<?php

namespace SMW\Tests\Query\Exception;

use SMW\Query\Exception\ResultFormatNotFoundException;

/**
 * @covers \SMW\Query\Exception\ResultFormatNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ResultFormatNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

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
