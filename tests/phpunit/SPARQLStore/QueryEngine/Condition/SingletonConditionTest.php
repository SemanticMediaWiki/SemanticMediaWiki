<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SingletonConditionTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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

		$this->assertIsString(

			$instance->getCondition()
		);

		$this->assertIsString(

			$instance->getWeakConditionString()
		);

		$this->assertIsBool(

			$instance->isSafe()
		);
	}

}
