<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\TableFormatter;
use SMW\ParserData;
use SMW\Factbox;
use SMW\Settings;
use SMW\DIProperty;

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
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FactboxTest extends ParserTestCase {

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
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
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

		$parserOutput = $this->setupParserOutput( $mockSemanticData );

		$instance = $this->newInstance( $this->newParserData( $mockTitle , $parserOutput ), $settings );
		$result   = $instance->doBuild()->getContent();

		$this->assertInternalType( 'string', $result );
		$this->assertContains( $mockTitle->getDBkey(), $result );
		$this->assertEquals( $mockTitle, $instance->getTitle() );

	}

	/**
	 * @since 1.9
	 */
	public function testCreateTable() {

		$parserData = $this->newParserData( $this->newTitle(), $this->newParserOutput() );
		$instance   = $this->newInstance( $parserData );

		$reflector = $this->newReflector();
		$createTable  = $reflector->getMethod( 'createTable' );
		$createTable->setAccessible( true );

		$result = $createTable->invoke( $instance, $parserData->getData() );
		$this->assertInternalType( 'string', $result );

	}

	/**
	 * @dataProvider fetchContentDataProvider
	 *
	 * @since 1.9
	 */
	public function testFetchContent( $mockParserData ) {

		$instance   = $this->newInstance( $mockParserData );
		$reflector  = $this->newReflector();

		$fetchContent = $reflector->getMethod( 'fetchContent' );
		$fetchContent->setAccessible( true );

		$this->assertInternalType( 'string', $fetchContent->invoke( $instance, SMW_FACTBOX_NONEMPTY ) );
		$this->assertEmpty( $fetchContent->invoke( $instance, SMW_FACTBOX_HIDDEN ) );

	}

	/**
	 * @since 1.9
	 */
	public function testCreateTableOnHistoricalData() {

		$parserData = $this->newParserData( $this->newTitle(), $this->newParserOutput() );
		$instance   = $this->newInstance( $parserData, null, $this->newContext( array( 'oldid' => 9001 ) ) );

		$reflector  = $this->newReflector();

		$createTable = $reflector->getMethod( 'createTable' );
		$createTable->setAccessible( true );

		$result = $createTable->invoke( $instance, $parserData->getData() );
		$this->assertInternalType( 'string', $result );

	}

	/**
	 * @dataProvider contentDataProvider
	 *
	 * Use a mock/stub in order for getTable to return canned content to test
	 * fetchContent.
	 *
	 * @since 1.9
	 */
	public function testGetContentDataSimulation( $setup, $expected ) {

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'hasVisibleSpecialProperties' => $setup['hasVisibleSpecialProperties'],
			'hasVisibleProperties'        => $setup['hasVisibleProperties'],
			'isEmpty'                     => $setup['isEmpty']
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
				'isEmpty'                     => false,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			),
			$text // expected return
		);

		$provider[] = array(
			array(
				'hasVisibleSpecialProperties' => true,
				'hasVisibleProperties'        => true,
				'isEmpty'                     => true,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			),
			$text // expected return
		);

		$provider[] = array(
			array(
				'hasVisibleSpecialProperties' => false,
				'hasVisibleProperties'        => true,
				'isEmpty'                     => false,
				'showFactbox'                 => SMW_FACTBOX_SPECIAL,
				'invokedContent'              => $text,
			),
			'' // expected return
		);

		$provider[] = array(
			array(
				'hasVisibleSpecialProperties' => false,
				'hasVisibleProperties'        => false,
				'isEmpty'                     => false,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			),
			'' // expected return
		);

		$provider[] = array(
			array(
				'hasVisibleSpecialProperties' => true,
				'hasVisibleProperties'        => false,
				'isEmpty'                     => false,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			),
			'' // expected return
		);

		return $provider;
	}

	/**
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
	 * @return array
	 */
	public function fetchContentDataProvider() {

		$provider = array();

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'isEmpty' => false,
			'getPropertyValues' => array()
		) );

		$mockParserData = $this->newMockBuilder()->newObject( 'ParserData', array(
			'getTitle'   => $this->newTitle(),
			'getSubject' => $this->newMockBuilder()->newObject( 'DIWikiPage' ),
			'getData'    => $mockSemanticData,
		) );

		$provider[] = array( $mockParserData );

		$mockSemanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'isEmpty' => false,
			'getPropertyValues' => array( new DIProperty( '_SKEY') ),
		) );

		$mockParserData = $this->newMockBuilder()->newObject( 'ParserData', array(
			'getTitle'   => $this->newTitle(),
			'getSubject' => $this->newMockBuilder()->newObject( 'DIWikiPage' ),
			'getData'    => $mockSemanticData,
		) );

		$provider[] = array( $mockParserData );

		return $provider;
	}

	/**
	 * @return ParserOutput
	 */
	protected function setupParserOutput( $semanticData ) {

		$parserOutput = $this->newParserOutput();

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', $semanticData );
		} else {
			$parserOutput->mSMWData = $semanticData;
		}

		return $parserOutput;

	}
}
