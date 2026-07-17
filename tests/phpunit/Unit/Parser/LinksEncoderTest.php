<?php

namespace SMW\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use SMW\Parser\InTextAnnotationParser;
use SMW\Parser\LinksEncoder;

/**
 * @covers \SMW\Parser\LinksEncoder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class LinksEncoderTest extends TestCase {

	/**
	 * @dataProvider obfuscateProvider
	 */
	public function testRoundTripLinkObfuscation( $text ) {
		$newText = LinksEncoder::encodeLinks( $text );

		$this->assertEquals(
			$text,
			LinksEncoder::removeLinkObfuscation( $newText )
		);
	}

	/**
	 * @dataProvider obfuscateProvider
	 */
	public function testfindAndEncodeLinks( $text, $expected ) {
		$inTextAnnotationParser = $this->getMockBuilder( InTextAnnotationParser::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertEquals(
			$expected,
			LinksEncoder::findAndEncodeLinks( $text, $inTextAnnotationParser )
		);
	}

	/**
	 * @dataProvider neutralizeAnnotationProvider
	 */
	public function testNeutralizeAnnotation( string $text, string $expected ) {
		$this->assertSame(
			$expected,
			LinksEncoder::neutralizeAnnotation( $text )
		);
	}

	/**
	 * @return array<string,array{string,string}>
	 */
	public function neutralizeAnnotationProvider(): array {
		return [
			'annotation reduces to its value' => [ '[[Bar::Baz]]', 'Baz' ],
			'annotation reduces to its caption' => [ '[[Bar::Baz|label]]', 'label' ],
			'annotation within text' => [ 'see [[Age::42]] here', 'see 42 here' ],
			'subobject annotation' => [ '[[Bar:=Baz]]', 'Baz' ],
			'annotation off switch is dropped' => [ '[[SMW::off]]', '' ],
			'annotation on switch is dropped' => [ '[[SMW::on]]', '' ],
			// a value that is an annotation in its own right is reduced too
			'nested annotation' => [ '[[Foo::[[Bar::Baz]]]]', 'Baz' ],
			'deeply nested annotation' => [ '[[A::[[B::[[C::D]]]]]]', 'D' ],
			// removeAnnotation() leaves this shape intact (#1747), so the
			// brackets are encoded instead of reduced
			'annotation that is not reduced is obfuscated' => [ '[[Bar|x::Baz]]', '&#91;&#91;Bar|x::Baz]]' ],
			// an annotation cannot be smuggled past the match by encoding it
			'encoded annotation' => [ '%5B%5BBar::Baz%5D%5D', 'Baz' ],
			'link is left alone' => [ '[[Some page]]', '[[Some page]]' ],
			'piped link is left alone' => [ '[[Some page|label]]', '[[Some page|label]]' ],
			'namespaced link is left alone' => [ '[[Category:Foo]]', '[[Category:Foo]]' ],
			'external link is left alone' => [ '[http://example.org e]', '[http://example.org e]' ],
			'text is left alone' => [ 'plain text', 'plain text' ],
			'empty text' => [ '', '' ],
		];
	}

	/**
	 * @dataProvider stripTextWithAnnotationProvider
	 */
	public function testStrip( $text, $expectedRemoval, $expectedObscuration ) {
		$this->assertEquals(
			$expectedRemoval,
			LinksEncoder::removeAnnotation( $text )
		);

		$this->assertEquals(
			$expectedObscuration,
			LinksEncoder::obfuscateAnnotation( $text )
		);
	}

	public function stripTextWithAnnotationProvider() {
		$provider = [];

		$provider[] = [
			'Suspendisse [[Bar::tincidunt semper|abc]] facilisi',
			'Suspendisse abc facilisi',
			'Suspendisse &#91;&#91;Bar::tincidunt semper|abc]] facilisi'
		];

		$provider[] = [
			'Suspendisse [[Bar::tincidunt semper]] facilisi',
			'Suspendisse tincidunt semper facilisi',
			'Suspendisse &#91;&#91;Bar::tincidunt semper]] facilisi'
		];

		$provider[] = [
			'Suspendisse [[:Tincidunt semper|tincidunt semper]]',
			'Suspendisse [[:Tincidunt semper|tincidunt semper]]',
			'Suspendisse [[:Tincidunt semper|tincidunt semper]]'
		];

		$provider[] = [
			'[[Foo::Foobar::テスト]] [[Bar:::ABC|DEF]] [[Foo:::0049 30 12345678/::Foo]]',
			'Foobar::テスト DEF :0049 30 12345678/::Foo',
			'&#91;&#91;Foo::Foobar::テスト]] &#91;&#91;Bar:::ABC|DEF]] &#91;&#91;Foo:::0049 30 12345678/::Foo]]'
		];

		$provider[] = [
			'%5B%5BFoo%20Bar::foobaz%5D%5D',
			'foobaz',
			'&#91;&#91;Foo%20Bar::foobaz]]'
		];

		$provider[] = [
			'Suspendisse tincidunt semper facilisi',
			'Suspendisse tincidunt semper facilisi',
			'Suspendisse tincidunt semper facilisi'
		];

		// #1747
		$provider[] = [
			'[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[File:Example.png|Bar::Foobar|link=Foo]]',
			'[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[File:Example.png|Bar::Foobar|link=Foo]]',
			'&#91;&#91;Foo|Bar::Foobar]] &#91;&#91;File:Example.png|alt=Bar::Foobar|Caption]] &#91;&#91;File:Example.png|Bar::Foobar|link=Foo]]'
		];

		$provider[] = [
			'[[Foo::@@@]] [[Bar::@@@|123]]',
			' 123',
			'&#91;&#91;Foo::@@@]] &#91;&#91;Bar::@@@|123]]'
		];

		$provider[] = [
			'Suspendisse [[SMW::off]][[Bar::tincidunt semper|abc]] facilisi[[SMW::on]] [[Bar:::ABC|DEF]]',
			'Suspendisse abc facilisi DEF',
			'Suspendisse &#91;&#91;SMW::off]]&#91;&#91;Bar::tincidunt semper|abc]] facilisi&#91;&#91;SMW::on]] &#91;&#91;Bar:::ABC|DEF]]'
		];

		return $provider;
	}

	public function obfuscateProvider() {
		$provider = [];

		$provider[] = [
			'Foo',
			'Foo'
		];

		$provider[] = [
			'[[Foo]]',
			'[[Foo]]'
		];

		$provider[] = [
			'[[Foo|Bar]]',
			'[[Foo|Bar]]'
		];

		$provider[] = [
			'[[Foo::[[Bar]]]]',
			'[[Foo::&#x005B;&#x005B;Bar&#x005D;&#x005D;]]'
		];

		$provider[] = [
			'[[Foo::[[Foo|Bar]]]]',
			'[[Foo::&#x005B;&#x005B;Foo&#124;Bar&#x005D;&#x005D;]]'
		];

		return $provider;
	}

}
