<?php

namespace SMW\Test;

use SMW\InternalParseBeforeLinks;
use SMW\ExtensionContext;

use Title;

/**
 * @covers \SMW\InternalParseBeforeLinks
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class InternalParseBeforeLinksTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\InternalParseBeforeLinks';
	}

	/**
	 * @since 1.9
	 *
	 * @return InternalParseBeforeLinks
	 */
	public function newInstance( &$parser = null, &$text = '' ) {

		if ( $parser === null ) {
			$parser = $this->newParser( $this->newTitle(), $this->getUser() );
		}

		$instance = new InternalParseBeforeLinks( $parser, $text );
		$instance->invokeContext( new ExtensionContext() );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcess( $title ) {

		$parser = $this->newParser( $title, $this->getUser() );

		$this->assertTrue(
			$this->newInstance( $parser )->process(),
			'asserts that process() always returns true'
		);

	}

	/**
	 * @dataProvider textDataProvider
	 *
	 * @see ParserTextProcessorTest
	 *
	 * @since 1.9
	 */
	public function testSemanticDataParserOuputUpdateIntegration( $setup, $expected ) {

		$text     = $setup['text'];
		$parser   = $this->newParser( $setup['title'], $this->getUser() );
		$instance = $this->newInstance( $parser, $text );

		$instance->withContext()
			->getDependencyBuilder()
			->getContainer()
			->registerObject( 'Settings', $this->newSettings( $setup['settings'] ) );

		$this->assertTrue(
			$instance->process(),
			'asserts that process() always returns true'
		);

		$this->assertEquals(
			$expected['resultText'],
			$text,
			'asserts that the text was modified within expected parameters'
		);

		// Re-read data from the Parser
		$parserData = $this->newParserData( $parser->getTitle(), $parser->getOutput() );

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $parserData->getSemanticData() );

	}

	/**
	 * @return array
	 */
	public function titleDataProvider() {

		$provider = array();

		// #0 Normal title
		$provider[] = array( $this->newTitle() );

		// #1 Title is a special page
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true,
		) );

		// #2 Title is a special page
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true,
			'isSpecial'     => true,
		) );

		// #3 Title is a special page
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true,
			'isSpecial'     => false,
		) );

		$provider[] = array( $title );

		return $provider;
	}

	/**
	 * @return array
	 */
	public function textDataProvider() {

		$provider = array();

		// #0 NS_MAIN; [[FooBar...]] with a different caption
		$provider[] = array(
			array(
				'title'    => $this->newTitle( NS_MAIN ),
				'settings' => array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgLinksInValues' => false,
					'smwgInlineErrors'  => true,
				),
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				),
				array(
					'resultText' => 'Lorem ipsum dolor sit &$% [[:Dictumst|寒い]]' .
						' [[:Tincidunt semper|tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[:9001|9001]] et Donec.',
					'propertyCount'  => 3,
					'propertyLabels' => array( 'Foo', 'Bar', 'FooBar' ),
					'propertyValues' => array( 'Dictumst', 'Tincidunt semper', '9001' )
				)
		);

		// #1 NS_SPECIAL, processed but no annotations
		$provider[] = array(
			array(
				'title'    => $this->newTitle( NS_SPECIAL, 'Ask' ),
				'settings' => array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgLinksInValues' => false,
					'smwgInlineErrors'  => true,
					'smwgEnabledSpecialPage' => array( 'Ask', 'Foo' )
				),
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				),
				array(
					'resultText' => 'Lorem ipsum dolor sit &$% [[:Dictumst|寒い]]' .
						' [[:Tincidunt semper|tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[:9001|9001]] et Donec.',
					'propertyCount' => 0
				)
		);

		// #2 NS_SPECIAL, not processed
		$provider[] = array(
			array(
				'title'    => $this->newTitle( NS_SPECIAL, 'Foo' ),
				'settings' => array(
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
					'smwgLinksInValues' => false,
					'smwgInlineErrors'  => true,
					'smwgEnabledSpecialPage' => array( 'Ask', 'Foo' )
				),
				'text'  => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
					' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
					' [[foo::9001]] et Donec.',
				),
				array(
					'resultText' => 'Lorem ipsum dolor sit &$% [[FooBar::dictumst|寒い]]' .
						' [[Bar::tincidunt semper]] facilisi {{volutpat}} Ut quis' .
						' [[foo::9001]] et Donec.',
					'propertyCount' => 0
				)
		);

		return $provider;
	}

}
