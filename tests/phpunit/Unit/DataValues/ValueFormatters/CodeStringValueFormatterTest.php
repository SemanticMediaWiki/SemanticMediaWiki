<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\ValueFormatters\CodeStringValueFormatter;
use SMWStringValue as StringValue;

/**
 * @covers \SMW\DataValues\ValueFormatters\CodeStringValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class CodeStringValueFormatterTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\CodeStringValueFormatter',
			new CodeStringValueFormatter()
		);
	}

	/**
	 * @dataProvider stringValueProvider
	 */
	public function testFormat( $stringUserValue, $type, $linker, $expected ) {

		$stringValue = new StringValue( '_cod' );
		$stringValue->setUserValue( $stringUserValue );

		$instance = new CodeStringValueFormatter( $stringValue );

		$this->assertEquals(
			$expected,
			$instance->format( $type, $linker )
		);
	}

	public function stringValueProvider() {

		$provider[] = array(
			'foo',
			CodeStringValueFormatter::VALUE,
			null,
			'foo'
		);

		$provider[] = array(
			'foo',
			CodeStringValueFormatter::WIKI_SHORT,
			null,
			'<div class="smwpre">foo</div>'
		);

		$provider[] = array(
			'foo',
			CodeStringValueFormatter::HTML_SHORT,
			null,
			'<div class="smwpre">foo</div>'
		);

		$provider[] = array(
			'foo',
			CodeStringValueFormatter::WIKI_LONG,
			null,
			'<div class="smwpre"><div style="height:5em; overflow:auto;">foo</div></div>'
		);

		$provider[] = array(
			'foo',
			CodeStringValueFormatter::HTML_LONG,
			null,
			'<div class="smwpre"><div style="height:5em; overflow:auto;">foo</div></div>'
		);

		// > 255
		$text = 'Lorem ipsum dolor sit amet consectetuer justo Nam quis lobortis vel. Sapien nulla enim Lorem enim pede ' .
		'lorem nulla justo diam wisi. Libero Nam turpis neque leo scelerisque nec habitasse a lacus mattis. Accumsan ' .
		'tincidunt Sed adipiscing nec facilisis tortor Nunc Sed ipsum tellus';

		$expected = '<div class="smwpre"><div style="height:5em; overflow:auto;">Lorem&#160;ipsum&#160;dolor&#160;sit&#160;amet&#160;' .
		'consectetuer&#160;justo&#160;Nam&#160;quis&#160;lobortis&#160;vel.&#160;Sapien&#160;nulla&#160;enim&#160;Lorem&#160;enim&#160;' .
		'pede&#160;lorem&#160;nulla&#160;justo&#160;diam&#160;wisi.&#160;Libero&#160;Nam&#160;turpis&#160;neque&#160;leo&#160;' .
		'scelerisque&#160;nec&#160;habitasse&#160;a&#160;lacus&#160;mattis.&#160;Accumsan&#160;tincidunt&#160;Sed&#160;adipiscing&#160;' .
		'nec&#160;facilisis&#160;tortor&#160;Nunc&#160;Sed&#160;ipsum&#160;tellus</div></div>';

		$provider[] = array(
			$text,
			CodeStringValueFormatter::HTML_LONG,
			null,
			$expected
		);

		$provider[] = array(
			$text,
			CodeStringValueFormatter::WIKI_LONG,
			null,
			$expected
		);

		// XMLContentEncode
		$provider[] = array(
			'<foo>',
			CodeStringValueFormatter::HTML_LONG,
			null,
			'<div class="smwpre"><div style="height:5em; overflow:auto;">&lt;foo&gt;</div></div>'
		);

		$provider[] = array(
			'<foo>',
			CodeStringValueFormatter::HTML_SHORT,
			null,
			'<div class="smwpre">&lt;foo&gt;</div>'
		);

		$provider[] = array(
			'*Foo',
			CodeStringValueFormatter::WIKI_LONG,
			null,
			'<div class="smwpre"><div style="height:5em; overflow:auto;">*Foo</div></div>'
		);

		return $provider;
	}

}
