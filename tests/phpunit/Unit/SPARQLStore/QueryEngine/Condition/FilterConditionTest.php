<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\FilterCondition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FilterConditionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\SPARQLStore\QueryEngine\Condition\FilterCondition',
			new FilterCondition( 'condition' )
		);
	}

	public function testCommonMethods() {

		$instance = new FilterCondition( 'filter' );

		$this->assertInternalType(
			'string',
			$instance->getCondition()
		);

		$this->assertInternalType(
			'array',
			$instance->namespaces
		);

		$this->assertInternalType(
			'string',
			$instance->getWeakConditionString()
		);

		$this->assertInternalType(
			'boolean',
			$instance->isSafe()
		);
	}

}
