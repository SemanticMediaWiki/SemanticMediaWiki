<?php

namespace SMW\Test;

use SMW\ContentProcessor;
use SMW\ExtensionContext;

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
class ContentProcessorTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ContentProcessor';
	}

	/**
	 * @since  1.9
	 *
	 * @return ContentProcessor
	 */
	private function newInstance( Title $title, ParserOutput $parserOutput, array $settings = array() ) {

		$context = new ExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $this->newSettings( $settings ) );

		$parserData = $this->newParserData( $title, $parserOutput );

		return new ContentProcessor( $parserData, $context );
	}

	/**
	 * @dataProvider textDataProvider
	 *
	 * @since 1.9
	 */
	public function testConstructor( $namespace ) {
		$instance = $this->newInstance( $this->newTitle( $namespace ), $this->newParserOutput() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @dataProvider magicWordDataProvider
	 *
	 * @since 1.9
	 */
	public function testStripMagicWords( $namespace, $text, array $expected ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle( $namespace );
		$instance     = $this->newInstance( $title, $parserOutput );

		// Make protected method accessible
		$reflector = $this->newReflector();
		$method    = $reflector->getMethod( 'stripMagicWords' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance, array( &$text ) );

		// Check return values
		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected, $result );

		// Check values against ParserData/ParserOutput object
		$parserData = $this->newParserData( $title, $parserOutput );

		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			$this->assertEquals( $expected, $parserData->getOutput()->getExtensionData( 'smwmagicwords' ) );
		} else {
			$this->assertEquals( $expected, $parserData->getOutput()->mSMWMagicWords );
		}
	}

	/**
	 * @dataProvider textDataProvider
	 *
	 * @since 1.9
	 */
	public function testParse( $namespace, array $settings, $text, array $expected ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle( $namespace );
		$instance     = $this->newInstance( $title, $parserOutput, $settings );

		// Text parsing
		$instance->parse( $text );

		// Check transformed text
		$this->assertContains( $expected['resultText'], $text );

		// Re-read data from stored parserOutput
		$parserData = $this->newParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( '\SMW\SemanticData', $parserData->getData() );

		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getData()
		);

	}

	/**
	 * @since 1.9
	 */
	public function testRedirect() {

		$namespace = NS_MAIN;
		$text      = '#REDIRECT [[:Lala]]';

		// Create text processor instance
		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle( $namespace );

		$settings = $this->newSettings( array(
			'smwgNamespacesWithSemanticLinks' => array( $namespace => true ),
			'smwgLinksInValues' => false,
			'smwgInlineErrors'  => true,
		) );

		$parserData = $this->newParserData( $title, $parserOutput );

		$context = new ExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		$instance = new ContentProcessor( $parserData, $context );
		$instance->parse( $text );

		// Build expected results from a successful setRedirect execution
		$expected['propertyCount'] = 1;
		$expected['propertyKey']   = '_REDI';
		$expected['propertyValue'] = ':Lala';

		// Check the returned instance
		$this->assertInstanceOf( '\SMW\SemanticData', $parserData->getData() );

		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getData()
		);

	}

	/**
	 * @since 1.9
	 */
	public function testProcess() {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle();
		$instance     = $this->newInstance( $title, $parserOutput );

		// Make protected methods accessible
		$reflection = $this->newReflector();

		$method = $reflection->getMethod( 'process' );
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

	/**
	 * Provides text sample, following namespace, the settings to be used,
	 * text string, and expected result array with {result text, property count,
	 * property label, and property value}
	 *
	 * @return array
	 */
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

}
