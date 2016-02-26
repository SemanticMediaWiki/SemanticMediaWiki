<?php

namespace SMW\Tests;

use SMW\RequestOptions;
use SMW\StringCondition;

/**
 * @covers \SMW\RequestOptions
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class RequestOptionsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\RequestOptions',
			new RequestOptions()
		);
	}

	public function testAddStringCondition() {

		$instance = new RequestOptions();
		$instance->addStringCondition( 'Foo', StringCondition::STRCOND_PRE );

		foreach ( $instance->getStringConditions() as $stringCondition ) {
			$this->assertInstanceOf(
				'\SMW\StringCondition',
				$stringCondition
			);

			$this->assertFalse(
				$stringCondition->asDisjunctiveCondition
			);
		}

		$this->assertEquals(
			'-1#0##1##1|Foo#0#',
			$instance->getHash()
		);
	}

}
