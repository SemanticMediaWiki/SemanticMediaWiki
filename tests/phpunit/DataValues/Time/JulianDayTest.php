<?php

namespace SMW\Tests\DataValues\Time;

use SMW\DataValues\Time\JulianDay;

/**
 * @covers \SMW\DataValues\Time\JulianDay
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class JulianDayTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider valueProvider
	 */
	public function testConvert( $calendarModel, $seralization, $jdValue ) {

		list( $year, $month, $day, $hour, $minute, $second ) = explode( '/', $seralization );

		$this->assertEquals(
			$jdValue,
			JulianDay::getJD( $calendarModel, $year, $month, $day, $hour, $minute, $second )
		);
	}

	public function testGetJD_Issue2454() {

		$offset = -4 / 24;

		$this->assertSame(
			2457869.3333333,
			JulianDay::getJD( 1, 2017, 4, 25, 20, 0, 0 )
		);

		$this->assertNotSame(
			2457869.5,
			JulianDay::getJD( 1, 2017, 4, 25, 20, 0, 0 ) - $offset // returns 2457869.4999999665
		);

		$this->assertSame(
			2457869.5,
			JulianDay::format( JulianDay::getJD( 1, 2017, 4, 25, 20, 0, 0 ) - $offset )
		);
	}

	public function valueProvider() {

		$provider[] = [
			JulianDay::CM_JULIAN,
			'1352/01/01/0/0/0',
			'2214875.500000'
		];

		$provider[] = [
			JulianDay::CM_GREGORIAN,
			'2100/10/04/0/0/0',
			'2488345.500000'
		];

		$provider[] = [
			JulianDay::CM_GREGORIAN,
			'2100/10/04/0/0/0',
			'2488345.500000'
		];

		$provider[] = [
			JulianDay::CM_JULIAN,
			'1582/10/04/0/0/0',
			'2299159.500000'
		];

		$provider[] = [
			JulianDay::CM_GREGORIAN,
			'1582/10/15/0/0/0',
			'2299160.5'
		];

		$provider[] = [
			JulianDay::CM_JULIAN,
			'-900/10/4/0/0/0',
			'1392974.500000'
		];

		$provider[] = [
			JulianDay::CM_JULIAN,
			'-4713/01/02/0/0/0',
			'0.5'
		];

		$provider[] = [
			JulianDay::CM_JULIAN,
			'-4713/01/02/12/0/0/0',
			'1'
		];

		$provider[] = [
			JulianDay::CM_JULIAN,
			'-9000/10/4/0/0/0',
			'-1565550.5'
		];

		$provider[] = [
			JulianDay::CM_GREGORIAN,
			'2100/10/4/13/55/55',
			'2488346.0804977'
		];

		$provider[] = [
			JulianDay::CM_GREGORIAN,
			'2100/10/4/13/55/55',
			2488346.0804977
		];

		return $provider;
	}

}
