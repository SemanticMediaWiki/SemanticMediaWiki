<?php

namespace SMW\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use SMW\Parser\PlainWikitext;

/**
 * @covers \SMW\Parser\PlainWikitext
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class PlainWikitextTest extends TestCase {

	/**
	 * @dataProvider plainTextProvider
	 */
	public function testAcceptsTextTheParserLeavesUnchanged( string $text ) {
		$this->assertTrue( PlainWikitext::isPlain( $text ) );
	}

	public static function plainTextProvider(): iterable {
		yield 'words' => [ 'Storm river night' ];
		yield 'sentence' => [ 'A quiet story, told (twice) - again.' ];
		yield 'letters with diacritics' => [ 'Ünïcödé text' ];
		yield 'combining marks' => [ 'हिन्दी' ];
		yield 'non-Latin script' => [ 'Ночь у реки' ];
		yield 'ISBN-like digits' => [ '978-3-16-148410-0' ];
		yield 'colon followed by a space' => [ 'Remark: see below' ];
		yield 'question mark after a word' => [ 'Is it true?' ];
		yield 'percent after a number' => [ '100%' ];
		yield 'single apostrophe' => [ "It's late" ];
		yield 'double quotes' => [ 'A "quoted" word' ];
		yield 'typographic quotes' => [ 'It’s a “quoted” word' ];
		yield 'slash' => [ 'and/or' ];
		yield 'ampersand' => [ 'Tom & Jerry' ];
		yield 'dashes' => [ '1990–2000 — and after' ];
		yield 'ellipsis of shortened text' => [ 'A long summary …' ];
		yield 'degree sign' => [ '20 °C' ];
	}

	/**
	 * @dataProvider markupProvider
	 */
	public function testRejectsTextTheParserMayChange( string $text ) {
		$this->assertFalse( PlainWikitext::isPlain( $text ) );
	}

	public static function markupProvider(): iterable {
		yield 'leading space (preformatted)' => [ ' Leading space' ];
		yield 'indentation' => [ ': indented' ];
		yield 'definition term' => [ ';term' ];
		yield 'horizontal rule' => [ '----' ];
		yield 'italic' => [ "An ''italic'' word" ];
		yield 'internal link' => [ 'See [[Foo]]' ];
		yield 'template' => [ 'A {{template}}' ];
		yield 'HTML' => [ 'A <b>bold</b> word' ];
		yield 'character reference' => [ 'Tom &amp; Jerry' ];
		yield 'non-ASCII character reference' => [ 'Mark &רלמ; here' ];
		yield 'free URL' => [ 'Visit https://example.org' ];
		yield 'URL protocol without slashes' => [ 'Call tel:123' ];
		yield 'French spacing before a colon' => [ 'Remark : x' ];
		yield 'French spacing before a question mark' => [ 'Is it ? yes' ];
		yield 'French spacing before an exclamation mark' => [ 'Wow !' ];
		yield 'French spacing before a semicolon' => [ 'So ; then' ];
		yield 'French spacing before a percent sign' => [ '100 %' ];
		yield 'French spacing inside guillemets' => [ 'Il dit « oui »' ];
		yield 'magic word' => [ 'A __NOTOC__ b' ];
		yield 'ISBN magic link' => [ 'ISBN 978-3-16-148410-0' ];
		yield 'RFC magic link' => [ 'RFC 1234' ];
		yield 'PMID magic link' => [ 'PMID 1234' ];
		yield 'line break before preformatted text' => [ "Two\n lines" ];
		yield 'trailing line break' => [ "Ends with a line break\n" ];
		yield 'combining overlay the parser escapes' => [ "Na\u{0338}ve" ];
	}

}
