<?php

namespace SMW\Tests\Rule\Exception;

use SMW\Rule\Exception\RuleTypeNotFoundException;

/**
 * @covers \SMW\Rule\Exception\RuleTypeNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleTypeNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new RuleTypeNotFoundException();

		$this->assertInstanceof(
			RuleTypeNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
