<?php

namespace SMW\Tests;

use SMW\NumberFormatter;

use Language;

/**
 * @covers \SMW\NumberFormatter
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class NumberFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\NumberFormatter',
			new NumberFormatter( 10000 )
		);

		$this->assertInstanceOf(
			'\SMW\NumberFormatter',
			NumberFormatter::getInstance()
		);
	}

	/**
	 * @dataProvider numberProvider
	 */
	public function testFormatNumberToLocalizedText( $maxNonExpNumber, $number, $expected ) {

		$instance = new NumberFormatter( $maxNonExpNumber );

		$this->assertEquals(
			$expected,
			$instance->formatNumberToLocalizedText( $number )
		);
	}

	public function numberProvider() {

		$provider[] = array(
			10000,
			1000,
			'1,000'
		);

		$provider[] = array(
			10000,
			1000.42,
			'1,000.42'
		);

		$provider[] = array(
			10000,
			1000000,
			'1.0e+6'
		);

		$provider[] = array(
			10000000,
			1000000,
			'1,000,000'
		);

		return $provider;
	}

}
