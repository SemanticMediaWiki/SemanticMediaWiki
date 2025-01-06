<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\FalseCondition
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class FalseConditionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'SMW\SPARQLStore\QueryEngine\Condition\FalseCondition',
			new FalseCondition()
		);
	}

	public function testCommonMethods() {
		$instance = new FalseCondition();

		$this->assertNotEmpty(
			$instance->getCondition()
		);

		$this->assertTrue(
			$instance->isSafe()
		);
	}

}
