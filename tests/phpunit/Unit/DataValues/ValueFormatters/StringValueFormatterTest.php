<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\ValueFormatters\StringValueFormatter;
use SMWStringValue as StringValue;

/**
 * @covers \SMW\DataValues\ValueFormatters\StringValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class StringValueFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\StringValueFormatter',
			new StringValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {

		$stringValue = $this->getMockBuilder( '\SMWStringValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new StringValueFormatter();

		$this->assertTrue(
			$instance->isFormatterFor( $stringValue )
		);
	}

	public function testToUseCaptionOutput() {

		$stringValue = new StringValue( '_txt' );
		$stringValue->setCaption( 'ABC[<>]' );

		$instance = new StringValueFormatter( $stringValue );

		$this->assertEquals(
			'ABC[<>]',
			$instance->format( StringValueFormatter::WIKI_SHORT )
		);

		$this->assertEquals(
			'ABC[&lt;&gt;]',
			$instance->format( StringValueFormatter::HTML_SHORT )
		);
	}

	/**
	 * @dataProvider stringValueProvider
	 */
	public function testFormat( $stringUserValue, $type, $linker, $expected ) {

		$stringValue = new StringValue( '_txt' );
		$stringValue->setUserValue( $stringUserValue );

		$instance = new StringValueFormatter( $stringValue );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {

		$instance = new StringValueFormatter();

		$this->setExpectedException( 'RuntimeException' );
		$instance->format( StringValueFormatter::VALUE );
	}

	public function stringValueProvider() {

		$provider[] = array(
			'foo',
			StringValueFormatter::VALUE,
			null,
			'foo'
		);

		$provider[] = array(
			'foo',
			StringValueFormatter::WIKI_SHORT,
			null,
			'foo'
		);

		$provider[] = array(
			'foo',
			StringValueFormatter::HTML_SHORT,
			null,
			'foo'
		);

		$provider[] = array(
			'foo',
			StringValueFormatter::WIKI_LONG,
			null,
			'foo'
		);

		$provider[] = array(
			'foo',
			StringValueFormatter::HTML_LONG,
			null,
			'foo'
		);

		// > 255
		$text = 'Lorem ipsum dolor sit amet consectetuer justo Nam quis lobortis vel. Sapien nulla enim Lorem enim pede ' .
		'lorem nulla justo diam wisi. Libero Nam turpis neque leo scelerisque nec habitasse a lacus mattis. Accumsan ' .
		'tincidunt Sed adipiscing nec facilisis tortor Nunc Sed ipsum tellus';

		$provider[] = array(
			$text,
			StringValueFormatter::HTML_LONG,
			null,
			'Lorem ipsum dolor sit amet consectetuer ju <span class="smwwarning">…</span> nec facilisis tortor Nunc Sed ipsum tellus'
		);

		$provider[] = array(
			$text,
			StringValueFormatter::WIKI_LONG,
			null,
			'Lorem ipsum dolor sit amet consectetuer ju <span class="smwwarning">…</span> nec facilisis tortor Nunc Sed ipsum tellus'
		);

		// XMLContentEncode
		$provider[] = array(
			'<foo>',
			StringValueFormatter::HTML_LONG,
			null,
			'&lt;foo&gt;'
		);

		$provider[] = array(
			'<foo>',
			StringValueFormatter::HTML_SHORT,
			null,
			'&lt;foo&gt;'
		);

		$provider[] = array(
			'*Foo',
			StringValueFormatter::WIKI_LONG,
			null,
			"\n" . '*Foo' . "\n"
		);

		$provider[] = array(
			'#Foo',
			StringValueFormatter::WIKI_LONG,
			null,
			"\n" . '#Foo' . "\n"
		);

		$provider[] = array(
			':Foo',
			StringValueFormatter::WIKI_LONG,
			null,
			"\n" . ':Foo' . "\n"
		);

		$provider[] = array(
			'* Foo',
			StringValueFormatter::HTML_LONG,
			null,
			"\n" . '* Foo' . "\n"
		);

		return $provider;
	}

}
