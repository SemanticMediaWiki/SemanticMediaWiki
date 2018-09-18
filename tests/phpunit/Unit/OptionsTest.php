<?php

namespace SMW\Tests;

use SMW\Options;

/**
 * @covers \SMW\Options
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.3
 *
 * @author mwjames
 */
class OptionsTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Options',
			new Options()
		);
	}

	public function testAddOption() {

		$instance = new Options();

		$this->assertFalse(
			$instance->has( 'Foo' )
		);

		$instance->set( 'Foo', 42 );

		$this->assertEquals(
			42,
			$instance->get( 'Foo' )
		);
	}

	public function testToArray() {

		$instance = new Options(
			[ 'Foo' => 42 ]
		);

		$this->assertEquals(
			[ 'Foo' => 42 ],
			$instance->toArray()
		);
	}

	public function testUnregisteredKeyThrowsException() {

		$instance = new Options();

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->get( 'Foo' );
	}

	public function testSafeGetOnUnregisteredKey() {

		$instance = new Options();

		$this->assertEquals(
			'Default',
			$instance->safeGet( 'Foo', 'Default' )
		);
	}

	public function testFilter() {

		$instance = new Options();
		$instance->set( 'Foo', '123' );
		$instance->set( 'Bar', '456' );

		$this->assertEquals(
			[
				'Foo' => '123'
			],
			$instance->filter( [ 'Foo' ] )
		);
	}

	/**
	 * @dataProvider dotProvider
	 */
	public function testDotGet( $options, $key, $expected ) {

		$instance = new Options( $options );

		$this->assertEquals(
			$expected,
			$instance->dotGet( $key )
		);
	}

	/**
	 * @dataProvider isFlagSetProvider
	 */
	public function testIsFlagSet( $value, $flag, $expected ) {

		$instance = new Options();
		$instance->set( 'Foo', $value );

		$this->assertEquals(
			$expected,
			$instance->isFlagSet( 'Foo', $flag )
		);
	}

	public function isSetProvider() {

		yield [
			100,
			100,
			true
		];

		yield [
			'foo',
			'foo',
			true
		];

		yield [
			( ( 4 | 8 ) | 16 ),
			2,
			false
		];

		yield [
			4 | 16,
			15,
			false
		];

		yield [
			false,
			2,
			false
		];

		yield [
			false,
			false,
			true
		];

		yield [
			true,
			true,
			true
		];
	}

	public function isFlagSetProvider() {

		yield [
			( ( 4 | 8 ) | 16 ),
			16,
			true
		];

		yield [
			( ( 4 | 8 ) | 16 ),
			4,
			true
		];

		yield [
			( ( 4 | 8 ) | 16 ),
			2,
			false
		];

		yield [
			4 | 16,
			15,
			false
		];

		yield [
			false,
			2,
			false
		];
	}

	public function dotProvider() {

		$o = [ 'Foo' => [
			'Bar' => [ 'Foobar' => 42 ],
			'Foobar' => 1001,
			'some.other.options' => 9999
			],
		];

		yield [
			$o,
			'Foo.Bar',
			[ 'Foobar' => 42 ]
		];

		yield [
			$o,
			'Foo.Foobar',
			1001
		];

		yield [
			$o,
			'Foo.Bar.Foobar',
			 42
		];

		yield [
			$o,
			'Foo.some.other.options',
			9999
		];

		yield [
			$o,
			'Foo.Bar.Foobar.unkown',
			false
		];
	}

}
