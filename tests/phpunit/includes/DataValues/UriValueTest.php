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

		// FIXME MW 1.19*
		$noFollowAttribute = version_compare( $GLOBALS['wgVersion'], '1.20', '<' ) ? '' : ' rel="nofollow"';

		// https://github.com/lanthaler/IRI/blob/master/Test/IriTest.php
		$provider[] = array(
			'http://example.org/aaa/bbb#ccc',
			false,
			null,
			array(
				'wikiValue'     => 'http://example.org/aaa/bbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa/bbb#ccc',
				'longWikiText'  => 'http://example.org/aaa/bbb#ccc',
				'shortHTMLText' => 'http://example.org/aaa/bbb#ccc',
				'shortWikiText' => 'http://example.org/aaa/bbb#ccc'
			)
		);

		$provider[] = array(
			'http://example.org/aaa/bbb#ccc',
			'Foo',
			null,
			array(
				'wikiValue'     => 'http://example.org/aaa/bbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa/bbb#ccc',
				'longWikiText'  => 'http://example.org/aaa/bbb#ccc',
				'shortHTMLText' => 'Foo',
				'shortWikiText' => 'Foo'
			)
		);

		$provider[] = array(
			'http://example.org/aaa/bbb#ccc',
			false,
			$linker,
			array(
				'wikiValue'     => 'http://example.org/aaa/bbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa/bbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa/bbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa/bbb#ccc</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc http://example.org/aaa/bbb#ccc]'
			)
		);

		#3
		$provider[] = array(
			'http://example.org/aaa/bbb#ccc',
			'Foo',
			$linker,
			array(
				'wikiValue'     => 'http://example.org/aaa/bbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa/bbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa/bbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">Foo</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc Foo]',
			)
		);

		#4
		$provider[] = array(
			'http://example.org/aaa%2Fbbb#ccc',
			false,
			null,
			array(
				'wikiValue'     => 'http://example.org/aaa%2Fbbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa%2Fbbb#ccc',
				'longWikiText'  => 'http://example.org/aaa%2Fbbb#ccc',
				'shortHTMLText' => 'http://example.org/aaa%2Fbbb#ccc',
				'shortWikiText' => 'http://example.org/aaa%2Fbbb#ccc'
			)
		);

		$provider[] = array(
			'http://example.org/aaa%2Fbbb#ccc',
			'Foo',
			null,
			array(
				'wikiValue'     => 'http://example.org/aaa%2Fbbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa%2Fbbb#ccc',
				'longWikiText'  => 'http://example.org/aaa%2Fbbb#ccc',
				'shortHTMLText' => 'Foo',
				'shortWikiText' => 'Foo'
			)
		);

		#6
		$provider[] = array(
			'http://example.org/aaa%2Fbbb#ccc',
			false,
			$linker,
			array(
				'wikiValue'     => 'http://example.org/aaa%2Fbbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa%2Fbbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa%2Fbbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa%2Fbbb#ccc</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc http://example.org/aaa%2Fbbb#ccc]'
			)
		);

		$provider[] = array(
			'http://example.org/aaa%2Fbbb#ccc',
			'Foo',
			$linker,
			array(
				'wikiValue'     => 'http://example.org/aaa%2Fbbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa%2Fbbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa%2Fbbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">Foo</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc Foo]',
			)
		);

		#8 UTF-8 encoded string
		$provider[] = array(
			'http://example.org/ようこそ--23-7B-7D',
			false,
			null,
			array(
				'wikiValue'     => 'http://example.org/ようこそ--23-7B-7D',
				'longHTMLText'  => 'http://example.org/ようこそ--23-7B-7D',
				'longWikiText'  => 'http://example.org/ようこそ--23-7B-7D',
				'shortHTMLText' => 'http://example.org/ようこそ--23-7B-7D',
				'shortWikiText' => 'http://example.org/ようこそ--23-7B-7D'
			)
		);

		#9
		$provider[] = array(
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			false,
			null,
			array(
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longWikiText'  => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'shortHTMLText' => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'shortWikiText' => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D'
			)
		);

		$provider[] = array(
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			'一二三',
			null,
			array(
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longWikiText'  => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'shortHTMLText' => '一二三',
				'shortWikiText' => '一二三'
			)
		);

		# 11
		$provider[] = array(
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			false,
			$linker,
			array(
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D">http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D</a>',
				'longWikiText'  => '[http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D">http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D</a>',
				'shortWikiText' => '[http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D]'
			)
		);

		$provider[] = array(
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			'一二三',
			$linker,
			array(
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D">http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D</a>',
				'longWikiText'  => '[http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D">一二三</a>',
				'shortWikiText' => '[http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D 一二三]',
			)
		);

		# 13
		$provider[] = array(
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			false,
			null,
			array(
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longHTMLText'  => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longWikiText'  => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'shortHTMLText' => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'shortWikiText' => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar'
			)
		);

		$provider[] = array(
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			'&!_:;@*#Foo',
			null,
			array(
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longHTMLText'  => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longWikiText'  => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'shortHTMLText' => '&!_:;@*#Foo',
				'shortWikiText' => '&!_:;@*#Foo'
			)
		);

		$provider[] = array(
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			false,
			$linker,
			array(
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar', // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=%21_:%3B@%2A%20#Foo&amp;=%20-3DBar">http://example.org/api?query=!_:;@* #Foo&amp;=%20-3DBar</a>', // @codingStandardsIgnoreEnd
				'longWikiText'  => '[http://example.org/api?query=%21_:%3B@%2A%20#Foo&=%20-3DBar http://example.org/api?query=!_:;@* #Foo&=%20-3DBar]', // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=%21_:%3B@%2A%20#Foo&amp;=%20-3DBar">http://example.org/api?query=!_:;@* #Foo&amp;=%20-3DBar</a>', // @codingStandardsIgnoreEnd
				'shortWikiText' => '[http://example.org/api?query=%21_:%3B@%2A%20#Foo&=%20-3DBar http://example.org/api?query=!_:;@* #Foo&=%20-3DBar]'
			)
		);

		$provider[] = array(
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			'&!_:;@* #Foo',
			$linker,
			array(
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar', // @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=%21_:%3B@%2A%20#Foo&amp;=%20-3DBar">http://example.org/api?query=!_:;@* #Foo&amp;=%20-3DBar</a>', // @codingStandardsIgnoreEnd
				'longWikiText'  => '[http://example.org/api?query=%21_:%3B@%2A%20#Foo&=%20-3DBar http://example.org/api?query=!_:;@* #Foo&=%20-3DBar]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=%21_:%3B@%2A%20#Foo&amp;=%20-3DBar">&amp;!_:;@* #Foo</a>',
				'shortWikiText' => '[http://example.org/api?query=%21_:%3B@%2A%20#Foo&=%20-3DBar &!_:;@* #Foo]'
			)
		);

		return $provider;
	}

	public function telProvider() {

		$provider[] = array(
			'+1-201-555-0123',
			false,
			null,
			array(
				'wikiValue'     => '+1-201-555-0123',
				'longHTMLText'  => '+1-201-555-0123',
				'longWikiText'  => '+1-201-555-0123',
				'shortHTMLText' => '+1-201-555-0123',
				'shortWikiText' => '+1-201-555-0123'
			)
		);

		return $provider;
	}

	public function emaProvider() {

		$provider[] = array(
			'foo@example.org',
			false,
			null,
			array(
				'wikiValue'     => 'foo@example.org',
				'longHTMLText'  => 'foo@example.org',
				'longWikiText'  => 'foo@example.org',
				'shortHTMLText' => 'foo@example.org',
				'shortWikiText' => 'foo@example.org'
			)
		);

		return $provider;
	}

}
