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

	}

}
