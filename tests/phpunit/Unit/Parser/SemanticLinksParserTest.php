<?php

namespace SMW\Tests\Parser;

use SMW\Parser\LinksProcessor;
use SMW\Parser\SemanticLinksParser;

/**
 * @covers \SMW\Parser\SemanticLinksParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SemanticLinksParserTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$linksProcessor = $this->getMockBuilder( 'SMW\Parser\LinksProcessor' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SemanticLinksParser( $linksProcessor );

		$this->assertInstanceOf(
			'SMW\Parser\SemanticLinksParser',
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
