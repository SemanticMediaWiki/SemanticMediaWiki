<?php

namespace SMW\Tests\Parser;

use ParserOutput;
use ReflectionClass;
use SMW\DIProperty;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\Parser\InTextAnnotationParser;
use SMW\Parser\LinksProcessor;
use SMW\ParserData;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\Parser\InTextAnnotationParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InTextAnnotationParserTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $testEnvironment;
	private $linksProcessor;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->linksProcessor = new LinksProcessor();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testCanConstruct( $namespace ) {

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( 'SMW\MediaWiki\RedirectTargetFinder' )
			->disableOriginalConstructor()
			->getMock();

		$title = Title::newFromText( __METHOD__, $namespace );

		$instance =	new InTextAnnotationParser(
			new ParserData( $title, $parserOutput ),
			$this->linksProcessor,
			new MagicWordsFinder(),
			$redirectTargetFinder
		);

		$this->assertInstanceOf(
			InTextAnnotationParser::class,
			$instance
		);
	}

	public function testHasMarker() {

		$this->assertTrue(
			InTextAnnotationParser::hasMarker( '[[SMW::off]]' )
		);

		$this->assertTrue(
			InTextAnnotationParser::hasMarker( '[[SMW::on]]' )
		);

		$this->assertFalse(
			InTextAnnotationParser::hasMarker( 'Foo' )
		);
	}

	/**
	 * @dataProvider magicWordDataProvider
	 */
	public function testStripMagicWords( $namespace, $text, array $expected ) {

		$parserData = new ParserData(
			Title::newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$magicWordsFinder = new MagicWordsFinder( $parserData->getOutput() );

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			$magicWordsFinder,
			new RedirectTargetFinder()
		);

		$instance->parse( $text );

		$this->assertEquals(
			$expected,
			$magicWordsFinder->getMagicWords()
		);
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testTextParse( $namespace, array $settings, $text, array $expected ) {

		$parserData = new ParserData(
			Title::newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$this->linksProcessor->isStrictMode(
			isset( $settings['smwgParserFeatures'] ) ? $settings['smwgParserFeatures'] : true
		);

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			new MagicWordsFinder(),
			new RedirectTargetFinder()
		);

		$instance->showErrors(
			isset( $settings['smwgParserFeatures'] ) ? $settings['smwgParserFeatures'] : true
		);

		$instance->isLinksInValues(
			( ( $settings['smwgParserFeatures'] & SMW_PARSER_LINV ) == SMW_PARSER_LINV )
		);

		$this->testEnvironment->withConfiguration(
			$settings
		);

		$instance->parse( $text );

		$this->assertContains(
			$expected['resultText'],
			$text
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testRedirectAnnotationFromText() {

		$namespace = NS_MAIN;
		$text      = '#REDIRECT [[:Lala]]';

		$expected = [
			'propertyCount'  => 1,
			'property'       => new DIProperty( '_REDI' ),
			'propertyValues' => [ 'Lala' ]
		];

		$settings = [
			'smwgNamespacesWithSemanticLinks' => [ $namespace => true ],
			'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
		];

		$this->testEnvironment->withConfiguration(
			$settings
		);

		$parserData = new ParserData(
			Title::newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$redirectTargetFinder = new RedirectTargetFinder();

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			new MagicWordsFinder(),
			$redirectTargetFinder
		);

		$instance->parse( $text );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testRedirectAnnotationFromInjectedRedirectTarget() {

		$namespace = NS_MAIN;
		$text      = '';
		$redirectTarget = Title::newFromText( 'Foo' );

		$expected = [
			'propertyCount'  => 1,
			'property'       => new DIProperty( '_REDI' ),
			'propertyValues' => [ 'Foo' ]
		];

		$settings = [
			'smwgNamespacesWithSemanticLinks' => [ $namespace => true ],
			'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
		];

		$this->testEnvironment->withConfiguration(
			$settings
		);

		$parserData = new ParserData(
			Title::newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$redirectTargetFinder = new RedirectTargetFinder();

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			new MagicWordsFinder(),
			$redirectTargetFinder
		);

		$instance->setRedirectTarget( $redirectTarget );
		$instance->parse( $text );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testStripMarkerDecoding() {

		$redirectTargetFinder = $this->getMockBuilder( 'SMW\MediaWiki\RedirectTargetFinder' )
			->disableOriginalConstructor()
			->getMock();

		$stripMarkerDecoder = $this->getMockBuilder( '\SMW\MediaWiki\StripMarkerDecoder' )
			->disableOriginalConstructor()
			->setMethods( [ 'canUse', 'hasStripMarker', 'unstrip' ] )
			->getMock();

		$stripMarkerDecoder->expects( $this->once() )
			->method( 'canUse' )
			->will( $this->returnValue( true ) );

		$stripMarkerDecoder->expects( $this->once() )
			->method( 'hasStripMarker' )
			->will( $this->returnValue( true ) );

		$stripMarkerDecoder->expects( $this->once() )
			->method( 'unstrip' )
			->with( $this->stringContains( '<nowiki>Bar</nowiki>' ) )
			->will( $this->returnValue( 'Bar' ) );

		$parserData = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			new MagicWordsFinder(),
			$redirectTargetFinder
		);

		$text = '[[Foo::<nowiki>Bar</nowiki>]]';

		$instance->setStripMarkerDecoder( $stripMarkerDecoder );
		$instance->parse( $text );

		$expected = [
			'propertyCount'  => 1,
			'property'       => new DIProperty( 'Foo' ),
			'propertyValues' => [ 'Bar' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);

		$this->assertEquals(
			'<nowiki>Bar</nowiki>',
			$text
		);
	}

	public function testProcessOnReflection() {

		$parserData = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			new MagicWordsFinder(),
			new RedirectTargetFinder()
		);

		$reflector = new ReflectionClass( '\SMW\Parser\InTextAnnotationParser' );

		$method = $reflector->getMethod( 'process' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, [] );
		$this->assertEmpty( $result );

		$result = $method->invoke( $instance, [ 'Test::foo', 'SMW' , 'lula' ] );
		$this->assertEmpty( $result );

		$result = $method->invoke( $instance, [ 'Test::bar', 'SMW' , 'on' ] );
		$this->assertEmpty( $result );

		$result = $method->invoke( $instance, [ 'Test::lula', 'SMW' , 'off' ] );
		$this->assertEmpty( $result );
	}

	/**
	 * @dataProvider stripTextWithAnnotationProvider
	 */
	public function testStrip( $text, $expectedRemoval, $expectedObscuration ) {

		$this->assertEquals(
			$expectedRemoval,
			InTextAnnotationParser::removeAnnotation( $text )
		);

		$this->assertEquals(
			$expectedObscuration,
			InTextAnnotationParser::obfuscateAnnotation( $text )
		);
	}

	public function stripTextWithAnnotationProvider() {

		$provider = [];

		$provider[] = [
			'Suspendisse [[Bar::tincidunt semper|abc]] facilisi',
			'Suspendisse abc facilisi',
			'Suspendisse &#91;&#91;Bar::tincidunt semper|abc]] facilisi'
		];

		return $provider;
	}

	public function textDataProvider() {

		$testEnvironment = new TestEnvironment();
		$provider = [];

		// #0 NS_MAIN; [[FooBar...]] with a different caption
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
			],
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[FooBar::dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
			[
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' [[:Dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
					' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
					' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[:9001|9001]] et Donec.',
				'propertyCount'  => 3,
				'propertyLabels' => [ 'Foo', 'Bar', 'FooBar' ],
				'propertyValues' => [ 'Dictumst', 'Tincidunt semper', '9001' ]
			]
		];

		// #1 NS_MAIN; [[FooBar...]] with a different caption and enabled SMW_PARSER_LINV
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR | SMW_PARSER_LINV,
			],
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[FooBar::dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::[[tincidunt semper]]]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::[http:://www/foo/9001] ]] et Donec.',
			[
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' [[:Dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
					' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
					' Aliquam {{volutpat}} arcu ultrices eu Ut quis'.
					' [[:Http:://www/foo/9001|http:://www/foo/9001]] et Donec.',
				'propertyCount'  => 3,
				'propertyLabels' => [ 'Foo', 'Bar', 'FooBar' ],
				'propertyValues' => [ 'Dictumst', 'Tincidunt semper', 'Http:://www/foo/9001' ]
			]
		];

		// #2 NS_MAIN, [[-FooBar...]] produces an error with inlineErrors = true
		// (only check for an indication of an error in 'resultText' )
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
			],
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[-FooBar::dictumst|重い]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
			[
				'resultText'     => 'class="smw-highlighter" data-type="4" data-state="inline"',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 3,
				'propertyKeys' => [ 'Foo', 'Bar', '_ERRC' ],
				'propertyValues' => [ 'Tincidunt semper', '9001' ]
			]
		];

		// #3 NS_MAIN, [[-FooBar...]] produces an error but inlineErrors = false
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_NONE,
			],
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[-FooBar::dictumst|軽い]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
			[
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' 軽い cursus. Nisl sit condimentum Quisque facilisis' .
					' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
					' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[:9001|9001]] et Donec.',
				'strictPropertyValueMatch' => false,
				'propertyCount'  => 3,
				'propertyKeys  ' => [ 'Foo', 'Bar', '_ERRC' ],
				'propertyValues' => [ 'Tincidunt semper', '9001' ]
			]
		];

		// #4 NS_HELP disabled
		$provider[] = [
			NS_HELP,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_HELP => false ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
			],
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[FooBar::dictumst|おもろい]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
			[
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' [[:Dictumst|おもろい]] cursus. Nisl sit condimentum Quisque facilisis' .
					' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
					' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[:9001|9001]] et Donec.',
				'propertyCount'  => 0,
				'propertyLabels' => [],
				'propertyValues' => []
			]
		];

		// #5 NS_HELP enabled but no properties or links at all
		$provider[] = [
			NS_HELP,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_HELP => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
			],
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' Suspendisse tincidunt semper facilisi dolor Aenean.',
			[
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' Suspendisse tincidunt semper facilisi dolor Aenean.',
				'propertyCount'  => 0,
				'propertyLabels' => [],
				'propertyValues' => []
			]
		];

		// #6 Bug 54967
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
			],
			'[[Foo::?bar]], [[Foo::Baz?]], [[Quxey::B?am]]',
			[
				'resultText'     => '[[:?bar|?bar]], [[:Baz?|Baz?]], [[:B?am|B?am]]',
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Foo', 'Quxey' ],
				'propertyValues' => [ '?bar', 'Baz?', 'B?am' ]
			]
		];

		#7 673

		// Special:Types/Number
		$specialTypeName = \SpecialPage::getTitleFor( 'Types', 'Number' )->getPrefixedText();

		$provider[] = [
			SMW_NS_PROPERTY,
			[
				'smwgNamespacesWithSemanticLinks' => [ SMW_NS_PROPERTY => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
			],
			'[[has type::number]], [[has Type::page]] ',
			[
				'resultText'     => "[[$specialTypeName|number]], [[:Page|page]]",
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Has type', 'Has Type' ],
				'propertyValues' => [ 'Number', 'Page' ]
			]
		];

		#8 1048, Double-double
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
			],
			'[[Foo::Bar::Foobar]], [[IPv6::fc00:123:8000::/64]] [[ABC::10.1002/::AID-MRM16::]]',
			[
				'resultText'     => '[[:Bar::Foobar|Bar::Foobar]], [[:Fc00:123:8000::/64|fc00:123:8000::/64]] [[:10.1002/::AID-MRM16::|10.1002/::AID-MRM16::]]',
				'propertyCount'  => 3,
				'propertyLabels' => [ 'Foo', 'IPv6', 'ABC' ],
				'propertyValues' => [ 'Bar::Foobar', 'Fc00:123:8000::/64', '10.1002/::AID-MRM16::' ]
			]
		];

		#9 T32603
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR,
			],
			'[[Foo:::Foobar]] [[Bar:::ABC|DEF]] [[Foo:::0049 30 12345678/::Foo]] ',
			[
				'resultText'     => '[[:Foobar|Foobar]] [[:ABC|DEF]] [[:0049 30 12345678/::Foo|0049 30 12345678/::Foo]]',
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Foo', 'Bar' ],
				'propertyValues' => [ 'Foobar', '0049 30 12345678/::Foo', 'ABC' ]
			]
		];

		#10 #1252 (disabled strict mode)
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_NONE
			],
			'[[Foo::Foobar::テスト]] [[Bar:::ABC|DEF]] [[Foo:::0049 30 12345678/::Foo]] ',
			[
				'resultText'     => '[[:テスト|テスト]] [[:ABC|DEF]] [[:Foo|Foo]]',
				'propertyCount'  => 4,
				'propertyLabels' => [ 'Foo', 'Bar:', 'Foobar', ':0049 30 12345678/' ],
				'propertyValues' => [ 'Foobar', 'Foo', 'ABC', 'テスト' ]
			]
		];

		#11 #1747 (left pipe)
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR | SMW_PARSER_NONE
			],
			'[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[File:Example.png|Bar::Foobar|link=Foo]]',
			[
				'resultText'     => '[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[File:Example.png|Bar::Foobar|link=Foo]]',
				'propertyCount'  => 0,
			]
		];

		#12 #1747 (left pipe + including one annotation)
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR | SMW_PARSER_STRICT
			],
			'[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[Foo::Foobar::テスト]] [[File:Example.png|Bar::Foobar|link=Foo]]',
			[
				'resultText'     => '[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[:Foobar::テスト|Foobar::テスト]] [[File:Example.png|Bar::Foobar|link=Foo]]',
				'propertyCount'  => 1,
				'propertyLabels' => [ 'Foo' ],
				'propertyValues' => [ 'Foobar::テスト' ]
			]
		];

		#13 @@@ syntax
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR | SMW_PARSER_STRICT
			],
			'[[Foo::@@@]] [[Bar::@@@en|Foobar]]',
			[
				'resultText'     => $testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, '[[:Property:Foo|Foo]] [[:Property:Bar|Foobar]]' ),
				'propertyCount'  => 0
			]
		];

		#14 [ ... ] in-text link
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR | SMW_PARSER_STRICT | SMW_PARSER_LINV
			],
			'[[Text::Bar [http://example.org/Foo Foo]]] [[Code::Foo[1] Foobar]]',
			[
				'resultText'     => 'Bar [http://example.org/Foo Foo] <div class="smwpre">Foo&#91;1]&#160;Foobar</div>',
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Text', 'Code' ],
				'propertyValues' => [ 'Bar [http://example.org/Foo Foo]', 'Foo[1] Foobar' ]
			]
		];

		#15 (#2671) external [] decode use
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => SMW_PARSER_INL_ERROR | SMW_PARSER_STRICT | SMW_PARSER_LINV
			],
			'<sup id="cite_ref-1" class="reference">[[#cite_note-1|&#91;1&#93;]]</sup>',
			[
				'resultText' => '<sup id="cite_ref-1" class="reference">[[#cite_note-1|&#91;1&#93;]]</sup>'
			]
		];

		return $provider;
	}

	/**
	 * @return array
	 */
	public function magicWordDataProvider() {

		$provider = [];

		// #0 __NOFACTBOX__
		$provider[] = [
			NS_MAIN,
			'Lorem ipsum dolor [[Foo::dictumst cursus]] facilisi __NOFACTBOX__',
			[ 'SMW_NOFACTBOX' ]
		];

		// #1 __SHOWFACTBOX__
		$provider[] = [
			NS_HELP,
			'Lorem ipsum dolor [[Foo::dictumst cursus]] facilisi __SHOWFACTBOX__',
			[ 'SMW_SHOWFACTBOX' ]
		];

		// #2 __NOFACTBOX__, __SHOWFACTBOX__
		$provider[] = [
			NS_HELP,
			'Lorem ipsum dolor [[Foo::dictumst cursus]] facilisi __NOFACTBOX__ __SHOWFACTBOX__',
			[ 'SMW_NOFACTBOX', 'SMW_SHOWFACTBOX' ]
		];

		// #3 __SHOWFACTBOX__, __NOFACTBOX__
		$provider[] = [
			NS_HELP,
			'Lorem ipsum dolor [[Foo::dictumst cursus]] facilisi __SHOWFACTBOX__ __NOFACTBOX__',
			[ 'SMW_NOFACTBOX', 'SMW_SHOWFACTBOX' ]
		];
		return $provider;
	}

}
