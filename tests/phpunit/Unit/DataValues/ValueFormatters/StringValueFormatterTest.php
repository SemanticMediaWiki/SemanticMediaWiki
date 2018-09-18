<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\StringValue;
use SMW\DataValues\ValueFormatters\StringValueFormatter;
use SMW\Tests\PHPUnitCompat;

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

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			StringValueFormatter::class,
			new StringValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {

		$stringValue = $this->getMockBuilder( '\SMW\DataValues\StringValue' )
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

		$instance = new StringValueFormatter();

		$this->assertEquals(
			'ABC[<>]',
			$instance->format( $stringValue, [ StringValueFormatter::WIKI_SHORT ] )
		);

		$this->assertEquals(
			'ABC[&lt;&gt;]',
			$instance->format( $stringValue, [ StringValueFormatter::HTML_SHORT ] )
		);
	}

	/**
	 * @dataProvider stringValueProvider
	 */
	public function testFormat( $stringUserValue, $type, $linker, $expected ) {

		$stringValue = new StringValue( '_txt' );
		$stringValue->setUserValue( $stringUserValue );

		$instance = new StringValueFormatter();

		$this->assertEquals(
			$expected,
			$instance->format( $stringValue, [ $type, $linker ] )
		);
	}

	public function testFormatWithReducedLength() {

		// > 255 / Reduced length
		$text = 'Lorem ipsum dolor sit amet consectetuer justo Nam quis lobortis vel. Sapien nulla enim Lorem enim pede ' .
		'lorem nulla justo diam wisi. Libero Nam turpis neque leo scelerisque nec habitasse a lacus mattis. Accumsan ' .
		'tincidunt Sed adipiscing nec facilisis tortor Nunc Sed ipsum tellus';

		$expected = 'Lorem ipsum dolor sit amet consectetuer …';

		$stringValue = new StringValue( '_txt' );
		$stringValue->setUserValue( $text );
		$stringValue->setOutputFormat( 40 );

		$instance = new StringValueFormatter();

		$this->assertEquals(
			$expected,
			$instance->format(  $stringValue, [ StringValueFormatter::HTML_LONG ] )
		);

		$this->assertEquals(
			$expected,
			$instance->format(  $stringValue, [ StringValueFormatter::WIKI_SHORT ] )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {

		$instance = new StringValueFormatter();

		$this->setExpectedException( 'RuntimeException' );
		$instance->format( StringValueFormatter::VALUE );
	}

	public function stringValueProvider() {

		$provider[] = [
			'foo',
			StringValueFormatter::VALUE,
			null,
			'foo'
		];

		$provider[] = [
			'foo',
			StringValueFormatter::WIKI_SHORT,
			null,
			'foo'
		];

		$provider[] = [
			'foo',
			StringValueFormatter::HTML_SHORT,
			null,
			'foo'
		];

		$provider[] = [
			'foo',
			StringValueFormatter::WIKI_LONG,
			null,
			'foo'
		];

		$provider[] = [
			'foo',
			StringValueFormatter::HTML_LONG,
			null,
			'foo'
		];

		// > 255
		$text = 'Lorem ipsum dolor sit amet consectetuer justo Nam quis lobortis vel. Sapien nulla enim Lorem enim pede ' .
		'lorem nulla justo diam wisi. Libero Nam turpis neque leo scelerisque nec habitasse a lacus mattis. Accumsan ' .
		'tincidunt Sed adipiscing nec facilisis tortor Nunc Sed ipsum tellus';

		$provider[] = [
			$text,
			StringValueFormatter::HTML_LONG,
			null,
			'Lorem ipsum dolor sit amet consectetuer ju <span class="smwwarning">…</span> nec facilisis tortor Nunc Sed ipsum tellus'
		];

		$provider[] = [
			$text,
			StringValueFormatter::WIKI_LONG,
			null,
			'Lorem ipsum dolor sit amet consectetuer ju <span class="smwwarning">…</span> nec facilisis tortor Nunc Sed ipsum tellus'
		];

		// Avoid breaking links
		$text = 'Lorem ipsum dolor sit amet consectetuer [[justo Nam quis lobortis vel]]. Sapien nulla enim Lorem enim pede ' .
		'lorem nulla justo diam wisi. Libero Nam turpis neque leo scelerisque nec habitasse a lacus mattis. Accumsan ' .
		'tincidunt [[Sed adipiscing nec]] facilisis tortor Nunc Sed ipsum tellus';

		$provider[] = [
			$text,
			StringValueFormatter::HTML_LONG,
			null,
			'Lorem ipsum dolor sit amet consectetuer [[justo Nam quis lobortis vel]] <span class="smwwarning">…</span> [[Sed adipiscing nec]] facilisis tortor Nunc Sed ipsum tellus'
		];

		// XMLContentEncode
		$provider[] = [
			'<foo>',
			StringValueFormatter::HTML_LONG,
			null,
			'&lt;foo&gt;'
		];

		$provider[] = [
			'<foo>',
			StringValueFormatter::HTML_SHORT,
			null,
			'&lt;foo&gt;'
		];

		$provider[] = [
			'*Foo',
			StringValueFormatter::WIKI_LONG,
			null,
			"\n" . '*Foo' . "\n"
		];

		$provider[] = [
			'#Foo',
			StringValueFormatter::WIKI_LONG,
			null,
			"\n" . '#Foo' . "\n"
		];

		$provider[] = [
			':Foo',
			StringValueFormatter::WIKI_LONG,
			null,
			"\n" . ':Foo' . "\n"
		];

		$provider[] = [
			'* Foo',
			StringValueFormatter::HTML_LONG,
			null,
			"\n" . '* Foo' . "\n"
		];

		return $provider;
	}

}
