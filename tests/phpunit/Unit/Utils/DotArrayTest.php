<?php

namespace SMW\Tests\Utils;

use SMW\Utils\DotArray;

/**
 * @covers \SMW\Utils\DotArray
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DotArrayTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider dotProvider
	 */
	public function testDotGet( $options, $key, $expected ) {

		$this->assertEquals(
			$expected,
			DotArray::get( $options, $key )
		);
	}

	public function dotProvider() {

		$options = [
			'Foo' => [
				'Bar' => [ 'Foobar' => 42 ],
				'Foobar' => 1001,
				'some.other.options' => 9999
			],
			'dot_1' => [ 'foo.bar' => [ 'n' => 1, 'size' => 250, 't' => false ] ],
			'dot_2' => [ 'foo.bar.foo' => [ 'n' => 1, 'size' => 9999, 't' => false ] ],
			'dot_3' => [ 'foo.bar' => [ 'foo' => [ 'n' => 1, 'size' => 1111, 't' => false ] ] ],
			'dot_4' => [ 'foo' => [ 'bar' => [ 'foo' => [ 'n' => 1, 'size' => 2222, 't' => false ] ] ] ],
			'dot_5' => [ 'foo' => [ 'bar' => [ 'foo' => [ 'n' => [ 1, 'size' => 4444, 't' => false ] ] ] ] ],

			'dot_6' => [
				'foo' => [
					// A real array definition takes precedence over a dot
					// path stored key
					'bar' => [ 'foobar' => [ 'foo' => [ 'n' => 123 ], 'foo.n' => 666, 'foo.size' => 777 ] ],
					'bar.foobar' => [ 'foo' => [ 'n' => 999, 'size' => 444 ] ],
				]
			],
		];

		yield 'dot_1.foo.bar' => [
			$options,
			'dot_1.foo.bar',
			[ 'n' => 1, 'size' => 250, 't' => false ]
		];

		yield 'dot_1.foo.bar.size' => [
			$options,
			'dot_1.foo.bar.size',
			250
		];

		yield 'dot_2.foo.bar.foo.size' => [
			$options,
			'dot_2.foo.bar.foo.size',
			9999
		];

		yield 'dot_3.foo.bar.foo.size' => [
			$options,
			'dot_3.foo.bar.foo.size',
			1111
		];

		yield 'dot_4.foo.bar.foo.size' => [
			$options,
			'dot_4.foo.bar.foo.size',
			2222
		];

		yield 'dot_5.foo.bar.foo.n.size' => [
			$options,
			'dot_5.foo.bar.foo.n.size',
			4444
		];

		yield 'dot_5.foo.bar.foo.size' => [
			$options,
			'dot_5.foo.bar.foo.size',
			false
		];

		yield 'Foo.Bar' => [
			$options,
			'Foo.Bar',
			[ 'Foobar' => 42 ]
		];

		yield 'Foo.Foobar' => [
			$options,
			'Foo.Foobar',
			1001
		];

		yield 'Foo.Bar.Foobar' => [
			$options,
			'Foo.Bar.Foobar',
			42
		];

		yield 'Foo.some.other.options' => [
			$options,
			'Foo.some.other.options',
			9999
		];

		yield 'Foo.Bar.Foobar.unkown' => [
			$options,
			'Foo.Bar.Foobar.unkown',
			false
		];

		yield 'dot_6.foo.bar.foobar.foo.n' => [
			$options,
			'dot_6.foo.bar.foobar.foo.n',
			123
		];

		yield 'dot_6.foo.bar.foobar.foo.size' => [
			$options,
			'dot_6.foo.bar.foobar.foo.size',
			false
		];
	}

}
