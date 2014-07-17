<?php

namespace SMW\Test;

use SMW\ContentProcessor;
use SMW\ParserData;
use SMW\Settings;
use SMW\Factbox;
use SMW\Application;

use ParserOutput;
use Title;

/**
 * @covers \SMW\Factbox
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FactboxMagicWordsTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string
	 */
	public function getClass() {
		return '\SMW\Factbox';
	}

	/**
	 * @since 1.9
	 *
	 * @return Factbox
	 */
	private function newInstance( ParserData $parserData = null, Settings $settings = null, $context = null ) {

		$mockStore = $this->newMockBuilder()->newObject( 'Store' );
		$context->setTitle( $parserData->getTitle() );

		return new Factbox( $mockStore, $parserData, $settings, $context );
	}

	/**
	 * @dataProvider textDataProvider
	 *
	 * @since 1.9
	 */
	public function testMagicWordsFromParserOutputExtension( $text, array $expected ) {

		$title        = $this->newTitle();
		$parserOutput = new ParserOutput();
		$settings     = Settings::newFromArray( array(
			'smwgNamespacesWithSemanticLinks' => array( $title->getNamespace() => true ),
			'smwgLinksInValues' => false,
			'smwgInlineErrors'  => true,
			)
		);

		Application::getInstance()->registerObject( 'Settings', $settings );

		$parserData = new ParserData( $title, $parserOutput );

		$inTextAnnotationParser = Application::getInstance()->newInTextAnnotationParser( $parserData );
		$inTextAnnotationParser->parse( $text );

		$this->assertEquals(
			$expected['magicWords'],
			$this->getMagicwords( $parserOutput )
		);

		Application::clear();
	}

	/**
	 * @dataProvider textDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetMagicWords( $text, array $expected ) {

		$title    = $this->newTitle();
		$settings = $this->newSettings( array(
			'smwgShowFactboxEdit' => SMW_FACTBOX_HIDDEN,
			'smwgShowFactbox'     => SMW_FACTBOX_HIDDEN
			)
		);

		// Simulated preview context for when it is expected
		if ( isset( $expected['preview'] ) && $expected['preview'] ) {
			$context = $this->newContext( array( 'wpPreview' => true ) );
			$context->setTitle( $title );
		} else {
			$context = $this->newContext();
			$context->setTitle( $title );
		}

		$mockParserOutput = $this->newMockBuilder()->newObject( 'ParserOutput', array(
			'getExtensionData' => $expected['magicWords']
		) );

		// MW 1.19, 1.20
		$mockParserOutput->mSMWMagicWords = $expected['magicWords'];

		$instance = $this->newInstance(
			new ParserData( $title, $mockParserOutput ),
			$settings,
			$context
		);

		$reflector = $this->newReflector();
		$magic = $reflector->getMethod( 'getMagicWords' );
		$magic->setAccessible( true );

		$result = $magic->invoke( $instance );
		$this->assertInternalType( 'integer', $result );
		$this->assertEquals( $expected['constants'], $result );

	}

	/**
	 * @return array
	 */
	public function textDataProvider() {

		$provider = array();

		// #0 __NOFACTBOX__, this test should not generate a factbox output
		$provider[] = array(
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' __NOFACTBOX__ ',
			array(
				'magicWords' => array( 'SMW_NOFACTBOX' ),
				'constants'  => SMW_FACTBOX_HIDDEN,
				'textOutput' => ''
			)
		);

		// #1 __SHOWFACTBOX__, this test should generate a factbox output
		$provider[] = array(
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut' .
			' __SHOWFACTBOX__',
			array(
				'magicWords' => array( 'SMW_SHOWFACTBOX' ),
				'constants'  => SMW_FACTBOX_NONEMPTY,
				'textOutput' => 'smwfactboxhead' // lazy check because we use assertContains
			)
		);

		// #2 empty
		$provider[] = array(
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut',
			array(
				'magicWords' => array(),
				'constants'  => SMW_FACTBOX_HIDDEN,
				'textOutput' => ''
			)
		);

		// #3 empty + preview option
		$provider[] = array(
			'Lorem ipsum dolor sit amet consectetuer auctor at quis' .
			' [[Foo::dictumst cursus]]. Nisl sit condimentum Quisque facilisis' .
			' Suspendisse [[Bar::tincidunt semper]] facilisi dolor Aenean. Ut',
			array(
				'magicWords' => array(),
				'preview'    => true,
				'constants'  => SMW_FACTBOX_HIDDEN,
				'textOutput' => ''
			)
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	protected function getMagicwords( $parserOutput ) {

		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			return $parserOutput->getExtensionData( 'smwmagicwords' );
		}

		return $parserOutput->mSMWMagicWords;
	}

}
