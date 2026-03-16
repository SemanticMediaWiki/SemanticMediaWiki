<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\FilterCondition
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class FilterConditionTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FilterCondition::class,
			new FilterCondition( 'condition' )
		);
	}

	public function testCommonMethods() {
		$instance = new FilterCondition( 'filter' );

		$this->assertIsString(

			$instance->getCondition()
		);

		$this->assertIsArray(

			$instance->namespaces
		);

		$this->assertIsString(

			$instance->getWeakConditionString()
		);

		$this->assertIsBool(

			$instance->isSafe()
		);
	}

}
