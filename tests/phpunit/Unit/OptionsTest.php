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

	public function testGetOptions() {

		$instance = new Options(
			array( 'Foo' => 42 )
		);

		$this->assertEquals(
			array( 'Foo' => 42 ),
			$instance->getOptions()
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

}
