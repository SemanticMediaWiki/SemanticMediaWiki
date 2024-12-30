<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\FilterCondition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FilterConditionTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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
