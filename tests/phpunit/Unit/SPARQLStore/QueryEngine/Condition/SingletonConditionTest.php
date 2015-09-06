<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SingletonConditionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$expElement = $this->getMockBuilder( '\SMWExpElement' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition',
			new SingletonCondition( $expElement )
		);
	}

	public function testCommonMethods() {

		$expElement = $this->getMockBuilder( '\SMWExpElement' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SingletonCondition( $expElement );

		$this->assertInternalType(
			'string',
			$instance->getCondition()
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
