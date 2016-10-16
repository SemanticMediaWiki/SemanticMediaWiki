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
				$stringCondition->isDisjunctiveCondition
			);
		}

		$this->assertEquals(
			'-1#0##1##1#|Foo#0#',
			$instance->getHash()
		);
	}

	public function testLimit() {

		$instance = new RequestOptions();
		$instance->setLimit( 42 );

		$this->assertEquals(
			42,
			$instance->getLimit()
		);
	}

	public function testOffset() {

		$instance = new RequestOptions();
		$instance->setOffset( 42 );

		$this->assertEquals(
			42,
			$instance->getOffset()
		);
	}

}
