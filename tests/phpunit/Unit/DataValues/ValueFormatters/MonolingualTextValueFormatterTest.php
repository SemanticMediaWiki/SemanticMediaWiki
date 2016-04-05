<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;

/**
 * @covers \SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MonolingualTextValueFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter',
			new MonolingualTextValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {

		$monolingualTextValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new MonolingualTextValueFormatter();

		$this->assertTrue(
			$instance->isFormatterFor( $monolingualTextValue )
		);
	}

	public function testToUseCaptionOutput() {

		$monolingualTextValue = new MonolingualTextValue();
		$monolingualTextValue->setCaption( 'ABC' );

		$instance = new MonolingualTextValueFormatter( $monolingualTextValue );

		$this->assertEquals(
			'ABC',
			$instance->format( MonolingualTextValueFormatter::WIKI_SHORT )
		);

		$this->assertEquals(
			'ABC',
			$instance->format( MonolingualTextValueFormatter::HTML_SHORT )
		);
	}

	/**
	 * @dataProvider stringValueProvider
	 */
	public function testFormat( $stringValue, $type, $linker, $expected ) {

		$monolingualTextValue = new MonolingualTextValue();
		$monolingualTextValue->setUserValue( $stringValue );

		$instance = new MonolingualTextValueFormatter( $monolingualTextValue );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {

		$instance = new MonolingualTextValueFormatter();

		$this->setExpectedException( 'RuntimeException' );
		$instance->format( MonolingualTextValueFormatter::VALUE );
	}

	public function stringValueProvider() {

		$provider[] = array(
			'foo@en',
			MonolingualTextValueFormatter::VALUE,
			null,
			'foo (en)'
		);

		$provider[] = array(
			'foo@en',
			MonolingualTextValueFormatter::WIKI_SHORT,
			null,
			'foo (en)'
		);

		$provider[] = array(
			'foo@en',
			MonolingualTextValueFormatter::HTML_SHORT,
			null,
			'foo (en)'
		);

		$provider[] = array(
			'foo@en',
			MonolingualTextValueFormatter::WIKI_LONG,
			null,
			'foo (en)'
		);

		$provider[] = array(
			'foo@en',
			MonolingualTextValueFormatter::HTML_LONG,
			null,
			'foo (en)'
		);

		return $provider;
	}

}
