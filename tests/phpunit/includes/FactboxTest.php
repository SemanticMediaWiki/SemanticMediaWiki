<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\TableFormatter;
use SMW\ParserData;
use SMW\Factbox;
use SMW\Settings;

use ParserOutput;
use Title;

/**
 * Tests for the Factbox class
 *
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMW\Factbox
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class FactboxTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\Factbox';
	}

	/**
	 * Helper method that returns a Factbox object
	 *
	 * @param $parserData
	 * @param $settings
	 * @param $context
	 *
	 * @return Factbox
	 */
	private function newInstance( ParserData $parserData = null, Settings $settings = null, $context = null ) {

		if ( $parserData === null ) {
			$parserData = $this->newParserData( $this->newTitle(), $this->newParserOutput() );
		}

		if ( $settings === null ) {
			$settings = $this->newSettings();
		}

		if ( $context === null ) {
			$context = $this->newContext();
		}

		$mockStore = $this->newMockBuilder()->newObject( 'Store' );
		$context->setTitle( $parserData->getTitle() );

		return new Factbox( $mockStore, $parserData, $settings, $context );
	}

	/**
	 * @test Factbox::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test Factbox::getMagicWords
	 * @dataProvider textDataProvider
	 *
	 * One can argue if this test really belongs in here since it mainly tests
	 * the returned values by ParserTextProcessor class but since their share a
	 * close dependency in how "magicWords" are transferred using the
	 * ParserOutput object, we do test this as well.
	 *
	 * @since 1.9
	 *
	 * @param $text
	 * @param array $expected
	 */
	public function testMagicWordsFromParserOutputExtension( $text, array $expected ) {

		$title        = $this->newTitle();
		$parserOutput = $this->newParserOutput();
		$settings     = $this->newSettings( array(
			'smwgNamespacesWithSemanticLinks' => array( $title->getNamespace() => true ),
			'smwgLinksInValues' => false,
			'smwgInlineErrors' => true,
			)
		);

		$textProcessor = $this->getParserTextProcessor( $title, $parserOutput, $settings );

		// Use the text processor to add text sample
		$textProcessor->parse( $text );

		// Check the magic words stripped and added by the text processor
		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			$this->assertEquals(
				$expected['magicWords'],
				$parserOutput->getExtensionData( 'smwmagicwords' )
			);
		} else {
			$this->assertEquals(
				$expected['magicWords'],
				$parserOutput->mSMWMagicWords
			);
		}
	}

	/**
	 * @test Factbox::getMagicWords
	 * @dataProvider textDataProvider
	 *
	 * Simulate and verify all combinations that can occur during processing
	 * of getMagicWords
	 *
	 * @since 1.9
	 *
	 * @param string $text
	 * @param array $expected
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
			$this->newParserData( $title, $mockParserOutput ),
			$settings,
			$context
		);

		// Access protected method
		$reflector = $this->newReflector();
		$magic = $reflector->getMethod( 'getMagicWords' );
		$magic->setAccessible( true );

		$result = $magic->invoke( $instance );
		$this->assertInternalType( 'integer', $result );
		$this->assertEquals( $expected['constants'], $result );

	}

	/**
	 * @test Factbox::getContent
	 * @test Factbox::isVisible
	 *
	 * Use a mock/stub object to verify the return value to getContent and
	 * isolate the method from other dependencies during test
	 *
	 * @since 1.9
	 */
	public function testGetContent() {

		$text    = __METHOD__;
		$title   = $this->newTitle();

		$context = $this->newContext();
		$context->setTitle( $title );

		// Build Factbox stub object to encapsulate the method
		// without the need for other dependencies to occur
		$factbox = $this->getMock( $this->getClass(),
			array( 'fetchContent', 'getMagicWords' ),
			array(
				$this->newMockBuilder()->newObject( 'Store' ),
				$this->newParserData( $title, $this->newParserOutput() ),
				$this->newSettings(),
				$context
			)
		);

		$factbox->expects( $this->any() )
			->method( 'getMagicWords' )
			->will( $this->returnValue( 'Lula' ) );

		$factbox->expects( $this->any() )
			->method( 'fetchContent' )
			->will( $this->returnValue( $text ) );

		// Check before execution
		$this->assertFalse( $factbox->isVisible() );
		$factbox->doBuild();

		$this->assertInternalType( 'string', $factbox->getContent() );
		$this->assertEquals( $text, $factbox->getContent() );

		// Check after execution
		$this->assertTrue( $factbox->isVisible() );

	}

	/**
	 * @test Factbox::getContent
	 *
	 * @since 1.9
	 */
	public function testGetContentRoundTrip() {

		$settings = $this->newSettings( array(
			'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY
		) );

		$mockTitle = $this->newMockBuilder()->newObject( 'Title', array(
			'getPageLanguage' => $this->getLanguage(),
		) );

		$mockSubject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $mockTitle,
			'getDBkey' => $mockTitle->getDBkey()
		) );

		$mockDIProperty =  $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => true,
			'isShown'       => true,
			'getLabel'      => $this->newRandomString( 10, 'property' )
		) );

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'getSubject'           => $mockSubject,
			'hasVisibleProperties' => true,
			'getPropertyValues'    => array( $mockSubject ),
			'getProperties'        => array( $mockDIProperty )
		) );

		$parserOutput = $this->newParserOutput();

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', $mockSemanticData );
		} else {
			$parserOutput->mSMWData = $mockSemanticData;
		}

		$instance = $this->newInstance( $this->newParserData( $mockTitle , $parserOutput ), $settings );
		$result   = $instance->doBuild()->getContent();

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $mockTitle->getDBkey(), $result );
		$this->assertEquals( $mockTitle, $instance->getTitle() );

	}

	/**
	 * @test Factbox::createTable
	 *
	 * @since 1.9
	 */
	public function testCreateTable() {

		$parserData = $this->newParserData( $this->getTitle(), $this->newParserOutput() );
		$instance   = $this->newInstance( $parserData );

		$reflector = $this->newReflector();
		$createTable  = $reflector->getMethod( 'createTable' );
		$createTable->setAccessible( true );

		$result = $createTable->invoke( $instance, $parserData->getData() );
		$this->assertInternalType( 'string', $result );

	}

	/**
	 * @test Factbox::fetchContent
	 *
	 * @since 1.9
	 */
	public function testFetchContent() {

		$parserData = $this->newParserData( $this->getTitle(), $this->newParserOutput() );
		$instance   = $this->newInstance( $parserData );

		$reflector    = $this->newReflector();
		$fetchContent = $reflector->getMethod( 'fetchContent' );
		$fetchContent->setAccessible( true );

		$this->assertInternalType( 'string', $fetchContent->invoke( $instance, SMW_FACTBOX_NONEMPTY ) );
		$this->assertEmpty( $fetchContent->invoke( $instance, SMW_FACTBOX_HIDDEN ) );

	}

	/**
	 * @test Factbox::fetchContent
	 * @dataProvider contentDataProvider
	 *
	 * Use a mock/stub in order for getTable to return canned content to test
	 * fetchContent.
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param $expected
	 */
	public function testGetContentDataSimulation( array $setup, $expected ) {

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'hasVisibleSpecialProperties' => $setup['hasVisibleSpecialProperties'],
			'hasVisibleProperties'        => $setup['hasVisibleProperties']
		) );

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getSemanticData' => $mockSemanticData,
		) );

		$mockParserData = $this->newMockBuilder()->newObject( 'ParserData', array(
			'getSubject'  => $this->newMockBuilder()->newObject( 'DIWikiPage' ),
			'getData'     => null
		) );

		// Build Factbox stub object to encapsulate the method
		// without the need for other dependencies to occur
		$factbox = $this->getMock( $this->getClass(),
			array( 'createTable' ),
			array(
				$mockStore,
				$mockParserData,
				$this->newSettings(),
				$this->newContext()
			)
		);

		$factbox->expects( $this->any() )
			->method( 'createTable' )
			->will( $this->returnValue( $setup['invokedContent'] ) );

		$reflector = $this->newReflector();
		$fetchContent = $reflector->getMethod( 'fetchContent' );
		$fetchContent->setAccessible( true );

		$this->assertInternalType( 'string', $fetchContent->invoke( $factbox ) );
		$this->assertEquals( $expected, $fetchContent->invoke( $factbox, $setup['showFactbox'] ) );

	}

	/**
	 * Conditional content switcher to test combinations of
	 * SMW_FACTBOX_NONEMPTY and SMWSemanticData etc.
	 *
	 * @return array
	 */
	public function contentDataProvider() {

		$text = __METHOD__;
		$provider = array();

		$provider[] = array(
			array(
				'hasVisibleSpecialProperties' => true,
				'hasVisibleProperties'        => true,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			),
			$text // expected return
		);

		$provider[] = array(
			array(
				'hasVisibleSpecialProperties' => false,
				'hasVisibleProperties'        => true,
				'showFactbox'                 => SMW_FACTBOX_SPECIAL,
				'invokedContent'              => $text,
			),
			'' // expected return
		);

		$provider[] = array(
			array(
				'hasVisibleSpecialProperties' => false,
				'hasVisibleProperties'        => false,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			),
			'' // expected return
		);

		$provider[] = array(
			array(
				'hasVisibleSpecialProperties' => true,
				'hasVisibleProperties'        => false,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			),
			'' // expected return
		);

		return $provider;
	}

	/**
	 * @test Factbox::getTableHeader
	 *
	 * Get access to the tableFormatter object in order to verify that the
	 * getTableHeader does return some expected content
	 *
	 * @since 1.9
	 */
	public function testGetTableHeader() {

		$title      = $this->newTitle();
		$reflector  = $this->newReflector();
		$parserData = $this->newParserData( $title, $this->newParserOutput() );
		$instance   = $this->newInstance( $parserData );

		$mockSubject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $title
		) );

		$tableFormatter = $reflector->getProperty( 'tableFormatter' );
		$tableFormatter->setAccessible( true );
		$tableFormatter->setValue( $instance, new TableFormatter() );

		$getTableHeader = $reflector->getMethod( 'getTableHeader' );
		$getTableHeader->setAccessible( true );
		$getTableHeader->invoke( $instance, $mockSubject );

		// "smwfactboxhead"/"smwrdflink" is used for doing a lazy check on
		// behalf of the invoked content
		$header = $tableFormatter->getValue( $instance )->getHeaderItems();
		$this->assertTag( array( 'class' => 'smwfactboxhead' ), $header );
		$this->assertTag( array( 'class' => 'smwrdflink' ), $header );

	}

	/**
	 * @test Factbox::getTableContent
	 * @dataProvider tableContentDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetTableContent( $test, $expected ) {

		$title      = $this->newTitle();
		$reflector  = $this->newReflector();
		$parserData = $this->newParserData( $title, $this->newParserOutput() );
		$instance   = $this->newInstance( $parserData );

		$mockSubject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $title
		) );

		$mockDIProperty = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'isUserDefined' => $test['isUserDefined'],
			'isShown'       => $test['isShown'],
			'getLabel'      => 'Quuey'
		) );

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'getPropertyValues' => array( $mockSubject ),
			'getProperties'     => array( $mockDIProperty )
		) );

		$tableFormatter = $reflector->getProperty( 'tableFormatter' );
		$tableFormatter->setAccessible( true );
		$tableFormatter->setValue( $instance, new TableFormatter() );

		$getTableContent = $reflector->getMethod( 'getTableContent' );
		$getTableContent->setAccessible( true );
		$getTableContent->invoke( $instance, $mockSemanticData );

		if ( $expected !== '' ) {
			$this->assertTag( $expected, $tableFormatter->getValue( $instance )->getTable() );
		} else {
			$this->assertEmpty( $tableFormatter->getValue( $instance )->getTable() );
		}
	}

	/**
	 * @return array
	 */
	public function tableContentDataProvider() {

		$provider = array();

		$provider[] = array(
			array(
				'isShown'       => true,
				'isUserDefined' => true,
			),
			array( 'class' => 'smwprops' )
		);

		$provider[] = array(
			array(
				'isShown'       => false,
				'isUserDefined' => true,
			),
			''
		);

		$provider[] = array(
			array(
				'isShown'       => true,
				'isUserDefined' => false,
			),
			array( 'class' => 'smwspecs' )
		);

		$provider[] = array(
			array(
				'isShown'       => false,
				'isUserDefined' => false,
			),
			''
		);

		return $provider;
	}

	/**
	 * Provides text sample together with the expected magic word and an
	 * indication of a possible output string
	 *
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
}
