<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\ValueFormatters\TimeValueFormatter;
use SMW\DataValues\ValueParsers\TimeValueParser;
use SMWTimeValue as TimeValue;
use SMW\Tests\PHPUnitCompat;

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

	use PHPUnitCompat;

	private $dataValueServiceFactory;

	protected function setUp() {
		parent::setUp();

		$constraintValueValidator = $this->getMockBuilder( '\SMW\DataValues\ValueValidators\ConstraintValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->will( $this->returnValue( new TimeValueParser() ) );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->will( $this->returnValue( $constraintValueValidator ) );
	}

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
			$instance->getOption( 'Foo' )
		);
	}

	public function testToUseCaptionOutput() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

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
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

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
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2000' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2000-01-01',
			$instance->getISO8601Date( true )
		);

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2000-02-23 12:02' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2000-02-23T12:02:00',
			$instance->getISO8601Date( true )
		);
	}

	public function testGetISO8601DateForMaxDefault() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2000' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2000-12-31',
			$instance->getISO8601Date( false )
		);

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2000-02-23 12:02' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2000-02-23T12:02:00',
			$instance->getISO8601Date( false )
		);
	}

	public function testGetCaptionFromDataItemForPositiveYearWithEraMarker() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2000 AD' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'AD 2000',
			$instance->getCaptionFromDataItem( $timeValue->getDataItem() )
		);
	}

	public function testLeapYear() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

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
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2016-02' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2016-02-29',
			$instance->getISO8601Date( false )
		);

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2015-02' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2015-02-28',
			$instance->getISO8601Date( false )
		);
	}

	public function testInvalidLeapYear() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2015-02-29' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testMediaWikiDate_WithDifferentLanguage() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

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
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2015-02-28' );

		$timeValue->setOption( TimeValue::OPT_USER_LANGUAGE, 'en' );
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

	public function testLOCLOutputFormatWithSpecificAnnotatedLanguage() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2015-02-28' );

		$timeValue->setOption( TimeValue::OPT_USER_LANGUAGE, 'en' );
		$timeValue->setOutputFormat( 'LOCL@ja' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2015年2月28日 (土)',
			$instance->getLocalizedFormat( $timeValue->getDataItem() )
		);
	}

	public function testLOCLOutputFormatWithTimeZone() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2015-02-28 12:12:00 A' );

		$timeValue->setOption( TimeValue::OPT_USER_LANGUAGE, 'en' );
		$timeValue->setOutputFormat( 'LOCL#TZ' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'12:12:00 A, 28 February 2015',
			$instance->format( TimeValueFormatter::HTML_LONG )
		);

		$this->assertEquals(
			'2015-02-28 12:12:00 A',
			$instance->format( TimeValueFormatter::WIKI_SHORT )
		);
	}

	public function testLOCLOutputFormatWithTimeZoneOnSpecificAnnotatedLanguage() {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2015-02-28 12:12:00 A' );

		$timeValue->setOption( TimeValue::OPT_USER_LANGUAGE, 'en' );
		$timeValue->setOutputFormat( 'LOCL@ja#TZ' );

		$instance = new TimeValueFormatter( $timeValue );

		$this->assertEquals(
			'2015年2月28日 (土) 12:12:00 A',
			$instance->format( TimeValueFormatter::HTML_LONG )
		);

		$this->assertEquals(
			'2015-02-28 12:12:00 A',
			$instance->format( TimeValueFormatter::WIKI_SHORT )
		);
	}

	public function timeInputProvider() {

		#0
		$provider[] = [
			'2000',
			TimeValueFormatter::VALUE,
			'',
			null,
			'',
			'2000'
		];

		#1
		$provider[] = [
			'2000',
			TimeValueFormatter::VALUE,
			'ISO',
			null,
			'',
			'2000'
		];

		#2
		$provider[] = [
			'2000',
			TimeValueFormatter::WIKI_SHORT,
			'ISO',
			null,
			'',
			'2000'
		];

		#3
		$provider[] = [
			'2000',
			TimeValueFormatter::HTML_SHORT,
			'ISO',
			null,
			'',
			'2000'
		];

		#4
		$provider[] = [
			'2000',
			TimeValueFormatter::WIKI_LONG,
			'ISO',
			null,
			'',
			'2000-01-01'
		];

		#5
		$provider[] = [
			'2000',
			TimeValueFormatter::HTML_LONG,
			'ISO',
			null,
			'',
			'2000-01-01'
		];

		#6
		$provider[] = [
			'2000',
			TimeValueFormatter::WIKI_LONG,
			'MEDIAWIKI',
			null,
			'',
			'2000'
		];

		#7
		$provider[] = [
			'2000',
			TimeValueFormatter::HTML_LONG,
			'MEDIAWIKI',
			null,
			'',
			'2000'
		];

		#8
		$provider[] = [
			'2000-02',
			TimeValueFormatter::VALUE,
			'',
			null,
			'',
			'2000-02'
		];

		#9
		$provider[] = [
			'2000-02',
			TimeValueFormatter::VALUE,
			'ISO',
			null,
			'',
			'2000-02'
		];

		#10
		$provider[] = [
			'2000-02',
			TimeValueFormatter::WIKI_SHORT,
			'',
			null,
			'',
			'2000-02'
		];

		#11
		$provider[] = [
			'2000-02',
			TimeValueFormatter::HTML_SHORT,
			'ISO',
			null,
			'',
			'2000-02'
		];

		#12
		$provider[] = [
			'2000-02',
			TimeValueFormatter::WIKI_LONG,
			'ISO',
			null,
			'',
			'2000-02-01'
		];

		#13
		$provider[] = [
			'2000-02',
			TimeValueFormatter::HTML_LONG,
			'ISO',
			null,
			'',
			'2000-02-01'
		];

		#14
		$provider[] = [
			'2000-02',
			TimeValueFormatter::HTML_LONG,
			'LOCL',
			null,
			'en',
			'February 2000'
		];

		return $provider;
	}

}
