<?php

namespace SMW\Tests\Parser;

use SMW\Parser\LinksProcessor;

/**
 * @covers \SMW\Parser\LinksProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LinksProcessorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new LinksProcessor();

		$this->assertInstanceOf(
			'SMW\Parser\LinksProcessor',
			$instance
		);
	}

	/**
	 * @dataProvider semanticPreLinkProvider
	 */
	public function testPreprocess( $semanticLink, $expected ) {

		$instance = new LinksProcessor();

		$this->assertEquals(
			$expected,
			$instance->preprocess( $semanticLink )
		);
	}

	/**
	 * @dataProvider semanticLinkProvider
	 */
	public function testProcess( $semanticLink, $expected ) {

		$instance = new LinksProcessor();

		$this->assertEquals(
			$expected,
			$instance->process( $semanticLink )
		);
	}

	public function semanticPreLinkProvider() {

		$provider = [];

		$provider[] = [
			[
				'[[Foo::Bar]]',
				'Foo',
				'Bar'
			],
			[
				'[[Foo::Bar]]',
				'Foo',
				'Bar'
			]
		];

		$provider[] = [
			[
				'[[Foo::Bar|Foobar]]',
				'Foo',
				'Bar'
			],
			[
				'[[Foo::Bar|Foobar]]',
				'Foo',
				'Bar'
			]
		];

		return $provider;
	}

	public function semanticLinkProvider() {

		$provider = [];

		$provider[] = [
			[
				'[[Foo::Bar]]',
				'Foo',
				'Bar'
			],
			[
				[
					'Foo'
				],
				'Bar',
				false
			]
		];

		$provider[] = [
			[
				'[[Foo::Bar|Foobar]]',
				'Foo',
				'Bar'
			],
			[
				[
					'Foo'
				],
				'Bar',
				false
			]
		];

		$provider[] = [
			[
				'[[Foo::=Bar|Foobar]]',
				'Foo',
				'=Bar'
			],
			[
				[
					'Foo'
				],
				'=Bar',
				false
			]
		];

		return $provider;
	}

}
