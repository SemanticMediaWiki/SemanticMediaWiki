<?php

namespace SMW\Tests;

use SMW\Tests\Utils\Validators\SemanticDataValidator;

use SMW\MediaWiki\MagicWordsFinder;
use SMW\MediaWiki\RedirectTargetFinder;

use SMW\InTextAnnotationParser;
use SMW\ApplicationFactory;
use SMW\Settings;
use SMW\ParserData;
use SMW\DIProperty;

use Title;
use ParserOutput;
use ReflectionClass;

/**
 * @covers \SMW\InTextAnnotationParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InTextAnnotationParserTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = new SemanticDataValidator();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

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
			new MagicWordsFinder(),
			$redirectTargetFinder
		);

		$this->assertInstanceOf(
			'\SMW\InTextAnnotationParser',
			$instance
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

		$instance = new InTextAnnotationParser(
			$parserData,
			new MagicWordsFinder(),
			new RedirectTargetFinder()
		);

		$instance->setStrictModeState(
			isset( $settings['smwgEnabledInTextAnnotationParserStrictMode'] ) ? $settings['smwgEnabledInTextAnnotationParserStrictMode'] : true
		);

		$this->applicationFactory->registerObject(
			'Settings',
			Settings::newFromArray( $settings )
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

		$expected = array(
			'propertyCount'  => 1,
			'property'       => new DIProperty( '_REDI' ),
			'propertyValues' => array( 'Lala' )
		);

		$settings = array(
			'smwgNamespacesWithSemanticLinks' => array( $namespace => true ),
			'smwgLinksInValues' => false,
			'smwgInlineErrors'  => true,
		);


		$this->applicationFactory->registerObject(
			'Settings',
			Settings::newFromArray( $settings )
		);

		$parserData = new ParserData(
			Title::newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$redirectTargetFinder = new RedirectTargetFinder();

		$instance = new InTextAnnotationParser(
			$parserData,
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

		$expected = array(
			'propertyCount'  => 1,
			'property'       => new DIProperty( '_REDI' ),
			'propertyValues' => array( 'Foo' )
		);

		$settings = array(
			'smwgNamespacesWithSemanticLinks' => array( $namespace => true ),
			'smwgLinksInValues' => false,
			'smwgInlineErrors'  => true,
		);

		$this->applicationFactory->registerObject(
			'Settings',
			Settings::newFromArray( $settings )
		);

		$parserData = new ParserData(
			Title::newFromText( __METHOD__, $namespace ),
			new ParserOutput()
		);

		$redirectTargetFinder = new RedirectTargetFinder();

		$instance = new InTextAnnotationParser(
			$parserData,
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

	public function testProcessOnReflection() {

		$parserData = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new InTextAnnotationParser(
			$parserData,
			new MagicWordsFinder(),
			new RedirectTargetFinder()
		);

		$reflector = new ReflectionClass( '\SMW\InTextAnnotationParser' );

		$method = $reflector->getMethod( 'process' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, array() );
		$this->assertEmpty( $result );

		$result = $method->invoke( $instance, array( 'Test::foo', 'SMW' , 'lula' ) );
		$this->assertEmpty( $result );

		$result = $method->invoke( $instance, array( 'Test::bar', 'SMW' , 'on' ) );
		$this->assertEmpty( $result );

		$result = $method->invoke( $instance, array( 'Test::lula', 'SMW' , 'off' ) );
		$this->assertEmpty( $result );
	}

	public function textDataProvider() {

		$provider = array();

		// #0 NS_MAIN; [[FooBar...]] with a different caption
		$provider[] = array(
			NS_MAIN,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors' => true,
			),
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[FooBar::dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
			array(
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' [[:Dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
					' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
					' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[:9001|9001]] et Donec.',
				'propertyCount'  => 3,
				'propertyLabels' => array( 'Foo', 'Bar', 'FooBar' ),
				'propertyValues' => array( 'Dictumst', 'Tincidunt semper', '9001' )
			)
		);

		// #1 NS_MAIN; [[FooBar...]] with a different caption and smwgLinksInValues = true
		$provider[] = array(
			NS_MAIN,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgLinksInValues' => true,
				'smwgInlineErrors'  => true,
			),
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[FooBar::dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::[[tincidunt semper]]]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::[http:://www/foo/9001] ]] et Donec.',
			array(
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' [[:Dictumst|寒い]] cursus. Nisl sit condimentum Quisque facilisis' .
					' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
					' Aliquam {{volutpat}} arcu ultrices eu Ut quis'.
					' [[:Http:://www/foo/9001|http:://www/foo/9001]] et Donec.',
				'propertyCount'  => 3,
				'propertyLabels' => array( 'Foo', 'Bar', 'FooBar' ),
				'propertyValues' => array( 'Dictumst', 'Tincidunt semper', 'Http:://www/foo/9001' )
			)
		);

		// #2 NS_MAIN, [[-FooBar...]] produces an error with inlineErrors = true
		// (only check for an indication of an error in 'resultText' )
		$provider[] = array(
			NS_MAIN,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors' => true,
			),
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[-FooBar::dictumst|重い]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
			array(
				'resultText'     => 'class="smw-highlighter" data-type="4" data-state="inline"',
				'propertyCount'  => 2,
				'propertyLabels' => array( 'Foo', 'Bar' ),
				'propertyValues' => array( 'Tincidunt semper', '9001' )
			)
		);

		// #3 NS_MAIN, [[-FooBar...]] produces an error but inlineErrors = false
		$provider[] = array(
			NS_MAIN,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors'  => false,
			),
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[-FooBar::dictumst|軽い]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
			array(
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' 軽い cursus. Nisl sit condimentum Quisque facilisis' .
					' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
					' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[:9001|9001]] et Donec.',
				'propertyCount'  => 2,
				'propertyLabels' => array( 'Foo', 'Bar' ),
				'propertyValues' => array( 'Tincidunt semper', '9001' )
			)
		);

		// #4 NS_HELP disabled
		$provider[] = array(
			NS_HELP,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_HELP => false ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors'  => true,
			),
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' [[FooBar::dictumst|おもろい]] cursus. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[foo::9001]] et Donec.',
			array(
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' [[:Dictumst|おもろい]] cursus. Nisl sit condimentum Quisque facilisis' .
					' Suspendisse [[:Tincidunt semper|tincidunt semper]] facilisi dolor Aenean. Ut' .
					' Aliquam {{volutpat}} arcu ultrices eu Ut quis [[:9001|9001]] et Donec.',
				'propertyCount'  => 0,
				'propertyLabels' => array(),
				'propertyValues' => array()
			)
		);

		// #5 NS_HELP enabled but no properties or links at all
		$provider[] = array(
			NS_HELP,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_HELP => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors'  => true,
			),
			'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
			' Suspendisse tincidunt semper facilisi dolor Aenean.',
			array(
				'resultText'    => 'Lorem ipsum dolor sit &$% consectetuer auctor at quis' .
					' Suspendisse tincidunt semper facilisi dolor Aenean.',
				'propertyCount'  => 0,
				'propertyLabels' => array(),
				'propertyValues' => array()
			)
		);

		// #6 Bug 54967
		$provider[] = array(
			NS_MAIN,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors'  => true,
			),
			'[[Foo::?bar]], [[Foo::Baz?]], [[Quxey::B?am]]',
			array(
				'resultText'     => '[[:?bar|?bar]], [[:Baz?|Baz?]], [[:B?am|B?am]]',
				'propertyCount'  => 2,
				'propertyLabels' => array( 'Foo', 'Quxey' ),
				'propertyValues' => array( '?bar', 'Baz?', 'B?am' )
			)
		);

		#7 673

		// Special:Types/Number
		$specialTypeName = \SpecialPage::getTitleFor( 'Types', 'Number' )->getPrefixedText();

		$provider[] = array(
			SMW_NS_PROPERTY,
			array(
				'smwgNamespacesWithSemanticLinks' => array( SMW_NS_PROPERTY => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors'  => true,
			),
			'[[has type::number]], [[has Type::page]] ',
			array(
				'resultText'     => "[[$specialTypeName|number]], [[:Page|page]]",
				'propertyCount'  => 2,
				'propertyLabels' => array( 'Has type', 'Has Type' ),
				'propertyValues' => array( 'Number', 'Page' )
			)
		);

		#8 1048, Double-double
		$provider[] = array(
			NS_MAIN,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors'  => true,
			),
			'[[Foo::Bar::Foobar]], [[IPv6::fc00:123:8000::/64]] [[ABC::10.1002/::AID-MRM16::]]',
			array(
				'resultText'     => '[[:Bar::Foobar|Bar::Foobar]], [[:Fc00:123:8000::/64|fc00:123:8000::/64]] [[:10.1002/::AID-MRM16::|10.1002/::AID-MRM16::]]',
				'propertyCount'  => 3,
				'propertyLabels' => array( 'Foo', 'IPv6', 'ABC' ),
				'propertyValues' => array( 'Bar::Foobar', 'Fc00:123:8000::/64', '10.1002/::AID-MRM16::' )
			)
		);

		#9 T32603
		$provider[] = array(
			NS_MAIN,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors'  => true,
			),
			'[[Foo:::Foobar]] [[Bar:::ABC|DEF]] [[Foo:::0049 30 12345678/::Foo]] ',
			array(
				'resultText'     => '[[:Foobar|Foobar]] [[:ABC|DEF]] [[:0049 30 12345678/::Foo|0049 30 12345678/::Foo]]',
				'propertyCount'  => 2,
				'propertyLabels' => array( 'Foo', 'Bar' ),
				'propertyValues' => array( 'Foobar', '0049 30 12345678/::Foo', 'ABC' )
			)
		);

		#10 #1252 (disabled strict mode)
		$provider[] = array(
			NS_MAIN,
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgLinksInValues' => false,
				'smwgInlineErrors'  => true,
				'smwgEnabledInTextAnnotationParserStrictMode' => false
			),
			'[[Foo::Foobar::テスト]] [[Bar:::ABC|DEF]] [[Foo:::0049 30 12345678/::Foo]] ',
			array(
				'resultText'     => '[[:テスト|テスト]] [[:ABC|DEF]] [[:Foo|Foo]]',
				'propertyCount'  => 4,
				'propertyLabels' => array( 'Foo', 'Bar:', 'Foobar', ':0049 30 12345678/' ),
				'propertyValues' => array( 'Foobar', 'Foo', 'ABC', 'テスト' )
			)
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function magicWordDataProvider() {

		$provider = array();

		// #0 __NOFACTBOX__
		$provider[] = array(
			NS_MAIN,
			'Lorem ipsum dolor [[Foo::dictumst cursus]] facilisi __NOFACTBOX__',
			array( 'SMW_NOFACTBOX' )
		);

		// #1 __SHOWFACTBOX__
		$provider[] = array(
			NS_HELP,
			'Lorem ipsum dolor [[Foo::dictumst cursus]] facilisi __SHOWFACTBOX__',
			array( 'SMW_SHOWFACTBOX' )
		);

		// #2 __NOFACTBOX__, __SHOWFACTBOX__
		$provider[] = array(
			NS_HELP,
			'Lorem ipsum dolor [[Foo::dictumst cursus]] facilisi __NOFACTBOX__ __SHOWFACTBOX__',
			array( 'SMW_NOFACTBOX', 'SMW_SHOWFACTBOX' )
		);

		// #3 __SHOWFACTBOX__, __NOFACTBOX__
		$provider[] = array(
			NS_HELP,
			'Lorem ipsum dolor [[Foo::dictumst cursus]] facilisi __SHOWFACTBOX__ __NOFACTBOX__',
			array( 'SMW_NOFACTBOX', 'SMW_SHOWFACTBOX' )
		);
		return $provider;
	}

}
