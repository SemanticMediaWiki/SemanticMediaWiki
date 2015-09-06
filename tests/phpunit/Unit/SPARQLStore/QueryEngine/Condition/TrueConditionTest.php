<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\TrueCondition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class TrueConditionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\SPARQLStore\QueryEngine\Condition\TrueCondition',
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
