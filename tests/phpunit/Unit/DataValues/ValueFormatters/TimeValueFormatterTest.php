<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\ValueFormatters\TimeValueFormatter;
use SMWTimeValue as TimeValue;

/**
 * @covers \SMW\DataValues\ValueFormatters\TimeValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class TimeValueFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\TimeValueFormatter',
			new TimeValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {

		$timeValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TimeValueFormatter();

		$this->assertTrue(
			$instance->isFormatterFor( $timeValue )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {

		$instance = new TimeValueFormatter();

		$this->setExpectedException( 'RuntimeException' );
		$instance->format( TimeValueFormatter::VALUE );
	}

	public function testSetGetOptionValue() {

		$instance = new TimeValueFormatter();
		$instance->setOption( 'Foo', 1001 );

		$this->assertEquals(
			1001,
			$instance->getOptionValueFor( 'Foo' )
		);
	}

	public function testToUseCaptionOutput() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setCaption( 'ABC[<>]' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'ABC[<>]',
			$instance->format( TimeValueFormatter::WIKI_SHORT )
		);
	}

	/**
	 * @dataProvider timeInputProvider
	 */
	public function testFormat( $timeUserValue, $type, $format, $linker, $languageCode, $expected ) {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( $timeUserValue );

		$timeValue->setOutputFormat( $format );

		$timeValue->setOption( 'user.language', $languageCode );
		$timeValue->setOption( 'content.language', $languageCode );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);
	}

	public function testGetISO8601DateForMinDefault() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2000' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2000-01-01',
			$instance->getISO8601Date( true )
		);

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2000-02-23 12:02' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2000-02-23T12:02:00',
			$instance->getISO8601Date( true )
		);
	}

	public function testGetISO8601DateForMaxDefault() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2000' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2000-12-31',
			$instance->getISO8601Date( false )
		);

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2000-02-23 12:02' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2000-02-23T12:02:00',
			$instance->getISO8601Date( false )
		);
	}

	public function testGetCaptionFromDataItemForPositiveYearWithEraMarker() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2000 AD' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'AD 2000',
			$instance->getCaptionFromDataItem( $timeValue->getDataItem() )
		);
	}

	public function testLeapYear() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2016-02-29' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEmpty(
			$instance->getErrors()
		);

		$this->assertEquals(
			'2016-02-29',
			$instance->getISO8601Date()
		);

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2016-02' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2016-02-29',
			$instance->getISO8601Date( false )
		);

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2015-02' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2015-02-28',
			$instance->getISO8601Date( false )
		);
	}

	public function testInvalidLeapYear() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2015-02-29' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testMediaWikiDate_WithDifferentLanguage() {

		$timeValue = new TimeValue( '_dat' );

		$timeValue->setUserValue( '2015-02-28' );
		$timeValue->setOption( 'user.language', 'en' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'28 February 2015',
			$instance->getMediaWikiDate()
		);

		$timeValue->setOption( 'user.language', 'ja' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2015年2月28日 (土)',
			$instance->getMediaWikiDate()
		);
	}

	public function testLOCLOutputFormat() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( '2015-02-28' );

		$timeValue->setOption( 'user.language', 'en' );
		$timeValue->setOutputFormat( 'LOCL' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			$instance->format( TimeValueFormatter::WIKI_LONG ),
			$instance->getLocalizedFormat( $timeValue->getDataItem() )
		);

		$this->assertEquals(
			$instance->format( TimeValueFormatter::HTML_LONG ),
			$instance->getLocalizedFormat( $timeValue->getDataItem() )
		);
	}

	public function timeInputProvider() {

		#0
		$provider[] = array(
			'2000',
			TimeValueFormatter::VALUE,
			'',
			null,
			'',
			'2000'
		);

		#1
		$provider[] = array(
			'2000',
			TimeValueFormatter::VALUE,
			'ISO',
			null,
			'',
			'2000'
		);

		#2
		$provider[] = array(
			'2000',
			TimeValueFormatter::WIKI_SHORT,
			'ISO',
			null,
			'',
			'2000'
		);

		#3
		$provider[] = array(
			'2000',
			TimeValueFormatter::HTML_SHORT,
			'ISO',
			null,
			'',
			'2000'
		);

		#4
		$provider[] = array(
			'2000',
			TimeValueFormatter::WIKI_LONG,
			'ISO',
			null,
			'',
			'2000-01-01'
		);

		#5
		$provider[] = array(
			'2000',
			TimeValueFormatter::HTML_LONG,
			'ISO',
			null,
			'',
			'2000-01-01'
		);

		#6
		$provider[] = array(
			'2000',
			TimeValueFormatter::WIKI_LONG,
			'MEDIAWIKI',
			null,
			'',
			'2000'
		);

		#7
		$provider[] = array(
			'2000',
			TimeValueFormatter::HTML_LONG,
			'MEDIAWIKI',
			null,
			'',
			'2000'
		);

		#8
		$provider[] = array(
			'2000-02',
			TimeValueFormatter::VALUE,
			'',
			null,
			'',
			'2000-02'
		);

		#9
		$provider[] = array(
			'2000-02',
			TimeValueFormatter::VALUE,
			'ISO',
			null,
			'',
			'2000-02'
		);

		#10
		$provider[] = array(
			'2000-02',
			TimeValueFormatter::WIKI_SHORT,
			'',
			null,
			'',
			'2000-02'
		);

		#11
		$provider[] = array(
			'2000-02',
			TimeValueFormatter::HTML_SHORT,
			'ISO',
			null,
			'',
			'2000-02'
		);

		#12
		$provider[] = array(
			'2000-02',
			TimeValueFormatter::WIKI_LONG,
			'ISO',
			null,
			'',
			'2000-02-01'
		);

		#13
		$provider[] = array(
			'2000-02',
			TimeValueFormatter::HTML_LONG,
			'ISO',
			null,
			'',
			'2000-02-01'
		);

		#14
		$provider[] = array(
			'2000-02',
			TimeValueFormatter::HTML_LONG,
			'LOCL',
			null,
			'en',
			'February 2000'
		);

		return $provider;
	}

}
