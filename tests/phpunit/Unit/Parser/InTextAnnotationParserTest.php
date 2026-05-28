<?php

namespace SMW\Tests\Unit\Parser;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SMW\DataItems\Property;
use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\MediaWiki\StripMarkerDecoder;
use SMW\Parser\InTextAnnotationParser;
use SMW\Parser\LinksProcessor;
use SMW\ParserData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Parser\InTextAnnotationParser
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class InTextAnnotationParserTest extends TestCase {

	private $semanticDataValidator;
	private $stringValidator;
	private $testEnvironment;
	private $linksProcessor;
	private $magicWordsFinder;
	private $hookContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();

		$this->linksProcessor = new LinksProcessor();

		$this->magicWordsFinder = $this->getMockBuilder( MagicWordsFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testCanConstruct( $namespace ) {
		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( RedirectTargetFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, $namespace );

		$instance =	new InTextAnnotationParser(
			new ParserData( $title, $parserOutput ),
			$this->linksProcessor,
			$this->magicWordsFinder,
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
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$magicWordsFinder = ApplicationFactory::getInstance()->create( 'MagicWordsFinder', $parserData->getOutput() );

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			$magicWordsFinder,
			new RedirectTargetFinder()
		);

		$instance->setHookContainer(
			$this->hookContainer
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
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$this->testEnvironment->withConfiguration( $settings );

		// Read post-normalization so downstream bitwise checks see the
		// integer bitmask regardless of whether the data provider supplied
		// the new array form or a legacy SMW_PARSER_* integer constant.
		$parserFeatures = (int)ApplicationFactory::getInstance()->getSettings()->get( 'smwgParserFeatures' );

		$this->linksProcessor->isStrictMode( $parserFeatures );

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			$this->magicWordsFinder,
			new RedirectTargetFinder()
		);

		$instance->setHookContainer(
			$this->hookContainer
		);

		$instance->showErrors( $parserFeatures );

		$instance->isLinksInValues(
			( $parserFeatures & SMW_PARSER_LINV ) === SMW_PARSER_LINV
		);

		$instance->parse( $text );

		$this->stringValidator->assertThatStringContains(
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
			'property'       => new Property( '_REDI' ),
			'propertyValues' => [ 'Lala' ]
		];

		$settings = [
			'smwgNamespacesWithSemanticLinks' => [ $namespace => true ],
			'smwgParserFeatures' => [ 'inline-errors' ],
		];

		$this->testEnvironment->withConfiguration(
			$settings
		);

		$parserData = new ParserData(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$redirectTargetFinder = new RedirectTargetFinder();

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			$this->magicWordsFinder,
			$redirectTargetFinder
		);

		$instance->setHookContainer(
			$this->hookContainer
		);

		$instance->parse( $text );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testRedirectAnnotationFromInjectedRedirectTarget() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		$namespace = NS_MAIN;
		$text      = '';
		$redirectTarget = $titleFactory->newFromText( 'Foo' );

		$expected = [
			'propertyCount'  => 1,
			'property'       => new Property( '_REDI' ),
			'propertyValues' => [ 'Foo' ]
		];

		$settings = [
			'smwgNamespacesWithSemanticLinks' => [ $namespace => true ],
			'smwgParserFeatures' => [ 'inline-errors' ],
		];

		$this->testEnvironment->withConfiguration(
			$settings
		);

		$parserData = new ParserData(
			$titleFactory->newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$redirectTargetFinder = new RedirectTargetFinder();

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			$this->magicWordsFinder,
			$redirectTargetFinder
		);

		$instance->setHookContainer(
			$this->hookContainer
		);

		$instance->setRedirectTarget( $redirectTarget );
		$instance->parse( $text );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testStripMarkerDecoding() {
		$redirectTargetFinder = $this->getMockBuilder( RedirectTargetFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$stripMarkerDecoder = $this->getMockBuilder( StripMarkerDecoder::class )
			->disableOriginalConstructor()
			->setMethods( [ 'canUse', 'hasStripMarker', 'unstrip' ] )
			->getMock();

		$stripMarkerDecoder->expects( $this->once() )
			->method( 'canUse' )
			->willReturn( true );

		$stripMarkerDecoder->expects( $this->once() )
			->method( 'hasStripMarker' )
			->willReturn( 1 );

		$stripMarkerDecoder->expects( $this->once() )
			->method( 'unstrip' )
			->with( $this->stringContains( '<nowiki>Bar</nowiki>' ) )
			->willReturn( 'Bar' );

		$parserData = new ParserData(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			$this->magicWordsFinder,
			$redirectTargetFinder
		);

		$text = '[[Foo::<nowiki>Bar</nowiki>]]';

		$instance->setHookContainer(
			$this->hookContainer
		);

		$instance->setStripMarkerDecoder( $stripMarkerDecoder );
		$instance->parse( $text );

		$expected = [
			'propertyCount'  => 1,
			'property'       => new Property( 'Foo' ),
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
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new InTextAnnotationParser(
			$parserData,
			$this->linksProcessor,
			$this->magicWordsFinder,
			new RedirectTargetFinder()
		);

		$reflector = new ReflectionClass( InTextAnnotationParser::class );

		$method = $reflector->getMethod( 'process' );

		$result = $method->invoke( $instance, [] );
		$this->assertEmpty( $result );

		$result = $method->invoke( $instance, [ 'Test::foo', 'SMW', 'lula' ] );
		$this->assertEmpty( $result );

		$result = $method->invoke( $instance, [ 'Test::bar', 'SMW', 'on' ] );
		$this->assertEmpty( $result );

		$result = $method->invoke( $instance, [ 'Test::lula', 'SMW', 'off' ] );
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
				'smwgParserFeatures' => [ 'inline-errors' ],
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
				'smwgParserFeatures' => [ 'inline-errors', 'links-in-values' ],
			],
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[FooBar::dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::[[tincidunt semper]]]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::[http:://www/foo/9001] ]] et Donec.',
			[
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' [[:Dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
					' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
					' Aliquam {{volutpat}} arcu ultrices eu Ut quis' .
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
				'smwgParserFeatures' => [ 'inline-errors' ],
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
				'smwgParserFeatures' => [],
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
				'smwgParserFeatures' => [ 'inline-errors' ],
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
				'smwgParserFeatures' => [ 'inline-errors' ],
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
				'smwgParserFeatures' => [ 'inline-errors' ],
			],
			'[[Foo::?bar]], [[Foo::Baz?]], [[Quxey::B?am]]',
			[
				'resultText'     => '[[:?bar|?bar]], [[:Baz?|Baz?]], [[:B?am|B?am]]',
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Foo', 'Quxey' ],
				'propertyValues' => [ '?bar', 'Baz?', 'B?am' ]
			]
		];

		# 7 673

		// Special:Types/Number
		$provider[] = [
			SMW_NS_PROPERTY,
			[
				'smwgNamespacesWithSemanticLinks' => [ SMW_NS_PROPERTY => true ],
				'smwgParserFeatures' => [ 'inline-errors' ],
			],
			'[[has type::number]], [[has Type::page]] ',
			[
				// Special:Types/Number -> .*/Number
				'resultText'     => "[[.*/Number|number]], [[:Page|page]]",
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Has type', 'Has Type' ],
				'propertyValues' => [ 'Number', 'Page' ]
			]
		];

		# 8 1048, Double-double
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => [ 'inline-errors' ],
			],
			'[[Foo::Bar::Foobar]], [[IPv6::fc00:123:8000::/64]] [[ABC::10.1002/::AID-MRM16::]]',
			[
				'resultText'     => '[[:Bar::Foobar|Bar::Foobar]], [[:Fc00:123:8000::/64|fc00:123:8000::/64]] [[:10.1002/::AID-MRM16::|10.1002/::AID-MRM16::]]',
				'propertyCount'  => 3,
				'propertyLabels' => [ 'Foo', 'IPv6', 'ABC' ],
				'propertyValues' => [ 'Bar::Foobar', 'Fc00:123:8000::/64', '10.1002/::AID-MRM16::' ]
			]
		];

		# 9 T32603
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => [ 'inline-errors' ],
			],
			'[[Foo:::Foobar]] [[Bar:::ABC|DEF]] [[Foo:::0049 30 12345678/::Foo]] ',
			[
				'resultText'     => '[[:Foobar|Foobar]] [[:ABC|DEF]] [[:0049 30 12345678/::Foo|0049 30 12345678/::Foo]]',
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Foo', 'Bar' ],
				'propertyValues' => [ 'Foobar', '0049 30 12345678/::Foo', 'ABC' ]
			]
		];

		# 10 #1252 (disabled strict mode)
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => []
			],
			'[[Foo::Foobar::テスト]] [[Bar:::ABC|DEF]] [[Foo:::0049 30 12345678/::Foo]] ',
			[
				'resultText'     => '[[:テスト|テスト]] [[:ABC|DEF]] [[:Foo|Foo]]',
				'propertyCount'  => 4,
				'propertyLabels' => [ 'Foo', 'Bar:', 'Foobar', ':0049 30 12345678/' ],
				'propertyValues' => [ 'Foobar', 'Foo', 'ABC', 'テスト' ]
			]
		];

		# 11 #1747 (left pipe)
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => [ 'inline-errors' ]
			],
			'[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[File:Example.png|Bar::Foobar|link=Foo]]',
			[
				'resultText'     => '[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[File:Example.png|Bar::Foobar|link=Foo]]',
				'propertyCount'  => 0,
			]
		];

		# 12 #1747 (left pipe + including one annotation)
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => [ 'inline-errors', 'strict' ]
			],
			'[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[Foo::Foobar::テスト]] [[File:Example.png|Bar::Foobar|link=Foo]]',
			[
				'resultText'     => '[[Foo|Bar::Foobar]] [[File:Example.png|alt=Bar::Foobar|Caption]] [[:Foobar::テスト|Foobar::テスト]] [[File:Example.png|Bar::Foobar|link=Foo]]',
				'propertyCount'  => 1,
				'propertyLabels' => [ 'Foo' ],
				'propertyValues' => [ 'Foobar::テスト' ]
			]
		];

		# 13 @@@ syntax
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => [ 'inline-errors', 'strict' ]
			],
			'[[Foo::@@@]] [[Bar::@@@en|Foobar]]',
			[
				'resultText'     => $testEnvironment->replaceNamespaceWithLocalizedText( SMW_NS_PROPERTY, '<span class="smw-property">[[:Property:Foo|Foo]]</span> <span class="smw-property">[[:Property:Bar|Foobar]]</span>' ),
				'propertyCount'  => 0
			]
		];

		# 14 @@@|# syntax (#4037)
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => [ 'inline-errors', 'strict' ]
			],
			'[[Foo::@@@|#]] [[Bar::@@@en|#]]',
			[
				'resultText'     => '<span class="smw-property nolink">Foo</span> <span class="smw-property nolink">Bar</span>',
				'propertyCount'  => 0
			]
		];

		# 15 [ ... ] in-text link
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => [ 'inline-errors', 'strict', 'links-in-values' ]
			],
			'[[Text::Bar [http://example.org/Foo Foo]]] [[Code::Foo[1] Foobar]]',
			[
				'resultText'     => 'Bar [http://example.org/Foo Foo] <div class="smwpre">Foo&#91;1]&#160;Foobar</div>',
				'propertyCount'  => 2,
				'propertyLabels' => [ 'Text', 'Code' ],
				'propertyValues' => [ 'Bar [http://example.org/Foo Foo]', 'Foo[1] Foobar' ]
			]
		];

		# 16 (#2671) external [] decode use
		$provider[] = [
			NS_MAIN,
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgParserFeatures' => [ 'inline-errors', 'strict', 'links-in-values' ]
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
