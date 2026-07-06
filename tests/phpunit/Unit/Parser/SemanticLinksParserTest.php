<?php

namespace SMW\Tests\Unit\Parser;

use PHPUnit\Framework\TestCase;
use SMW\Parser\LinksProcessor;
use SMW\Parser\SemanticLinksParser;

/**
 * @covers \SMW\Parser\SemanticLinksParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SemanticLinksParserTest extends TestCase {

	public function testCanConstruct() {
		$linksProcessor = $this->getMockBuilder( LinksProcessor::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SemanticLinksParser( $linksProcessor );

		$this->assertInstanceOf(
			SemanticLinksParser::class,
			$instance
		);
	}

	/**
	 * @dataProvider textProvider
	 */
	public function testParse( $text, $expected ) {
		$instance = new SemanticLinksParser(
			new LinksProcessor()
		);

		$this->assertEquals(
			$expected,
			$instance->parse( $text )
		);
	}

	public function textProvider() {
		$provider = [];

		$provider[] = [
			'Foo',
			[]
		];

		$provider[] = [
			'[[Foo]]',
			[]
		];

		$provider[] = [
			'[[Foo|Bar]]',
			[]
		];

		$provider[] = [
			'[[Foo::[[Bar]]]]',
			[]
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				[
					'Foo'
				],
				'Bar',
				false
			]
		];

		$provider[] = [
			'[[Foo::Bar|Foobar]]',
			[
				[
					'Foo'
				],
				'Bar',
				'Foobar'
			]
		];

		return $provider;
	}

}
