<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\TrueCondition
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class TrueConditionTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TrueCondition::class,
			new TrueCondition()
		);
	}

	public function testCommonMethods() {
		$instance = new TrueCondition();

		$this->assertEmpty(
			$instance->getCondition()
		);

		$this->assertFalse(
			$instance->isSafe()
		);
	}

}
