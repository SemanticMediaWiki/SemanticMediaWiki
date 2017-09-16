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
			'Suspendisse &#91;&#91;Bar::tincidunt semper|abc]] facilisi'
		);

		$provider[] = array(
			'Suspendisse [[Bar::tincidunt semper]] facilisi',
			'Suspendisse tincidunt semper facilisi',
			'Suspendisse &#91;&#91;Bar::tincidunt semper]] facilisi'
		);

		$provider[] = array(
			'Suspendisse [[:Tincidunt semper|tincidunt semper]]',
			'Suspendisse [[:Tincidunt semper|tincidunt semper]]',
			'Suspendisse [[:Tincidunt semper|tincidunt semper]]'
		);

		$provider[] = array(
			'[[Foo::Foobar::テスト]] [[Bar:::ABC|DEF]] [[Foo:::0049 30 12345678/::Foo]]',
			'Foobar::テスト DEF :0049 30 12345678/::Foo',
			'&#91;&#91;Foo::Foobar::テスト]] &#91;&#91;Bar:::ABC|DEF]] &#91;&#91;Foo:::0049 30 12345678/::Foo]]'
		);

		$provider[] = array(
			'%5B%5BFoo%20Bar::foobaz%5D%5D',
			'foobaz',
			'&#91;&#91;Foo%20Bar::foobaz]]'
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
			'&#91;&#91;Foo|Bar::Foobar]] &#91;&#91;File:Example.png|alt=Bar::Foobar|Caption]] &#91;&#91;File:Example.png|Bar::Foobar|link=Foo]]'
		);

		$provider[] = array(
			'[[Foo::@@@]] [[Bar::@@@|123]]',
			' 123',
			'&#91;&#91;Foo::@@@]] &#91;&#91;Bar::@@@|123]]'
		);

		$provider[] = array(
			'Suspendisse [[SMW::off]][[Bar::tincidunt semper|abc]] facilisi[[SMW::on]] [[Bar:::ABC|DEF]]',
			'Suspendisse abc facilisi DEF',
			'Suspendisse &#91;&#91;SMW::off]]&#91;&#91;Bar::tincidunt semper|abc]] facilisi&#91;&#91;SMW::on]] &#91;&#91;Bar:::ABC|DEF]]'
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
			'[[Foo]]'
		);

		$provider[] = array(
			'[[Foo|Bar]]',
			'[[Foo|Bar]]'
		);

		$provider[] = array(
			'[[Foo::[[Bar]]]]',
			'[[Foo::&#x005B;&#x005B;Bar&#x005D;&#x005D;]]'
		);

		$provider[] = array(
			'[[Foo::[[Foo|Bar]]]]',
			'[[Foo::&#x005B;&#x005B;Foo&#124;Bar&#x005D;&#x005D;]]'
		);

		return $provider;
	}

}
