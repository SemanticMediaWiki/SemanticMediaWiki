<?php

namespace SMW\Tests;

use SMW\IntlTimeFormatter;
use Language;
use SMWDITime as DITime;

/**
 * @covers \SMW\IntlTimeFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class IntlTimeFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$dataItem = $this->getMockBuilder( '\SMWDITime' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\IntlTimeFormatter',
			new IntlTimeFormatter( $dataItem )
		);
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $serialization, $formatOption, $expected ) {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new IntlTimeFormatter(
			DITime::doUnserialize( $serialization ),
			$language
		);

		$this->assertEquals(
			$expected,
			$instance->format( $formatOption )
		);
	}

	/**
	 * @dataProvider localizedFormatProvider
	 */
	public function testGetLocalizedFormat( $serialization, $languageCode, $expected ) {

		$instance = new IntlTimeFormatter(
			DITime::doUnserialize( $serialization ),
			Language::factory( $languageCode )
		);

		$this->assertEquals(
			$expected,
			$instance->getLocalizedFormat()
		);
	}

	public function testContainsValidDateFormatRule() {

		$formatOption = 'F Y/m/d H:i:s';

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new IntlTimeFormatter(
			DITime::doUnserialize( '1/2000/12/12/1/1/20.200' ),
			$language
		);

		$this->assertTrue(
			$instance->containsValidDateFormatRule( $formatOption )
		);
	}

	public function testFormatWithLocalizedMonthReplacement() {

		// F - A full textual representation of a month, such as January or March
		$formatOption = 'F Y/m/d H:i:s';

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$language->expects( $this->once() )
			->method( 'getMonthName' )
			->with( $this->equalTo( '12' ) )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new IntlTimeFormatter(
			DITime::doUnserialize( '1/2000/12/12/1/1/20.200' ),
			$language
		);

		$this->assertEquals(
			'Foo 2000/12/12 01:01:20',
			$instance->format( $formatOption )
		);
	}

	public function formatProvider() {

		#0
		$provider[] = array(
			'1/2000/12/12/1/1/20/200',
			'Y/m/d H:i:s',
			'2000/12/12 01:01:20'
		);

		#1
		$provider[] = array(
			'2/2000/12/12/1/1/20/200',
			'Y/m/d H:i:s',
			'2000/12/12 01:01:20'
		);

		#2
		$provider[] = array(
			'1/2000/12/12/1/1/20.200',
			'Y/m/d H:i:s.u',
			'2000/12/12 01:01:20.200000'
		);

		#3
		$provider[] = array(
			'2/1300/11/02/12/03/25.888499949',
			'Y-m-d H:i:s.u',
			'1300-11-02 12:03:25.888500'
		);

		#4 time alone doesn't require a calendar model
		$provider[] = array(
			'2/1300/11/02/12/03/25.888499949',
			'H:i:s.u',
			'12:03:25.888500'
		);

		return $provider;
	}

	public function localizedFormatProvider() {

		#0
		$provider[] = array(
			'1/2000/12/12/1/1/20/200',
			'en',
			'01:01:20, 12 December 2000'
		);

		#1
		$provider[] = array(
			'1/2000/12/12/1/1/20/200',
			'ja',
			'2000年12月12日 (月) 01:01:20'
		);

		#2
		$provider[] = array(
			'1/2000/12/12/1/1/20/200',
			'es',
			'01:01:20 12 dic 2000'
		);

		return $provider;
	}
}
