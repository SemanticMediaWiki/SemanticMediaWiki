<?php

namespace SMW\Tests\Utils;

use SMW\Utils\StatsFormatter;

/**
 * @covers \SMW\Utils\StatsFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class StatsFormatterTest extends \PHPUnit_Framework_TestCase {

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
	 * @dataProvider formatProvider
	 */
	public function testFormat( $stats, $format, $expected ) {

		$this->assertInternalType(
			$expected,
			StatsFormatter::format( $stats, $format )
		);
	}

	public function formatProvider() {

		$provider[] = [
			[ 'Foo' => 1, 'Bar' => 1 ],
			StatsFormatter::FORMAT_PLAIN,
			'string'
		];

		$provider[] = [
			[ 'Foo' => 1, 'Bar' => 1 ],
			StatsFormatter::FORMAT_HTML,
			'string'
		];

		$provider[] = [
			[ 'Foo' => 1, 'Bar' => 1 ],
			StatsFormatter::FORMAT_JSON,
			'string'
		];

		$provider[] = [
			[ 'Foo' => 1, 'Bar' => 1 ],
			null,
			'array'
		];

		return $provider;
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
