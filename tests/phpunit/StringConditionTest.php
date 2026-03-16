<?php

namespace SMW\Tests;

use PHPUnit\Framework\TestCase;
use SMW\StringCondition;

/**
 * @covers \SMW\StringCondition
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class StringConditionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new StringCondition( 'Foo', StringCondition::STRCOND_PRE, true );

		$this->assertInstanceOf(
			StringCondition::class,
			$instance
		);

		$this->assertSame(
			'Foo',
			$instance->string
		);

		$this->assertEquals(
			'Foo#0#1#',
			$instance->getHash()
		);

		$this->assertEquals(
			StringCondition::COND_PRE,
			$instance->condition
		);

		$this->assertTrue(
			$instance->isOr
		);

		$this->assertFalse(
			$instance->isNot
		);
	}

}
