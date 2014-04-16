<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\ContentProcessor;
use SMW\ExtensionContext;
use SMW\Settings;
use SMW\ParserData;
use SMW\DIProperty;

use Title;
use ParserOutput;
use ReflectionClass;

/**
 * @covers \SMW\ContentProcessor
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ContentProcessorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider textDataProvider
	 */
	public function testCanConstruct( $namespace ) {

		$title = Title::newFromText( __METHOD__, $namespace );

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ContentProcessor',
			$this->acquireInstance( $title, $parserOutput )
		);
	}

	/**
	 * @dataProvider magicWordDataProvider
	 */
	public function testStripMagicWordsOnReflection( $namespace, $text, array $expected ) {

		$parserOutput = new ParserOutput();
		$title        = Title::newFromText( __METHOD__, $namespace );
		$instance     = $this->acquireInstance( $title, $parserOutput );

		$reflector = new ReflectionClass( '\SMW\ContentProcessor' );
		$method    = $reflector->getMethod( 'stripMagicWords' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, array( &$text ) );

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );

		$parserData = new ParserData( $title, $parserOutput );

		$this->assertMagicWords( $expected, $parserData->getOutput() );
	}

	/**
	 * @dataProvider textDataProvider
	 */
	public function testTextParse( $namespace, array $settings, $text, array $expected ) {

		$parserOutput = new ParserOutput();
		$title        = Title::newFromText( __METHOD__, $namespace );
		$instance     = $this->acquireInstance( $title, $parserOutput, $settings );

		$instance->parse( $text );

		$this->assertContains( $expected['resultText'], $text );

		$parserData = new ParserData( $title, $parserOutput );

		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testRedirectParse() {

		$namespace = NS_MAIN;
		$text      = '#REDIRECT [[:Lala]]';

		$settings = array(
			'smwgNamespacesWithSemanticLinks' => array( $namespace => true ),
			'smwgLinksInValues' => false,
			'smwgInlineErrors'  => true,
		);

		$parserOutput = new ParserOutput();
		$title        = Title::newFromText( __METHOD__, $namespace );
		$instance     = $this->acquireInstance( $title, $parserOutput, $settings );

		$instance->parse( $text );

		$expected['propertyCount']  = 1;
		$expected['property']       = new DIProperty( '_REDI' );
		$expected['propertyValues'] = ':Lala';

		$parserData = new ParserData( $title, $parserOutput );

		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testProcessOnReflection() {

		$parserOutput = new ParserOutput();
		$title        = Title::newFromText( __METHOD__ );
		$instance     = $this->acquireInstance( $title, $parserOutput );

		$reflector = new ReflectionClass( '\SMW\ContentProcessor' );

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
		// see also ParserTextProcessorTemplateTransclusionTest
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

		return $provider;
	}

	/**
	 * @return array
	 */
	public function magicWordDataProvider() {
		return array(
			// #0 __NOFACTBOX__
			array(
				NS_MAIN,
				'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
				' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
				' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
				' __NOFACTBOX__ ',
				array( 'SMW_NOFACTBOX' )
			),

			// #1 __SHOWFACTBOX__
			array(
				NS_HELP,
				'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
				' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
				' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
				' __SHOWFACTBOX__',
				array( 'SMW_SHOWFACTBOX' )
			),
		);
	}

	protected function assertMagicWords( $expected, $parserOutput ) {

		// MW 1.21 dependency
		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			return $this->assertEquals( $expected, $parserOutput->getExtensionData( 'smwmagicwords' ) );
		}

		return $this->assertEquals( $expected, $parserOutput->mSMWMagicWords );
	}

	/**
	 * @return ContentProcessor
	 */
	private function acquireInstance( Title $title, ParserOutput $parserOutput, array $settings = array() ) {

		$context = new ExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', Settings::newFromArray( $settings ) );

		$parserData = new ParserData( $title, $parserOutput );

		return new ContentProcessor( $parserData, $context );
	}

}
