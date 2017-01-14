<?php

namespace SMW\Tests\Parser;

use SMW\Parser\Obfuscator;

/**
 * @covers \SMW\Parser\Obfuscator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ObfuscatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider obfuscateProvider
	 */
	public function testRoundTripLinkObfuscation( $text ) {

		$newText = Obfuscator::encodeLinks( $text );

		$this->assertEquals(
			$text,
			Obfuscator::removeLinkObfuscation( $newText )
		);
	}

	/**
	 * @dataProvider obfuscateProvider
	 */
	public function testObfuscateLinks( $text, $expected ) {

		$inTextAnnotationParser = $this->getMockBuilder( 'SMW\InTextAnnotationParser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertEquals(
			$expected,
			Obfuscator::obfuscateLinks( $text, $inTextAnnotationParser )
		);
	}

	/**
	 * @dataProvider stripTextWithAnnotationProvider
	 */
	public function testStrip( $text, $expectedRemoval, $expectedObscuration ) {

		$this->assertEquals(
			$expectedRemoval,
			Obfuscator::removeAnnotation( $text )
		);

		$this->assertEquals(
			$expectedObscuration,
			Obfuscator::obfuscateAnnotation( $text )
		);
	}

	public function stripTextWithAnnotationProvider() {

		$provider = array();

		$provider[] = array(
			'Suspendisse [[Bar::tincidunt semper|abc]] facilisi',
			'Suspendisse abc facilisi',
			'Suspendisse &#x005B;&#x005B;Bar::tincidunt semper|abc]] facilisi'
		);

		$provider[] = array(
			'Suspendisse [[Bar::tincidunt semper]] facilisi',
			'Suspendisse tincidunt semper facilisi',
			'Suspendisse &#x005B;&#x005B;Bar::tincidunt semper]] facilisi'
		);

		$provider[] = array(
			'Suspendisse [[:Tincidunt semper|tincidunt semper]]',
			'Suspendisse [[:Tincidunt semper|tincidunt semper]]',
			'Suspendisse [[:Tincidunt semper|tincidunt semper]]'
		);

		$provider[] = array(
			'[[Foo::Foobar::テスト]] [[Bar:::ABC|DEF]] [[Foo:::0049 30 12345678/::Foo]]',
			'Foobar::テスト DEF :0049 30 12345678/::Foo',
			'&#x005B;&#x005B;Foo::Foobar::テスト]] &#x005B;&#x005B;Bar:::ABC|DEF]] &#x005B;&#x005B;Foo:::0049 30 12345678/::Foo]]'
		);

		$provider[] = array(
			'%5B%5BFoo%20Bar::foobaz%5D%5D',
			'foobaz',
			'&#x005B;&#x005B;Foo%20Bar::foobaz]]'
		);

		$provider[] = array(
			'Suspendisse tincidunt semper facilisi',
			'Suspendisse tincidunt semper facilisi',
			'Suspendisse tincidunt semper facilisi'
		);

		// #1747
		$provider[] = array(
			'[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[File:Example.png|Bar::Foobar|link=Foo]]',
			'[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[File:Example.png|Bar::Foobar|link=Foo]]',
			'&#x005B;&#x005B;Foo|Bar::Foobar]] &#x005B;&#x005B;File:Example.png|alt=Bar::Foobar|Caption]] &#x005B;&#x005B;File:Example.png|Bar::Foobar|link=Foo]]'
		);

		$provider[] = array(
			'[[Foo::@@@]] [[Bar::@@@|123]]',
			' 123',
			'&#x005B;&#x005B;Foo::@@@]] &#x005B;&#x005B;Bar::@@@|123]]'
		);

		$provider[] = array(
			'Suspendisse [[SMW::off]][[Bar::tincidunt semper|abc]] facilisi[[SMW::on]] [[Bar:::ABC|DEF]]',
			'Suspendisse abc facilisi DEF',
			'Suspendisse &#x005B;&#x005B;SMW::off]]&#x005B;&#x005B;Bar::tincidunt semper|abc]] facilisi&#x005B;&#x005B;SMW::on]] &#x005B;&#x005B;Bar:::ABC|DEF]]'
		);

		return $provider;
	}

	public function obfuscateProvider() {

		$provider = array();

		$provider[] = array(
			'Foo',
			'Foo'
		);

		$provider[] = array(
			'[[Foo]]',
			'&#91;&#91;Foo&#93;&#93;'
		);

		$provider[] = array(
			'[[Foo|Bar]]',
			'&#91;&#91;Foo&#124;Bar&#93;&#93;'
		);

		$provider[] = array(
			'[[Foo::[[Bar]]]]',
			'[[Foo::&#91;&#91;Bar&#93;&#93;]]'
		);

		return $provider;
	}

}
