<?php

namespace SMW\Tests\Unit\SPARQLStore\QueryEngine\Condition;

use PHPUnit\Framework\TestCase;
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
class FalseConditionTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FalseCondition::class,
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
