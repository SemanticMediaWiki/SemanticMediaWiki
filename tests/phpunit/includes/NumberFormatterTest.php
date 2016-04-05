<?php

namespace SMW\Tests;

use Language;
use SMW\NumberFormatter;

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

		$localizer = $this->getMockBuilder( '\SMW\Localizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\NumberFormatter',
			new NumberFormatter( 10000, $localizer )
		);

		$this->assertInstanceOf(
			'\SMW\NumberFormatter',
			NumberFormatter::getInstance()
		);
	}

	/**
	 * @dataProvider numberProvider
	 */
	public function testLocalizedFormattedNumber( $maxNonExpNumber, $number, $expected ) {

		$localizer = $this->getMockBuilder( '\SMW\Localizer' )
			->disableOriginalConstructor()
			->getMock();

		$localizer->expects( $this->any() )
			->method( 'getContentLanguage' )
			->will( $this->returnValue( 'en' ) );

		$localizer->expects( $this->any() )
			->method( 'getUserLanguage' )
			->will( $this->returnValue( 'en' ) );

		$instance = new NumberFormatter( $maxNonExpNumber, $localizer );

		$this->assertEquals(
			$expected,
			$instance->getLocalizedFormattedNumber( $number )
		);
	}

	/**
	 * @dataProvider unformattedNumberByPrecisionProvider
	 */
	public function testGetUnformattedNumberByPrecision( $maxNonExpNumber, $number, $precision, $expected ) {

		$localizer = $this->getMockBuilder( '\SMW\Localizer' )
			->disableOriginalConstructor()
			->getMock();

		$localizer->expects( $this->any() )
			->method( 'getContentLanguage' )
			->will( $this->returnValue( 'en' ) );

		$localizer->expects( $this->any() )
			->method( 'getUserLanguage' )
			->will( $this->returnValue( 'en' ) );

		$instance = new NumberFormatter( $maxNonExpNumber, $localizer );

		$this->assertEquals(
			$expected,
			$instance->getUnformattedNumberByPrecision( $number, $precision )
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

	public function unformattedNumberByPrecisionProvider() {

		$provider[] = array(
			10000,
			1000,
			2,
			'1000.00'
		);

		$provider[] = array(
			10000,
			1000.42,
			3,
			'1000.420'
		);

		$provider[] = array(
			10000,
			1000000,
			0,
			'1000000'
		);

		$provider[] = array(
			10000000,
			1000000,
			2,
			'1000000.00'
		);

		$provider[] = array(
			10000000,
			1000000,
			false,
			'1000000'
		);

		return $provider;
	}

}
