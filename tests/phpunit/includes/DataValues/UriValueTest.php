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
	public function testOutputFormatting( $uri, $caption = false, $linker = null, $expected ) {

		$instance = new UriValue( '_uri' );
		$instance->setUserValue( $uri, $caption );

		$this->assertEquals(
			$expected['wikiValue'],
			$instance->getWikiValue()
		);

		$this->assertEquals(
			$expected['longHTMLText'],
			$instance->getLongHTMLText( $linker )
		);

		$this->assertEquals(
			$expected['longWikiText'],
			$instance->getLongWikiText( $linker )
		);

		$this->assertEquals(
			$expected['shortHTMLText'],
			$instance->getShortHTMLText( $linker )
		);

		$this->assertEquals(
			$expected['shortWikiText'],
			$instance->getShortWikiText( $linker )
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

		//
		$provider[] = array(
			'http://example.org/aaa%2fbbb#ccc',
			false,
			null,
			array(
				'wikiValue'     => 'http://example.org/aaa%2fbbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa/bbb#ccc',
				'longWikiText'  => 'http://example.org/aaa/bbb#ccc',
				'shortHTMLText' => 'http://example.org/aaa/bbb#ccc',
				'shortWikiText' => 'http://example.org/aaa/bbb#ccc'
			)
		);

		$provider[] = array(
			'http://example.org/aaa%2fbbb#ccc',
			'Foo',
			null,
			array(
				'wikiValue'     => 'http://example.org/aaa%2fbbb#ccc',
				'longHTMLText'  => 'http://example.org/aaa/bbb#ccc',
				'longWikiText'  => 'http://example.org/aaa/bbb#ccc',
				'shortHTMLText' => 'Foo',
				'shortWikiText' => 'Foo'
			)
		);

		$provider[] = array(
			'http://example.org/aaa%2fbbb#ccc',
			false,
			$linker,
			array(
				'wikiValue'     => 'http://example.org/aaa%2fbbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa/bbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa/bbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa/bbb#ccc</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc http://example.org/aaa/bbb#ccc]'
			)
		);

		$provider[] = array(
			'http://example.org/aaa%2fbbb#ccc',
			'Foo',
			$linker,
			array(
				'wikiValue'     => 'http://example.org/aaa%2fbbb#ccc',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">http://example.org/aaa/bbb#ccc</a>',
				'longWikiText'  => '[http://example.org/aaa/bbb#ccc http://example.org/aaa/bbb#ccc]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/aaa/bbb#ccc">Foo</a>',
				'shortWikiText' => '[http://example.org/aaa/bbb#ccc Foo]',
			)
		);

		// UTF-8 encoded string
		$provider[] = array(
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			false,
			null,
			array(
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => 'http://example.org/ようこそ#{}',
				'longWikiText'  => 'http://example.org/ようこそ#{}',
				'shortHTMLText' => 'http://example.org/ようこそ#{}',
				'shortWikiText' => 'http://example.org/ようこそ#{}'
			)
		);

		$provider[] = array(
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			'%20%E4%B8%80%E4%BA%8C%E4%B8%89',
			null,
			array(
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => 'http://example.org/ようこそ#{}',
				'longWikiText'  => 'http://example.org/ようこそ#{}',
				'shortHTMLText' => '一二三',
				'shortWikiText' => '一二三'
			)
		);

		$provider[] = array(
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			false,
			$linker,
			array(
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/ようこそ#{}">http://example.org/ようこそ#{}</a>',
				'longWikiText'  => '[http://example.org/ようこそ#{} http://example.org/ようこそ#{}]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/ようこそ#{}">http://example.org/ようこそ#{}</a>',
				'shortWikiText' => '[http://example.org/ようこそ#{} http://example.org/ようこそ#{}]'
			)
		);

		$provider[] = array(
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
			'%20%E4%B8%80%E4%BA%8C%E4%B8%89',
			$linker,
			array(
				'wikiValue'     => 'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D-23-7B-7D',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/ようこそ#{}">http://example.org/ようこそ#{}</a>',
				'longWikiText'  => '[http://example.org/ようこそ#{} http://example.org/ようこそ#{}]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/ようこそ#{}">一二三</a>',
				'shortWikiText' => '[http://example.org/ようこそ#{} 一二三]',
			)
		);

		// ...
		$provider[] = array(
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			false,
			null,
			array(
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longHTMLText'  => 'http://example.org/api?query=!_:;@* #Foo&= =Bar',
				'longWikiText'  => 'http://example.org/api?query=!_:;@* #Foo&= =Bar',
				'shortHTMLText' => 'http://example.org/api?query=!_:;@* #Foo&= =Bar',
				'shortWikiText' => 'http://example.org/api?query=!_:;@* #Foo&= =Bar'
			)
		);

		$provider[] = array(
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			'&!_:;@*#Foo',
			null,
			array(
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longHTMLText'  => 'http://example.org/api?query=!_:;@* #Foo&= =Bar',
				'longWikiText'  => 'http://example.org/api?query=!_:;@* #Foo&= =Bar',
				'shortHTMLText' => '&!_:;@*#Foo',
				'shortWikiText' => '&!_:;@*#Foo'
			)
		);

		$provider[] = array(
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			false,
			$linker,
			array(
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=!_:;@*_#Foo&amp;=_=Bar">http://example.org/api?query=!_:;@* #Foo&amp;= =Bar</a>',
				'longWikiText'  => '[http://example.org/api?query=!_:;@*_#Foo&=_=Bar http://example.org/api?query=!_:;@* #Foo&= =Bar]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=!_:;@*_#Foo&amp;=_=Bar">http://example.org/api?query=!_:;@* #Foo&amp;= =Bar</a>',
				'shortWikiText' => '[http://example.org/api?query=!_:;@*_#Foo&=_=Bar http://example.org/api?query=!_:;@* #Foo&= =Bar]'
			)
		);

		$provider[] = array(
			'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
			'&!_:;@* #Foo',
			$linker,
			array(
				'wikiValue'     => 'http://example.org/api?query=!_:;@* #Foo&=%20-3DBar',
				'longHTMLText'  => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=!_:;@*_#Foo&amp;=_=Bar">http://example.org/api?query=!_:;@* #Foo&amp;= =Bar</a>',
				'longWikiText'  => '[http://example.org/api?query=!_:;@*_#Foo&=_=Bar http://example.org/api?query=!_:;@* #Foo&= =Bar]',
				'shortHTMLText' => '<a class="external"' . $noFollowAttribute . ' href="http://example.org/api?query=!_:;@*_#Foo&amp;=_=Bar">&amp;!_:;@* #Foo</a>',
				'shortWikiText' => '[http://example.org/api?query=!_:;@*_#Foo&=_=Bar &!_:;@* #Foo]'
			)
		);

		return $provider;
	}

}
