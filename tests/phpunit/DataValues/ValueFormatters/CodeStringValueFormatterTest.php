<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValueFactory;
use SMW\DataValues\ValueFormatters\CodeStringValueFormatter;

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
			CodeStringValueFormatter::class,
			new CodeStringValueFormatter()
		);
	}

	/**
	 * @dataProvider stringValueProvider
	 */
	public function testFormat( $userValue, $type, $linker, $expected ) {

		$codeStringValue = DataValueFactory::getInstance()->newDataValueByType( '_cod' );
		$codeStringValue->setUserValue( $userValue );

		$instance = new CodeStringValueFormatter();

		$this->assertEquals(
			$expected,
			$instance->format( $codeStringValue, [ $type, $linker ] )
		);
	}

	public function stringValueProvider() {

		$provider[] = [
			'foo',
			CodeStringValueFormatter::VALUE,
			null,
			'foo'
		];

		$provider[] = [
			'foo',
			CodeStringValueFormatter::WIKI_SHORT,
			null,
			'<div class="smwpre">foo</div>'
		];

		$provider[] = [
			'foo',
			CodeStringValueFormatter::HTML_SHORT,
			null,
			'<div class="smwpre">foo</div>'
		];

		$provider[] = [
			'foo',
			CodeStringValueFormatter::WIKI_LONG,
			null,
			'<div class="smwpre"><div style="min-height:5em; overflow:auto;">foo</div></div>'
		];

		$provider[] = [
			'foo',
			CodeStringValueFormatter::HTML_LONG,
			null,
			'<div class="smwpre"><div style="min-height:5em; overflow:auto;">foo</div></div>'
		];

		$provider[] = [
			'<code><nowiki>&#x005B;&#x005B;Foo]]</nowiki></code>',
			CodeStringValueFormatter::HTML_LONG,
			null,
			'<div class="smwpre"><div style="min-height:5em; overflow:auto;">&#91;&#91;Foo]]</div></div>'
		];

		$provider[] = [
			'<code><nowiki>[[Foo]]</nowiki></code>',
			CodeStringValueFormatter::HTML_LONG,
			null,
			'<div class="smwpre"><div style="min-height:5em; overflow:auto;">&#91;&#91;Foo]]</div></div>'
		];

		// > 255
		$text = 'Lorem ipsum dolor sit amet consectetuer justo Nam quis lobortis vel. Sapien nulla enim Lorem enim pede ' .
		'lorem nulla justo diam wisi. Libero Nam turpis neque leo scelerisque nec habitasse a lacus mattis. Accumsan ' .
		'tincidunt Sed adipiscing nec facilisis tortor Nunc Sed ipsum tellus';

		$expected = '<div class="smwpre"><div style="min-height:5em; overflow:auto;">Lorem&#160;ipsum&#160;dolor&#160;sit&#160;amet&#160;' .
		'consectetuer&#160;justo&#160;Nam&#160;quis&#160;lobortis&#160;vel.&#160;Sapien&#160;nulla&#160;enim&#160;Lorem&#160;enim&#160;' .
		'pede&#160;lorem&#160;nulla&#160;justo&#160;diam&#160;wisi.&#160;Libero&#160;Nam&#160;turpis&#160;neque&#160;leo&#160;' .
		'scelerisque&#160;nec&#160;habitasse&#160;a&#160;lacus&#160;mattis.&#160;Accumsan&#160;tincidunt&#160;Sed&#160;adipiscing&#160;' .
		'nec&#160;facilisis&#160;tortor&#160;Nunc&#160;Sed&#160;ipsum&#160;tellus</div></div>';

		$provider[] = [
			$text,
			CodeStringValueFormatter::HTML_LONG,
			null,
			$expected
		];

		$provider[] = [
			$text,
			CodeStringValueFormatter::WIKI_LONG,
			null,
			$expected
		];

		// XMLContentEncode
		$provider[] = [
			'<foo>',
			CodeStringValueFormatter::HTML_LONG,
			null,
			'<div class="smwpre"><div style="min-height:5em; overflow:auto;">&lt;foo&gt;</div></div>'
		];

		$provider[] = [
			'<foo>',
			CodeStringValueFormatter::HTML_SHORT,
			null,
			'<div class="smwpre">&lt;foo&gt;</div>'
		];

		$provider[] = [
			'*Foo',
			CodeStringValueFormatter::WIKI_LONG,
			null,
			'<div class="smwpre"><div style="min-height:5em; overflow:auto;">*Foo</div></div>'
		];

		// JSON
		$jsonString = '{"limit": 50,"offset": 0,"sort": [],"order": [],"mode": 1}';
		$provider[] = [
			$jsonString,
			CodeStringValueFormatter::WIKI_LONG,
			null,
			'<div class="smwpre"><div style="min-height:5em; overflow:auto;">' . CodeStringValueFormatter::asJson( $jsonString ) . '</div></div>'
		];

		return $provider;
	}

}
