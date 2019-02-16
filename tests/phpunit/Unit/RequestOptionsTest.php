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
			RequestOptions::class,
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
				$stringCondition->isOr
			);
		}

		$this->assertEquals(
			'[-1,0,false,true,null,true,false,"Foo#0##",[],[]]',
			$instance->getHash()
		);
	}

	public function testEddExtraCondition() {

		$instance = new RequestOptions();
		$instance->addExtraCondition( 'Foo' );
		$instance->addExtraCondition( [ 'Bar' => 'Foobar' ] );

		$this->assertEquals(
			[
				'Foo',
				[ 'Bar' => 'Foobar' ]
			],
			$instance->getExtraConditions()
		);

		$this->assertEquals(
			'[-1,0,false,true,null,true,false,"",["Foo",{"Bar":"Foobar"}],[]]',
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

		$provider[] = [
			42,
			42
		];

		$provider[] = [
			'42foo',
			42
		];

		return $provider;
	}

}
