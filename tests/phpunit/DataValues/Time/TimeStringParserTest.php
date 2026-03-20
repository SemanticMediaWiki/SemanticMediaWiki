<?php

namespace SMW\Tests\DataValues\Time;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\Time\TimeStringParser;

/**
 * @covers \SMW\DataValues\Time\TimeStringParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0
 */
class TimeStringParserTest extends TestCase {

	/**
	 * @dataProvider timeStringProvider
	 */
	public function testParseTimeString( string $input, array|false $expected ): void {
		$this->assertEquals( $expected, TimeStringParser::parseTimeString( $input ) );
	}

	public static function timeStringProvider(): iterable {
		yield 'simple time' => [
			'13:45',
			[ 'hours' => 13, 'minutes' => 45, 'seconds' => false, 'timeoffset' => false ],
		];

		yield 'time with seconds' => [
			'13:45:23',
			[ 'hours' => 13, 'minutes' => 45, 'seconds' => 23, 'timeoffset' => false ],
		];

		yield 'time with negative offset' => [
			'13:45:23-3',
			[ 'hours' => 13, 'minutes' => 45, 'seconds' => 23, 'timeoffset' => -3 ],
		];

		yield 'time with positive offset' => [
			'13:45:23+5',
			[ 'hours' => 13, 'minutes' => 45, 'seconds' => 23, 'timeoffset' => 5 ],
		];

		yield 'time with half-hour offset' => [
			'13:45:23+5:30',
			[ 'hours' => 13, 'minutes' => 45, 'seconds' => 23, 'timeoffset' => 5.5 ],
		];

		yield 'time with T prefix' => [
			'T13:45:23',
			[ 'hours' => 13, 'minutes' => 45, 'seconds' => 23, 'timeoffset' => false ],
		];

		yield 'midnight' => [
			'00:00',
			[ 'hours' => 0, 'minutes' => 0, 'seconds' => false, 'timeoffset' => false ],
		];

		yield 'hour 24 with zero minutes' => [
			'24:00',
			[ 'hours' => 24, 'minutes' => 0, 'seconds' => false, 'timeoffset' => false ],
		];

		yield 'invalid — not a time string' => [
			'hello',
			false,
		];

		yield 'invalid — hour 25' => [
			'25:00',
			false,
		];

		yield 'invalid — hour 24 with non-zero minutes' => [
			'24:30',
			false,
		];
	}

	/**
	 * @dataProvider milTimeStringProvider
	 */
	public function testParseMilTimeString( string $input, array|false $expected ): void {
		$this->assertEquals( $expected, TimeStringParser::parseMilTimeString( $input ) );
	}

	public static function milTimeStringProvider(): iterable {
		yield 'simple military time' => [
			'1345',
			[ 'hours' => 13, 'minutes' => 45, 'seconds' => false ],
		];

		yield 'military time with seconds' => [
			'134523',
			[ 'hours' => 13, 'minutes' => 45, 'seconds' => 23 ],
		];

		yield 'midnight military' => [
			'0000',
			[ 'hours' => 0, 'minutes' => 0, 'seconds' => false ],
		];

		yield 'hour 24 with zero minutes' => [
			'2400',
			[ 'hours' => 24, 'minutes' => 0, 'seconds' => false ],
		];

		yield 'invalid — not military time' => [
			'hello',
			false,
		];

		yield 'invalid — hour 25' => [
			'2500',
			false,
		];

		yield 'invalid — hour 24 with non-zero minutes' => [
			'2430',
			false,
		];

		yield 'invalid — too short' => [
			'13',
			false,
		];
	}

}
