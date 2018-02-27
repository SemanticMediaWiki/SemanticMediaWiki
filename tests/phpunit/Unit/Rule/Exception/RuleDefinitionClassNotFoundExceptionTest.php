<?php

namespace SMW\Tests\Rule\Exception;

use SMW\Rule\Exception\RuleDefinitionClassNotFoundException;

/**
 * @covers \SMW\Rule\Exception\RuleDefinitionClassNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleDefinitionClassNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new RuleDefinitionClassNotFoundException();

		$this->assertInstanceof(
			RuleDefinitionClassNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
