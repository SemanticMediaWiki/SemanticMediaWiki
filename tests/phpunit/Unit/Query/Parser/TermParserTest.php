<?php

namespace SMW\Tests\Query\Parser;

use SMW\Query\Parser\TermParser;

/**
 * @covers \SMW\Query\Parser\TermParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TermParserTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TermParser::class,
			new TermParser()
		);
	}

	/**
	 * @dataProvider termProvider
	 */
	public function testTerm_parser( $term, $expected ) {

		$instance = new TermParser();

		$this->assertEquals(
			$expected,
			$instance->parse( $term )
		);
	}

	/**
	 * @dataProvider term_prefixProvider
	 */
	public function testTerm_prefix_parser( $term, $prefixes, $expected ) {

		$instance = new TermParser( $prefixes );

		$this->assertEquals(
			$expected,
			$instance->parse( $term )
		);
	}

	public function termProvider() {

		yield [
			'in:foo',
			'[[in:foo]]'
		];

		yield [
			'[[in:foo]]',
			'[[in:foo]]'
		];

		yield [
			'in:foo || bar',
			'[[in:foo]] || bar'
		];

		yield [
			'in:foo && bar',
			'[[in:foo]] && bar'
		];

		yield [
			'in:foo || in:bar',
			'[[in:foo]] || [[in:bar]]'
		];

		yield [
			'in:foo && in:bar',
			'[[in:foo]] && [[in:bar]]'
		];

		yield [
			'in:foo bar in:bar ',
			'[[in:foo bar]] [[in:bar]]'
		];

		yield [
			'in:foo bar && in:bar',
			'[[in:foo bar]] && [[in:bar]]'
		];

		yield [
			'in:foo bar || in:bar ',
			'[[in:foo bar]] || [[in:bar]]'
		];

		yield [
			'(in:foo bar && in:foo) || in:bar ',
			'<q>[[in:foo bar]] && [[in:foo]]</q> || [[in:bar]]'
		];

		yield [
			'in:foo bar in:bar phrase:foobar 123 && in:oooo',
			'[[in:foo bar]] [[in:bar]] [[phrase:foobar 123]] && [[in:oooo]]'
		];

		yield [
			'<q>in:foo bar && in:bar</q> OR phrase:foo bar foobar',
			'<q>[[in:foo bar]] && [[in:bar]]</q> OR [[phrase:foo bar foobar]]'
		];

		yield [
			'(in:foo && in:bar)||in:foobar',
			'<q>[[in:foo]] && [[in:bar]]</q> || [[in:foobar]]'
		];

		yield [
			'(in:foo && (in:bar AND not:ooo)) || in:foobar',
			'<q>[[in:foo]] && <q>[[in:bar]] AND [[not:ooo]]</q></q> || [[in:foobar]]'
		];

		yield [
			'<q>in:foo bar && in:bar</q> OR [[Has number::123]]',
			'<q>[[in:foo bar]] && [[in:bar]]</q> OR [[Has number::123]]'
		];

		yield [
			'in:foo [[Has foo::bar]]',
			'[[in:foo]] [[Has foo::bar]]'
		];

		yield [
			'in:foo [[Has foo::bar]] (in:foo bar)',
			'[[in:foo]] [[Has foo::bar]] <q>[[in:foo bar]]</q>'
		];

		yield [
			'category:foo',
			'[[category:foo]]'
		];

		yield [
			'foo',
			'foo'
		];

		yield [
			'<q>[[Bar property::Foobar]]</q> Foo',
			'<q>[[Bar property::Foobar]]</q> Foo'
		];

		yield [
			'in:foo [[Has foo::bar]] (in:(foo bar && fin))',
			'[[in:foo]] [[Has foo::bar]] <q>[[in:foo bar]] && [[in:fin]]</q>'
		];

		yield [
			'has:foo has:bar',
			'[[foo::+]] [[bar::+]]'
		];

		yield [
			'has:(foo && bar)',
			'[[foo::+]] && [[bar::+]]'
		];

		yield [
			'in:(foo && bar) in:(ham && cheese)',
			'[[in:foo]] && [[in:bar]] [[in:ham]] && [[in:cheese]]'
		];

		yield [
			'has:(foo && bar) in:(ham && cheese)',
			'[[foo::+]] && [[bar::+]] [[in:ham]] && [[in:cheese]]'
		];
	}

	public function term_prefixProvider() {

		yield [
			'in:foo || not:bar',
			[ 'keyw' => [ 'Has keyword', 'Keyw' ] ],
			'[[in:foo]] || [[not:bar]]'
		];

		yield [
			'in:foo || keyword:foo bar || keyw:foo bar',
			[ 'keyw' => [ 'Has keyword', 'Keyw' ] ],
			'[[in:foo]] || keyword:foo bar]] || <q>[[Has keyword::foo bar]] || [[Keyw::foo bar]]</q>'
		];

		yield [
			'in:foo keyw:foo bar',
			[ 'keyw' => [ 'Has keyword', 'Keyw' ] ],
			'[[in:foo]] <q>[[Has keyword::foo bar]] || [[Keyw::foo bar]]</q>'
		];

		yield [
			'in:foo keyw:foo bar [[Foo::bar]]',
			[ 'keyw' => [ 'Has keyword', 'Keyw' ] ],
			'[[in:foo]] <q>[[Has keyword::foo bar]] || [[Keyw::foo bar]]</q> [[Foo::bar]]'
		];

		yield [
			'in:foo (a:foo bar || not:bar)',
			[ 'a' => [ 'Has keyword', 'Keyw' ] ],
			'[[in:foo]] <q><q>[[Has keyword::foo bar]] || [[Keyw::foo bar]]</q> || [[not:bar]]</q>'
		];

		yield [
			'in:foo',
			[ 'in' => [ 'a', 'b' ] ],
			'[[in:foo]]'
		];
	}

}
