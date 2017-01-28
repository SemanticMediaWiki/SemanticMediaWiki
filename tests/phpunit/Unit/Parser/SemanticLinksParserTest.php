<?php

namespace SMW\Tests\Parser;

use SMW\Parser\SemanticLinksParser;
use SMW\Parser\LinksProcessor;

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

		$provider = array();

		$provider[] = array(
			'Foo',
			array()
		);

		$provider[] = array(
			'[[Foo]]',
			array()
		);

		$provider[] = array(
			'[[Foo|Bar]]',
			array()
		);

		$provider[] = array(
			'[[Foo::[[Bar]]]]',
			array()
		);

		$provider[] = array(
			'[[Foo::Bar]]',
			array(
				array(
					'Foo'
				),
				'Bar',
				false
			)
		);

		$provider[] = array(
			'[[Foo::Bar|Foobar]]',
			array(
				array(
					'Foo'
				),
				'Bar',
				'Foobar'
			)
		);

		return $provider;
	}

}
