<?php

namespace SMW\Tests\Unit\ParserFunctions;

use PHPUnit\Framework\TestCase;
use SMW\ParserFunctions\PropertySearchLinkParserFunction;

/**
 * @covers \SMW\ParserFunctions\PropertySearchLinkParserFunction
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.4.0
 */
class PropertySearchLinkParserFunctionTest extends TestCase {

	public function testValueIsTheDefaultCaption() {
		$this->assertSame(
			'[[Special:SearchByProperty/:Has-20text/Foo-20bar|Foo bar]]',
			$this->parse( 'Has text', 'Foo bar', '' )
		);
	}

	public function testCustomCaption() {
		$this->assertSame(
			'[[Special:SearchByProperty/:Has-20text/Foo|See Foo]]',
			$this->parse( 'Has text', 'Foo', 'See Foo' )
		);
	}

	public function testPropertyIsTheCaptionWithoutValue() {
		$this->assertSame(
			'[[Special:SearchByProperty/:Has-20text/|Has text]]',
			$this->parse( 'Has text', '', '' )
		);
	}

	public function testEscapesCharactersThatATitleCannotCarry() {
		$this->assertStringContainsString(
			'/:Has-20text/AC-2FDC-20-231-20-5Bx-5D-20a-5Fb|',
			$this->parse( 'Has text', 'AC/DC #1 [x] a_b', '' )
		);
	}

	public function testValueCannotTurnTheLinkIntoAnAnnotation() {
		$this->assertStringContainsString(
			'/:Has-20text/a-3A-3Ab|',
			$this->parse( 'Has text', 'a::b', 'caption' )
		);
	}

	public function testValueCannotTurnTheLinkIntoAnAnnotationWithTheOlderSyntax() {
		$this->assertStringContainsString(
			'/:Has-20text/a-3A=b|',
			$this->parse( 'Has text', 'a:=b', 'caption' )
		);
	}

	public function testCaptionCannotAnnotate() {
		$this->assertStringNotContainsString(
			'[[Foo::Bar',
			$this->parse( 'Has text', 'Foo', '[[Foo::Bar]]' )
		);
	}

	public function testDecodesTheCharacterReferencesOfPageNameMagicWords() {
		$this->assertSame(
			"[[Special:SearchByProperty/:Has-20text/O'Reilly|O&#39;Reilly]]",
			$this->parse( 'Has text', 'O&#039;Reilly', '' )
		);
	}

	public function testDefaultCaptionIsNotParsedAsWikitext() {
		$this->assertStringNotContainsString(
			"|'''Foo'''",
			$this->parse( 'Has text', "'''Foo'''", '' )
		);
	}

	public function testCustomCaptionIsParsedAsWikitext() {
		$this->assertStringContainsString(
			"|'''Foo'''",
			$this->parse( 'Has text', 'Foo', "'''Foo'''" )
		);
	}

	public function testRendersNothingWithoutProperty() {
		$this->assertSame(
			'',
			$this->parse( '', 'Foo', '' )
		);
	}

	private function parse( string $property, string $value, string $caption ): string {
		return ( new PropertySearchLinkParserFunction() )->parse( $property, $value, $caption );
	}

}
