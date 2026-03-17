<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\Condition;

use PHPUnit\Framework\TestCase;
use SMW\Exporter\Element\ExpElement;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class SingletonConditionTest extends TestCase {

	public function testCanConstruct() {
		$expElement = $this->getMockBuilder( ExpElement::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SingletonCondition::class,
			new SingletonCondition( $expElement )
		);
	}

	public function testCommonMethods() {
		$expElement = $this->getMockBuilder( ExpElement::class )
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
