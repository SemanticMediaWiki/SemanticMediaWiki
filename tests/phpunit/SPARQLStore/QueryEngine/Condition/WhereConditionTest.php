<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\WhereCondition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class WhereConditionTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			new WhereCondition( 'condition', true )
		);
	}

	public function testCommonMethods() {

		$instance = new WhereCondition( 'condition', true );

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
