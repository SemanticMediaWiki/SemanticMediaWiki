<?php

namespace SMW\Tests\Rule\Exception;

use SMW\Rule\Exception\RuleDefinitionSchemaException;

/**
 * @covers \SMW\Rule\Exception\RuleDefinitionSchemaException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleDefinitionSchemaExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new RuleDefinitionSchemaException();

		$this->assertInstanceof(
			RuleDefinitionSchemaException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
