<?php

namespace SMW\Tests\DataValues\Time;

use SMW\DataValues\Time\IntlTimeFormatter;
use SMW\Localizer;
use SMWDITime as DITime;

/**
 * @covers \SMW\DataValues\Time\IntlTimeFormatter
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
			IntlTimeFormatter::class,
			new IntlTimeFormatter( $dataItem )
		);
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $serialization, $languageCode, $formatOption, $expected ) {

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new IntlTimeFormatter(
			DITime::doUnserialize( $serialization ),
			Localizer::getInstance()->getLanguage( $languageCode )
		);

		$this->assertEquals(
			$expected,
			$instance->format( $formatOption )
		);
	}

	/**
	 * @dataProvider localizedFormatProvider
	 */
	public function testGetLocalizedFormat( $serialization, $languageCode, $flag, $expected ) {

		$instance = new IntlTimeFormatter(
			DITime::doUnserialize( $serialization ),
			Localizer::getInstance()->getLanguage( $languageCode )
		);

		$this->assertEquals(
			$expected,
			$instance->getLocalizedFormat( $flag )
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
		$provider[] = [
			'1/2000/12/12/1/1/20/200',
			'en',
			'Y/m/d H:i:s',
			'2000/12/12 01:01:20'
		];

		#1
		$provider[] = [
			'2/2000/12/12/1/1/20/200',
			'en',
			'Y/m/d H:i:s',
			'2000/12/12 01:01:20'
		];

		#2
		$provider[] = [
			'1/2000/12/12/1/1/20.200',
			'en',
			'Y/m/d H:i:s.u',
			'2000/12/12 01:01:20.200000'
		];

		// Skip on HHVM to avoid .888500 vs. .888499 msec @see hhvm#6899
		// https://bugs.php.net/bug.php?id=76822
		if ( !defined( 'HHVM_VERSION' ) && version_compare( PHP_VERSION, '7.2', '<' ) ) {
			#3
			$provider[] = [
				'2/1300/11/02/12/03/25.888499949',
				'en',
				'Y-m-d H:i:s.u',
				'1300-11-02 12:03:25.888500'
			];

			#4 time alone doesn't require a calendar model
			$provider[] = [
				'2/1300/11/02/12/03/25.888499949',
				'en',
				'H:i:s.u',
				'12:03:25.888500'
			];
		}

		#5
		$provider['on monthnumber 12'] = [
			'1/2000/12/12',
			'en',
			'Y-m-d M',
			'2000-12-12 Dec'
		];

		#6
		$provider['on daynumber 7'] = [
			'1/2016/05/08/1/1/20/200',
			'en',
			'Y-m-d D',
			'2016-05-08 Sun'
		];

		#7
		$provider['on timezone 1'] = [
			'1/1970/1/12/11/43/0/14',
			'en',
			'Y-m-d H:i:s T',
			'1970-01-12 11:43:00 UTC'
		];

		return $provider;
	}

	public function localizedFormatProvider() {

		#0
		$provider[] = [
			'1/2000/12/12/1/1/20/200',
			'en',
			IntlTimeFormatter::LOCL_DEFAULT,
			'01:01:20, 12 December 2000'
		];

		#1
		$provider[] = [
			'1/2000/12/12/1/1/20/200',
			'ja',
			IntlTimeFormatter::LOCL_DEFAULT,
			'2000年12月12日 (火) 01:01:20'
		];

		#2
		$provider[] = [
			'1/2000/12/12/1/1/20/200',
			'es',
			IntlTimeFormatter::LOCL_DEFAULT,
			'01:01:20 12 dic 2000'
		];

		#3
		$provider['on daynumber 1'] = [
			'1/2016/05/02/1/1/20/200',
			'ja',
			IntlTimeFormatter::LOCL_DEFAULT,
			'2016年5月2日 (月) 01:01:20'
		];

		#4
		$provider['on daynumber 7'] = [
			'1/2016/05/08/1/1/20/200',
			'ja',
			IntlTimeFormatter::LOCL_DEFAULT,
			'2016年5月8日 (日) 01:01:20'
		];

		#5
		$provider['midnight-ja'] = [
			'1/2016/05/08/00/00/00/00',
			'ja',
			IntlTimeFormatter::LOCL_DEFAULT,
			'2016年5月8日 (日) 00:00:00'
		];

		#6
		$provider['midnight-en'] = [
			'1/2016/05/08/0/0/0/0',
			'en',
			IntlTimeFormatter::LOCL_DEFAULT,
			'00:00:00, 8 May 2016'
		];

		#7
		$provider['after-midnight'] = [
			'1/2016/05/08/0/0/01/0',
			'en',
			IntlTimeFormatter::LOCL_DEFAULT,
			'00:00:01, 8 May 2016'
		];

		#8
		$provider['timezone-short'] = [
			'1/1970/1/12/11/43/0/14',
			'en',
			IntlTimeFormatter::LOCL_TIMEZONE,
			'12:43:00 BST, 12 January 1970'
		];

		#9
		// -'07:43:00 America/Cuiaba, 12 January 1970'
		// +'08:43:00 America/Cuiaba, 12 January 1970'
		// Because of Daylight Saving Time UTC-3/Standard Time UTC-4
		//	$provider['timezone-long'] = array(
		//		'1/1970/1/12/11/43/0/America/Cuiaba',
		//		'en',
		//		IntlTimeFormatter::LOCL_TIMEZONE,
		//		'07:43:00 America/Cuiaba, 12 January 1970'
		//	);

		return $provider;
	}

}
