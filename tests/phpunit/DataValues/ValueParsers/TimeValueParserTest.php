<?php

namespace SMW\Tests\DataValues\ValueParsers;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\Time\Components;
use SMW\DataValues\ValueParsers\TimeValueParser;

/**
 * @covers \SMW\DataValues\ValueParsers\TimeValueParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TimeValueParserTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TimeValueParser::class,
			new TimeValueParser()
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testParse( $value, $expected, $errors ) {
		$instance = new TimeValueParser();

		$this->assertEquals(
			new Components( $expected ),
			$instance->parse( $value )
		);

		$this->assertEquals(
			$errors,
			$instance->getErrors()
		);
	}

	public static function valueProvider(): iterable {
		yield 'simple date' => [
			'1 Jan 1970',
			[
				'value' => '1 Jan 1970',
				'datecomponents' => [
					'1', 'Jan', '1970'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		// JD value
		yield 'julian day number' => [
			'2458119.500000',
			[
				'value' => '2458119.500000',
				'datecomponents' => [
					'2458119', '500000'
				],
				'calendarmodel' => 'JD',
				'era' => false,
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'ISO 8601 with negative offset' => [
			'2002-11-01T00:00:00.000-0800',
			[
				'value' => '2002-11-01T00:00:00.000-0800',
				'datecomponents' => [
					'2002', '-', '11', '-', '01'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 8,
				'minutes' => 0,
				'seconds' => 0,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'datetime with positive offset' => [
			'2018-10-11 18:23:59 +0200',
			[
				'value' => '2018-10-11 18:23:59 +0200',
				'datecomponents' => [
					'2018', '-', '10', '-', '11'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 16,
				'minutes' => 23,
				'seconds' => 59,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with time' => [
			'12 May 2007 13:45',
			[
				'value' => '12 May 2007 13:45',
				'datecomponents' => [
					'12', 'May', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 13,
				'minutes' => 45,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with AD era' => [
			'12 May 2007 AD',
			[
				'value' => '12 May 2007 AD',
				'datecomponents' => [
					'12', 'May', '2007'
				],
				'calendarmodel' => false,
				'era' => '+',
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with BC era' => [
			'1 Jan 500 BC',
			[
				'value' => '1 Jan 500 BC',
				'datecomponents' => [
					'1', 'Jan', '500'
				],
				'calendarmodel' => false,
				'era' => '-',
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with Julian calendar model' => [
			'1 Jan 1500 Jl',
			[
				'value' => '1 Jan 1500 Jl',
				'datecomponents' => [
					'1', 'Jan', '1500'
				],
				'calendarmodel' => 'Jl',
				'era' => false,
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with pm time' => [
			'12 May 2007 3:00 pm',
			[
				'value' => '12 May 2007 3:00 pm',
				'datecomponents' => [
					'12', 'May', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 15,
				'minutes' => 0,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with am time noon edge case' => [
			'12 May 2007 12:00 am',
			[
				'value' => '12 May 2007 12:00 am',
				'datecomponents' => [
					'12', 'May', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 0,
				'minutes' => 0,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'numeric date with dashes' => [
			'2007-05-12',
			[
				'value' => '2007-05-12',
				'datecomponents' => [
					'2007', '-', '05', '-', '12'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with time and timezone' => [
			'12 May 2007 13:45 EST',
			[
				'value' => '12 May 2007 13:45 EST',
				'datecomponents' => [
					'12', 'May', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 13,
				'minutes' => 45,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => -5,
				'timezone' => '28'
			],
			[]
		];

		yield 'date with Gregorian calendar model' => [
			'1 Jan 1500 GR',
			[
				'value' => '1 Jan 1500 GR',
				'datecomponents' => [
					'1', 'Jan', '1500'
				],
				'calendarmodel' => 'GR',
				'era' => false,
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with BCE era' => [
			'1 Jan 500 BCE',
			[
				'value' => '1 Jan 500 BCE',
				'datecomponents' => [
					'1', 'Jan', '500'
				],
				'calendarmodel' => false,
				'era' => '-',
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with CE era' => [
			'1 Jan 500 CE',
			[
				'value' => '1 Jan 500 CE',
				'datecomponents' => [
					'1', 'Jan', '500'
				],
				'calendarmodel' => false,
				'era' => '+',
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with ordinal suffix' => [
			'1st Jan 2007',
			[
				'value' => '1st Jan 2007',
				'datecomponents' => [
					'd1', 'Jan', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with pm noon edge case' => [
			'12 May 2007 12:00 pm',
			[
				'value' => '12 May 2007 12:00 pm',
				'datecomponents' => [
					'12', 'May', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 12,
				'minutes' => 0,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with time seconds and offset' => [
			'12 May 2007 13:45:30-3:30',
			[
				'value' => '12 May 2007 13:45:30-3:30',
				'datecomponents' => [
					'12', 'May', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 13,
				'minutes' => 45,
				'seconds' => 30,
				'microseconds' => false,
				'timeoffset' => -2.5,
				'timezone' => false
			],
			[]
		];

		yield 'date with slash separators' => [
			'12/05/2007',
			[
				'value' => '12/05/2007',
				'datecomponents' => [
					'12', '-', '05', '-', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with military timezone' => [
			'1 Jan 2007 1345Z',
			[
				'value' => '1 Jan 2007 1345Z',
				'datecomponents' => [
					'1', 'Jan', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 13,
				'minutes' => 45,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => '1'
			],
			[]
		];

		yield 'date with dot separators (German style)' => [
			'12.05.2007',
			[
				'value' => '12.05.2007',
				'datecomponents' => [
					'12', '05', '2007'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => false,
				'minutes' => false,
				'seconds' => false,
				'microseconds' => false,
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];

		yield 'date with time and milliseconds' => [
			'1 Jan 2200 12:00:00.100',
			[
				'value' => '1 Jan 2200 12:00:00.100',
				'datecomponents' => [
					'1', 'Jan', '2200'
				],
				'calendarmodel' => false,
				'era' => false,
				'hours' => 12,
				'minutes' => 0,
				'seconds' => 0,
				'microseconds' => '100',
				'timeoffset' => 0,
				'timezone' => false
			],
			[]
		];
	}

	/**
	 * @dataProvider errorProvider
	 */
	public function testParseErrors( string $value ): void {
		$instance = new TimeValueParser();
		$result = $instance->parse( $value );

		$this->assertFalse( $result );
		$this->assertNotEmpty( $instance->getErrors() );
	}

	public static function errorProvider(): iterable {
		yield 'invalid am/pm — hours > 12' => [
			'12 May 2007 13:00 pm',
		];

		yield 'invalid am/pm — hours == 0' => [
			'12 May 2007 0:00 pm',
		];
	}

	public function testClearErrors(): void {
		$instance = new TimeValueParser();

		// Parse with invalid am/pm to produce errors
		$instance->parse( '12 May 2007 13:00 pm' );
		$this->assertNotEmpty( $instance->getErrors() );

		$instance->clearErrors();
		$this->assertSame( [], $instance->getErrors() );
	}

	public function testParseResetsErrorsBetweenCalls(): void {
		$instance = new TimeValueParser();

		// First parse produces errors
		$instance->parse( '12 May 2007 13:00 pm' );
		$this->assertNotEmpty( $instance->getErrors() );

		// Second parse resets errors
		$instance->parse( '1 Jan 1970' );
		$this->assertSame( [], $instance->getErrors() );
	}

}
