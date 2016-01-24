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
	public function testFormat( $timeUserValue, $type, $format, $linker, $expected ) {

		$timeValue = new TimeValue( '_dat' );
		$timeValue->setUserValue( $timeUserValue );
		$timeValue->setOutputFormat( $format );

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

	public function timeInputProvider() {

		$provider[] = array(
			'2000',
			TimeValueFormatter::VALUE,
			'',
			null,
			'2000'
		);

		$provider[] = array(
			'2000',
			TimeValueFormatter::VALUE,
			'ISO',
			null,
			'2000'
		);

		$provider[] = array(
			'2000',
			TimeValueFormatter::WIKI_SHORT,
			'ISO',
			null,
			'2000'
		);

		$provider[] = array(
			'2000',
			TimeValueFormatter::HTML_SHORT,
			'ISO',
			null,
			'2000'
		);

		$provider[] = array(
			'2000',
			TimeValueFormatter::WIKI_LONG,
			'ISO',
			null,
			'2000-01-01'
		);

		$provider[] = array(
			'2000',
			TimeValueFormatter::HTML_LONG,
			'ISO',
			null,
			'2000-01-01'
		);

		$provider[] = array(
			'2000',
			TimeValueFormatter::WIKI_LONG,
			'MEDIAWIKI',
			null,
			'2000'
		);

		$provider[] = array(
			'2000',
			TimeValueFormatter::HTML_LONG,
			'MEDIAWIKI',
			null,
			'2000'
		);

		$provider[] = array(
			'2000-02',
			TimeValueFormatter::VALUE,
			'',
			null,
			'2000-02'
		);

		$provider[] = array(
			'2000-02',
			TimeValueFormatter::VALUE,
			'ISO',
			null,
			'2000-02'
		);

		$provider[] = array(
			'2000-02',
			TimeValueFormatter::WIKI_SHORT,
			'',
			null,
			'2000-02'
		);

		$provider[] = array(
			'2000-02',
			TimeValueFormatter::HTML_SHORT,
			'ISO',
			null,
			'2000-02'
		);

		$provider[] = array(
			'2000-02',
			TimeValueFormatter::WIKI_LONG,
			'ISO',
			null,
			'2000-02-01'
		);

		$provider[] = array(
			'2000-02',
			TimeValueFormatter::HTML_LONG,
			'ISO',
			null,
			'2000-02-01'
		);

		return $provider;
	}

}
