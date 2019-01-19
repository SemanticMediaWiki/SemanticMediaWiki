<?php

namespace SMW\Tests\DataValues;

use SMWURIValue as UriValue;

/**
 * @covers \SMWURIValue
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class UriValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWURIValue',
			new UriValue( '_uri' )
		);
	}

	/**
	 * @dataProvider uriProvider
	 */
	public function testUriOutputFormatting( $uri, $caption = false, $linker = null, $expected ) {

		$instance = new UriValue( '_uri' );
		$instance->setUserValue( $uri, $caption );

		$this->assertOutputFormatting(
			$instance,
			$linker,
			$expected
		);
	}

	/**
	 * @dataProvider uriProvider
	 */
	public function testAnuOutputFormatting( $uri, $caption = false, $linker = null, $expected ) {

		$instance = new UriValue( '_anu' );
		$instance->setUserValue( $uri, $caption );

		$this->assertOutputFormatting(
			$instance,
			$linker,
			$expected
		);
	}

	/**
	 * @dataProvider telProvider
	 */
	public function testTelOutputFormatting( $uri, $caption = false, $linker = null, $expected ) {

		$instance = new UriValue( '_tel' );
		$instance->setUserValue( $uri, $caption );

		$this->assertOutputFormatting(
			$instance,
			$linker,
			$expected
		);
	}

	/**
	 * @dataProvider emaProvider
	 */
	public function testEmaOutputFormatting( $uri, $caption = false, $linker = null, $expected ) {

		$instance = new UriValue( '_ema' );
		$instance->setUserValue( $uri, $caption );

		$this->assertOutputFormatting(
			$instance,
			$linker,
			$expected
		);
	}

	private function assertOutputFormatting( $instance, $linker, $expected ) {

		$this->assertEquals(
			$expected['wikiValue'],
			$instance->getWikiValue(),
			'Failed asserting wikiValue'
		);

		$this->assertEquals(
			$expected['longHTMLText'],
			$instance->getLongHTMLText( $linker ),
			'Failed asserting longHTMLText'
		);

		$this->assertEquals(
			$expected['longWikiText'],
			$instance->getLongWikiText( $linker ),
			'Failed asserting longWikiText'
		);

		$this->assertEquals(
			$expected['shortHTMLText'],
			$instance->getShortHTMLText( $linker ),
			'Failed asserting shortHTMLText'
		);

		$this->assertEquals(
			$expected['shortWikiText'],
			$instance->getShortWikiText( $linker ),
			'Failed asserting shortWikiText'
		);
	}

	public function uriProvider() {

		$linker = smwfGetLinker();
		$noFollowAttribute = ' rel="nofollow"';

		// https://github.com/lanthaler/IRI/blob/master/Test/IriTest.php
		$provider[] = [
			'http://example.org/aaa/bbb#ccc',
			false,
			null,
			[
				'wikiValue'     => 'http://example.org/aaa/bbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa/bbb#ccc',
				'longWikiText'  => 'http://example.org/aaa/bbb#ccc',
				'shortHTMLText' => 'http://example.org/aaa/bbb#ccc',
				'shortWikiText' => 'http://example.org/aaa/bbb#ccc'
			]
		];

		$provider[] = [
			'http://example.org/aaa/bbb#ccc',
			'Foo',
			null,
			[
				'wikiValue'     => 'http://example.org/aaa/bbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa/bbb#ccc',
				'longWikiText'  => 'http://example.org/aaa/bbb#ccc',
				'shortHTMLText' => 'Foo',
				'shortWikiText' => 'Foo'
			]
		];

		$provider[] = [
			'http://example.org/aaa/bbb#ccc',
			false,
			$linker,
			[
				'wikiValue'     => 'http://example.org/aaa/bbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa/bbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa/bbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa/bbb#ccc</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc http://example.org/aaa/bbb#ccc]'
			]
		];

		#3
		$provider[] = [
			'http://example.org/aaa/bbb#ccc',
			'Foo',
			$linker,
			[
				'wikiValue'     => 'http://example.org/aaa/bbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa/bbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa/bbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">Foo</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc Foo]',
			]
		];

		#4
		$provider[] = [
			'http://example.org/aaa%2Fbbb#ccc',
			false,
			null,
			[
				'wikiValue'     => 'http://example.org/aaa%2Fbbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa%2Fbbb#ccc',
				'longWikiText'  => 'http://example.org/aaa%2Fbbb#ccc',
				'shortHTMLText' => 'http://example.org/aaa%2Fbbb#ccc',
				'shortWikiText' => 'http://example.org/aaa%2Fbbb#ccc'
			]
		];

		$provider[] = [
			'http://example.org/aaa%2Fbbb#ccc',
			'Foo',
			null,
			[
				'wikiValue'     => 'http://example.org/aaa%2Fbbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa%2Fbbb#ccc',
				'longWikiText'  => 'http://example.org/aaa%2Fbbb#ccc',
				'shortHTMLText' => 'Foo',
				'shortWikiText' => 'Foo'
			]
		];

		#6
		$provider[] = [
			'http://example.org/aaa%2Fbbb#ccc',
			false,
			$linker,
			[
				'wikiValue'     => 'http://example.org/aaa%2Fbbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa%2Fbbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa%2Fbbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa%2Fbbb#ccc</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc http://example.org/aaa%2Fbbb#ccc]'
			]
		];

		$provider[] = [
			'http://example.org/aaa%2Fbbb#ccc',
			'Foo',
			$linker,
			[
				'wikiValue'     => 'http://example.org/aaa%2Fbbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa%2Fbbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa%2Fbbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">Foo</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc Foo]',
			]
		];

		#8 UTF-8 encoded string
		$provider[] = [
			'http://example.org/ようこそ--23-7B-7D',
			false,
			null,
			[
				'wikiValue'     => 'http://example.org/ようこそ--23-7B-7D',
				'longHTMLText'  => 'http://example.org/ようこそ--23-7B-7D',
				'longWikiText'  => 'http://example.org/ようこそ--23-7B-7D',
				'shortHTMLText' => 'http://example.org/ようこそ--23-7B-7D',
				'shortWikiText' => 'http://example.org/ようこそ--23-7B-7D'
			]
		];

		#9
		$provider[] = [
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			false,
			null,
			[
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longWikiText'  => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'shortHTMLText' => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'shortWikiText' => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D'
			]
		];

		$provider[] = [
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			'一二三',
			null,
			[
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longWikiText'  => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'shortHTMLText' => '一二三',
				'shortWikiText' => '一二三'
			]
		];

		# 11
		$provider[] = [
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			false,
			$linker,
			[
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D">http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D</a>',
				'longWikiText'  => '[http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D">http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D</a>',
				'shortWikiText' => '[http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D]'
			]
		];

		$provider[] = [
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			'一二三',
			$linker,
			[
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D">http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D</a>',
				'longWikiText'  => '[http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D">一二三</a>',
				'shortWikiText' => '[http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D 一二三]',
			]
		];

		# 13
		$provider[] = [
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			false,
			null,
			[
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longHTMLText'  => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longWikiText'  => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'shortHTMLText' => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'shortWikiText' => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar'
			]
		];

		$provider[] = [
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			'&!_:;@*#Foo',
			null,
			[
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longHTMLText'  => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longWikiText'  => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'shortHTMLText' => '&!_:;@*#Foo',
				'shortWikiText' => '&!_:;@*#Foo'
			]
		];

		#15
		$provider[] = [
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			false,
			$linker,
			[
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar', // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=%21_:%3B@%2A_#Foo&amp;=_-3DBar">http://example.org/api?query=! :;@* #Foo&amp;=%20-3DBar</a>', // @codingStandardsIgnoreEnd
				'longWikiText'  => '[http://example.org/api?query=%21_:%3B@%2A_#Foo&=_-3DBar http://example.org/api?query=! :;@* #Foo&=%20-3DBar]', // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=%21_:%3B@%2A_#Foo&amp;=_-3DBar">http://example.org/api?query=! :;@* #Foo&amp;=%20-3DBar</a>', // @codingStandardsIgnoreEnd
				'shortWikiText' => '[http://example.org/api?query=%21_:%3B@%2A_#Foo&=_-3DBar http://example.org/api?query=! :;@* #Foo&=%20-3DBar]'
			]
		];

		$provider[] = [
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			'&!_:;@* #Foo',
			$linker,
			[
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar', // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=%21_:%3B@%2A_#Foo&amp;=_-3DBar">http://example.org/api?query=! :;@* #Foo&amp;=%20-3DBar</a>', // @codingStandardsIgnoreEnd
				'longWikiText'  => '[http://example.org/api?query=%21_:%3B@%2A_#Foo&=_-3DBar http://example.org/api?query=! :;@* #Foo&=%20-3DBar]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=%21_:%3B@%2A_#Foo&amp;=_-3DBar">&amp;! :;@* #Foo</a>',
				'shortWikiText' => '[http://example.org/api?query=%21_:%3B@%2A_#Foo&=_-3DBar &! :;@* #Foo]'
			]
		];

		return $provider;
	}

	public function telProvider() {

		$provider[] = [
			'+1-201-555-0123',
			false,
			null,
			[
				'wikiValue'     => '+1-201-555-0123',
				'longHTMLText'  => '+1-201-555-0123',
				'longWikiText'  => '+1-201-555-0123',
				'shortHTMLText' => '+1-201-555-0123',
				'shortWikiText' => '+1-201-555-0123'
			]
		];

		return $provider;
	}

	public function emaProvider() {

		$provider[] = [
			'foo@example.org',
			false,
			null,
			[
				'wikiValue'     => 'foo@example.org',
				'longHTMLText'  => 'foo@example.org',
				'longWikiText'  => 'foo@example.org',
				'shortHTMLText' => 'foo@example.org',
				'shortWikiText' => 'foo@example.org'
			]
		];

		return $provider;
	}

}
