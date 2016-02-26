<?php

namespace SMW\Tests;

use SMW\StringCondition;

/**
 * @covers \SMW\StringCondition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class StringConditionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new StringCondition( 'Foo', StringCondition::STRCOND_PRE, true );

		$this->assertInstanceOf(
			'\SMW\StringCondition',
			$instance
		);

		$this->assertSame(
			'Foo',
			$instance->string
		);

		$this->assertEquals(
			'Foo#0#1',
			$instance->getHash()
		);

		$this->assertEquals(
			StringCondition::STRCOND_PRE,
			$instance->condition
		);

		$this->assertTrue(
			$instance->asDisjunctiveCondition
		);
	}

}
