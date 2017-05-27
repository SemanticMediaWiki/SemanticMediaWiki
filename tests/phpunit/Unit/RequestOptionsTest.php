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
			'[-1,0,false,true,null,true,"Foo#0#",[]]',
			$instance->getHash()
		);
	}

	public function testEddExtraCondition() {

		$instance = new RequestOptions();
		$instance->addExtraCondition( 'Foo' );
		$instance->addExtraCondition( array( 'Bar' => 'Foobar' ) );

		$this->assertEquals(
			array(
				'Foo',
				array( 'Bar' => 'Foobar' )
			),
			$instance->getExtraConditions()
		);

		$this->assertEquals(
			'[-1,0,false,true,null,true,"",["Foo",{"Bar":"Foobar"}]]',
			$instance->getHash()
		);
	}

	/**
	 * @dataProvider numberProvider
	 */
	public function testLimit( $limit, $expected ) {

		$instance = new RequestOptions();
		$instance->setLimit( $limit );

		$this->assertEquals(
			$expected,
			$instance->getLimit()
		);

		$instance->limit = $limit;

		$this->assertEquals(
			$expected,
			$instance->getLimit()
		);
	}

	/**
	 * @dataProvider numberProvider
	 */
	public function testOffset( $offset, $expected ) {

		$instance = new RequestOptions();
		$instance->setOffset( $offset );

		$this->assertEquals(
			$expected,
			$instance->getOffset()
		);

		$instance->offset = $offset;

		$this->assertEquals(
			$expected,
			$instance->getOffset()
		);
	}

	public function numberProvider() {

		$provider[] = array(
			42,
			42
		);

		$provider[] = array(
			'42foo',
			42
		);

		return $provider;
	}

}
