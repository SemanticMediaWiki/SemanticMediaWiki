<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\Time\Components;
use SMW\DataValues\ValueParsers\TimeValueParser;

/**
 * @covers \SMW\DataValues\ValueParsers\TimeValueParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TimeValueParserTest extends \PHPUnit_Framework_TestCase {

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

	public function valueProvider() {

		yield [
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
		yield [
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

		yield '2002-11-01T00:00:00.000-0800' => [
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

		yield '2018-10-11 18:23:59 +0200' => [
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
	}

}
