<?php

namespace SMW\Tests\Unit\DataValues\ValueFormatters;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\DataValue;
use SMW\DataValues\TimeValue;
use SMW\DataValues\ValueFormatters\TimeValueFormatter;
use SMW\DataValues\ValueParsers\TimeValueParser;
use SMW\DataValues\ValueValidators\ConstraintValueValidator;
use SMW\Services\DataValueServiceFactory;

/**
 * @covers \SMW\DataValues\ValueFormatters\TimeValueFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class TimeValueFormatterTest extends TestCase {

	private $dataValueServiceFactory;

	protected function setUp(): void {
		parent::setUp();

		$constraintValueValidator = $this->getMockBuilder( ConstraintValueValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getValueParser' )
			->willReturn( new TimeValueParser() );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->willReturn( $constraintValueValidator );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TimeValueFormatter::class,
			new TimeValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {
		$timeValue = $this->getMockBuilder( TimeValue::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TimeValueFormatter();

		$this->assertTrue(
			$instance->isFormatterFor( $timeValue )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {
		$instance = new TimeValueFormatter();

		$this->expectException( 'RuntimeException' );
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

	public function testHtmlOutputIsWrappedInTimeElementWhileWikiStaysPlain() {
		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory( $this->dataValueServiceFactory );
		$timeValue->setUserValue( '2000-02-23 12:02:00' );

		$instance = new TimeValueFormatter( $timeValue );

		// HTML output carries a machine-readable datetime for assistive technology
		$this->assertStringContainsString(
			'<time datetime="2000-02-23T12:02:00">',
			$instance->format( TimeValueFormatter::HTML_SHORT )
		);
		$this->assertStringContainsString(
			'<time datetime="2000-02-23T12:02:00">',
			$instance->format( TimeValueFormatter::HTML_LONG )
		);

		// Wiki output stays plain so exports and other machine consumers are unaffected
		$this->assertStringNotContainsString(
			'<time',
			$instance->format( TimeValueFormatter::WIKI_SHORT )
		);
		$this->assertStringNotContainsString(
			'<time',
			$instance->format( TimeValueFormatter::WIKI_LONG )
		);
	}

	public function testTimeElementIsOmittedForBceDates() {
		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory( $this->dataValueServiceFactory );
		$timeValue->setUserValue( '300 BC' );

		$instance = new TimeValueFormatter( $timeValue );

		// The HTML <time> datetime attribute cannot represent BCE years, so the
		// caption is left unwrapped rather than emitting an invalid datetime.
		$this->assertStringNotContainsString(
			'<time',
			$instance->format( TimeValueFormatter::HTML_SHORT )
		);
		$this->assertStringNotContainsString(
			'<time',
			$instance->format( TimeValueFormatter::HTML_LONG )
		);
	}

	/**
	 * @testdox getISO8601Date($mindefault): $userValue -> $expected
	 * @testWith [true, "2000", "2000-01-01"]
	 *           [true, "2000-02-23 12:02", "2000-02-23T12:02:00"]
	 *           [true, "2000-02", "2000-02-01"]
	 *           [false, "2000", "2000-12-31"]
	 *           [false, "2000-02-23 12:02", "2000-02-23T12:02:00"]
	 *           [false, "2000-02", "2000-02-29"]
	 *           [false, "1900-02", "1900-02-28"]
	 */
	public function testGetISO8601Date( $mindefault, $userValue, $expected ) {
		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);
		$timeValue->setUserValue( $userValue );
		$instance = new TimeValueFormatter( $timeValue );

		$result = $instance->getISO8601Date( $mindefault );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @testdox getPartialISO8601Date(): $userValue -> $expected
	 * @testWith ["2000-02-23 12:02", "2000-02-23T12:02:00"]
	 *           ["2000-02-23", "2000-02-23"]
	 *           ["2000-02", "2000-02"]
	 *           ["2000", "2000"]
	 */
	public function testPartialGetISO8601Date( $userValue, $expected ) {
		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);
		$timeValue->setUserValue( $userValue );
		$instance = new TimeValueFormatter( $timeValue );

		$result = $instance->getPartialISO8601Date();

		$this->assertEquals( $expected, $result );
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
			'<time datetime="2015-02-28">' . $instance->getLocalizedFormat( $timeValue->getDataItem() ) . '</time>',
			$instance->format( TimeValueFormatter::HTML_LONG )
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
			'<time datetime="2015-02-28T11:12:00">12:12:00 A, 28 February 2015</time>',
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
			'<time datetime="2015-02-28T11:12:00">2015年2月28日 (土) 12:12:00 A</time>',
			$instance->format( TimeValueFormatter::HTML_LONG )
		);

		$this->assertEquals(
			'2015-02-28 12:12:00 A',
			$instance->format( TimeValueFormatter::WIKI_SHORT )
		);
	}

	private function newTimeValue( string $userValue, string $outputFormat, bool $defer ) {
		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory( $this->dataValueServiceFactory );
		$timeValue->setUserValue( $userValue );
		$timeValue->setOutputFormat( $outputFormat );
		$timeValue->setOption( DataValue::OPT_USER_LANGUAGE, 'en' );

		if ( $defer ) {
			$timeValue->setOption( DataValue::OPT_DEFER_LOCAL_TIME, true );
		}

		return $timeValue;
	}

	public function testDeferredLocalTimeEmitsTimeElement() {
		$reset = $GLOBALS['wgLocalTZoffset'] ?? 0;
		$GLOBALS['wgLocalTZoffset'] = 0;

		try {
			$instance = new TimeValueFormatter(
				$this->newTimeValue( '2024-06-01 14:00:00', 'LOCL#TO', true )
			);

			// Derive the expected anchor the same way the formatter does, so the
			// test is robust to the exact ISO time-string format.
			$expectedAnchor = $instance->getISO8601Date() . 'Z';

			$output = $instance->format( TimeValueFormatter::HTML_LONG );

			$this->assertStringContainsString( '<time ', $output );
			$this->assertStringContainsString( 'class="smw-localtime"', $output );
			$this->assertStringContainsString( 'datetime="' . $expectedAnchor . '"', $output );
		} finally {
			$GLOBALS['wgLocalTZoffset'] = $reset;
		}
	}

	public function testNonDeferredLocalTimeHasNoLocaltimeElement() {
		$instance = new TimeValueFormatter(
			$this->newTimeValue( '2024-06-01 14:00:00', 'LOCL#TO', false )
		);

		// Without deferral there is no client-side local-time conversion element.
		// The value still carries the general <time> date markup, so assert on the
		// smw-localtime marker rather than any <time>.
		$this->assertStringNotContainsString(
			'smw-localtime',
			$instance->format( TimeValueFormatter::HTML_LONG )
		);
	}

	public function testDeferredDateOnlyHasNoLocaltimeElement() {
		$instance = new TimeValueFormatter(
			$this->newTimeValue( '2024-06-01', 'LOCL#TO', true )
		);

		// A date without a time component is not deferred for local-time conversion;
		// it still carries the general <time> date markup.
		$this->assertStringNotContainsString(
			'smw-localtime',
			$instance->format( TimeValueFormatter::HTML_LONG )
		);
	}

	public function testBareLOCLFormatRecordsUserLanguageOutput() {
		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2015-02-28' );
		$timeValue->setOption( TimeValue::OPT_USER_LANGUAGE, 'en' );
		$timeValue->setOutputFormat( 'LOCL' );

		$instance = new TimeValueFormatter( $timeValue );

		// A bare LOCL format renders the date in the viewer's interface
		// language, so the output is not cache-stable across languages.
		$instance->getLocalizedFormat( $timeValue->getDataItem() );

		$this->assertTrue(
			$timeValue->hasUserLanguageOutput()
		);
	}

	public function testAnnotatedLOCLFormatDoesNotRecordUserLanguageOutput() {
		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2015-02-28' );
		$timeValue->setOption( TimeValue::OPT_USER_LANGUAGE, 'en' );
		$timeValue->setOutputFormat( 'LOCL@ja' );

		$instance = new TimeValueFormatter( $timeValue );

		// An annotated LOCL format (`LOCL@ja`) renders a fixed language, so the
		// output is cache-stable across languages.
		$instance->getLocalizedFormat( $timeValue->getDataItem() );

		$this->assertFalse(
			$timeValue->hasUserLanguageOutput()
		);
	}

	public function testMediaWikiDateRecordsUserLanguageOutput() {
		$timeValue = new TimeValue( '_dat' );
		$timeValue->setDataValueServiceFactory(
			$this->dataValueServiceFactory
		);

		$timeValue->setUserValue( '2015-02-28' );
		$timeValue->setOption( TimeValue::OPT_USER_LANGUAGE, 'en' );

		$instance = new TimeValueFormatter( $timeValue );

		// The MEDIAWIKI format renders the date in the viewer's interface
		// language, so the output is not cache-stable across languages.
		$instance->getMediaWikiDate();

		$this->assertTrue(
			$timeValue->hasUserLanguageOutput()
		);
	}

	public function timeInputProvider() {
		# 0
		$provider[] = [
			'2000',
			TimeValueFormatter::VALUE,
			'',
			null,
			'',
			'2000'
		];

		# 1
		$provider[] = [
			'2000',
			TimeValueFormatter::VALUE,
			'ISO',
			null,
			'',
			'2000'
		];

		# 2
		$provider[] = [
			'2000',
			TimeValueFormatter::WIKI_SHORT,
			'ISO',
			null,
			'',
			'2000'
		];

		# 3
		$provider[] = [
			'2000',
			TimeValueFormatter::HTML_SHORT,
			'ISO',
			null,
			'',
			'<time datetime="2000">2000</time>'
		];

		# 4
		$provider[] = [
			'2000',
			TimeValueFormatter::WIKI_LONG,
			'ISO',
			null,
			'',
			'2000-01-01'
		];

		# 5
		$provider[] = [
			'2000',
			TimeValueFormatter::HTML_LONG,
			'ISO',
			null,
			'',
			'<time datetime="2000">2000-01-01</time>'
		];

		# 6
		$provider[] = [
			'2000',
			TimeValueFormatter::WIKI_LONG,
			'MEDIAWIKI',
			null,
			'',
			'2000'
		];

		# 7
		$provider[] = [
			'2000',
			TimeValueFormatter::HTML_LONG,
			'MEDIAWIKI',
			null,
			'',
			'<time datetime="2000">2000</time>'
		];

		# 8
		$provider[] = [
			'2000-02',
			TimeValueFormatter::VALUE,
			'',
			null,
			'',
			'2000-02'
		];

		# 9
		$provider[] = [
			'2000-02',
			TimeValueFormatter::VALUE,
			'ISO',
			null,
			'',
			'2000-02'
		];

		# 10
		$provider[] = [
			'2000-02',
			TimeValueFormatter::WIKI_SHORT,
			'',
			null,
			'',
			'2000-02'
		];

		# 11
		$provider[] = [
			'2000-02',
			TimeValueFormatter::HTML_SHORT,
			'ISO',
			null,
			'',
			'<time datetime="2000-02">2000-02</time>'
		];

		# 12
		$provider[] = [
			'2000-02',
			TimeValueFormatter::WIKI_LONG,
			'ISO',
			null,
			'',
			'2000-02-01'
		];

		# 13
		$provider[] = [
			'2000-02',
			TimeValueFormatter::HTML_LONG,
			'ISO',
			null,
			'',
			'<time datetime="2000-02">2000-02-01</time>'
		];

		# 14
		$provider[] = [
			'2000-02',
			TimeValueFormatter::HTML_LONG,
			'LOCL',
			null,
			'en',
			'<time datetime="2000-02">February 2000</time>'
		];

		return $provider;
	}

}
