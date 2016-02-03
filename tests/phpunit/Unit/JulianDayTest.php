<?php

namespace SMW\Tests;

use SMW\JulianDay;
use SMWDITime as DITime;

/**
 * @covers \SMW\JulianDay
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
	public function testConvert( $seralization, $jdValue ) {

		$this->assertEquals(
			$jdValue,
			JulianDay::get( DITime::doUnserialize( $seralization ) )
		);

		$this->assertEquals(
			DITime::doUnserialize( $seralization ),
			JulianDay::newDiFromJD( $jdValue )
		);
	}

	public function valueProvider() {

		$provider[] = array(
			'2/1352/01/01',
			'2214875.500000'
		);

		$provider[] = array(
			'1/2100/10/04',
			'2488345.500000'
		);

		$provider[] = array(
			'1/2100/10/04',
			'2488345.500000'
		);

		$provider[] = array(
			'2/1582/10/04',
			'2299159.500000'
		);

		$provider[] = array(
			'1/1582/10/15',
			'2299160.5'
		);

		$provider[] = array(
			'2/-900/10/4',
			'1392974.500000'
		);

		$provider[] = array(
			'2/-4713/01/02',
			'0.5'
		);

		$provider[] = array(
			'2/-4713/01/02/12/0/0',
			'1'
		);

		$provider[] = array(
			'2/-9000/10/4',
			'-1565550.5'
		);

		$provider[] = array(
			'1/2100/10/4/13/55/55',
			'2488346.0804977'
		);

		$provider[] = array(
			'1/2100/10/4/13/55/55',
			2488346.0804977
		);

		return $provider;
	}

}
