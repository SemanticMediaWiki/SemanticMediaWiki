<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

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
class FilterConditionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'SMW\SPARQLStore\QueryEngine\Condition\FilterCondition',
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
