<?php

namespace SMW\Tests\Utils;

use SMW\Utils\StatsFormatter;

/**
 * @covers \SMW\Utils\StatsFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class StatsFormatterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider statsProvider
	 */
	public function testGetStatsFromFlatKey( $stats, $expected ) {
		$this->assertEquals(
			$expected,
			StatsFormatter::getStatsFromFlatKey( $stats )
		);
	}

	/**
	 * @dataProvider formatStringProvider
	 */
	public function testFormatReturnsString( $stats, $format ) {
		$this->assertIsString(
			StatsFormatter::format( $stats, $format )
		);
	}

	/**
	 * @dataProvider formatArrayProvider
	 */
	public function testFormatReturnsArray( $stats, $format ) {
		$this->assertIsArray(
			StatsFormatter::format( $stats, $format )
		);
	}

	public function formatStringProvider() {
		return [
			[
				[ 'Foo' => 1, 'Bar' => 1 ],
				StatsFormatter::FORMAT_PLAIN,
			],
			[
				[ 'Foo' => 1, 'Bar' => 1 ],
				StatsFormatter::FORMAT_HTML,
			],
			[
				[ 'Foo' => 1, 'Bar' => 1 ],
				StatsFormatter::FORMAT_JSON,
			],
		];
	}

	public function formatArrayProvider() {
		return [
			[
				[ 'Foo' => 1, 'Bar' => 1 ],
				null,
			],
		];
	}

	public function statsProvider() {
		$provider[] = [
			[ 'Foo' => 1, 'Bar' => 1 ],
			[
				'Foo' => 1,
				'Bar' => 1
			]
		];

		$provider[] = [
			[ 'Foo.foobar' => 1, 'Bar' => 1 ],
			[
				'Foo' => [ 'foobar' => 1 ],
				'Bar' => 1
			]
		];

		$provider[] = [
			[ 'Foo.foobar' => 5, 'Bar' => 1, 'Foo.foobar.baz' => 1 ],
			[
				'Foo' => [ 'foobar' => [ 5, 'baz' => 1 ] ],
				'Bar' => 1
			]
		];

		return $provider;
	}

}
