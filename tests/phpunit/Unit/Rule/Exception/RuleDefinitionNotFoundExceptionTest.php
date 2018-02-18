<?php

namespace SMW\Tests\Rule\Exception;

use SMW\Rule\Exception\RuleDefinitionNotFoundException;

/**
 * @covers \SMW\Rule\Exception\RuleDefinitionNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleDefinitionNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new RuleDefinitionNotFoundException();

		$this->assertInstanceof(
			RuleDefinitionNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
