<?php

namespace SMW\Tests;

use SMW\Tests\Util\UtilityFactory;
use SMW\Tests\Util\Mock\MockObjectBuilder;
use SMW\Tests\Util\Mock\CoreMockObjectRepository;
use SMW\Tests\Util\Mock\MediaWikiMockObjectRepository;

use SMW\Application;
use SMW\TableFormatter;
use SMW\ParserData;
use SMW\Factbox;
use SMW\DIProperty;
use SMW\DIWikiPage;

use ReflectionClass;
use ParserOutput;
use Title;

/**
 * @covers \SMW\Factbox
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FactboxTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;
	private $application;
	private $mockbuilder;

	protected function setUp() {
		parent::setUp();

		$this->application = Application::getInstance();
		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();

		// This needs to be fixed but not now
		$this->mockbuilder = new MockObjectBuilder();
		$this->mockbuilder->registerRepository( new CoreMockObjectRepository() );
		$this->mockbuilder->registerRepository( new MediaWikiMockObjectRepository() );
	}

	protected function tearDown() {
		$this->application->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Factbox',
			new Factbox( $store, $parserData, $messageBuilder )
		);
	}

	public function testGetContent() {

		$text = __METHOD__;

		$parserData = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		// Build Factbox stub object to encapsulate the method
		// without the need for other dependencies to occur
		$instance = $this->getMock( '\SMW\Factbox',
			array( 'fetchContent', 'getMagicWords' ),
			array(
				$store,
				$parserData,
				$messageBuilder
			)
		);

		$instance->expects( $this->any() )
			->method( 'getMagicWords' )
			->will( $this->returnValue( 'Lula' ) );

		$instance->expects( $this->any() )
			->method( 'fetchContent' )
			->will( $this->returnValue( $text ) );

		$this->assertFalse( $instance->isVisible() );

		$instance->doBuild();

		$this->assertInternalType(
			'string',
			$instance->getContent()
		);

		$this->assertEquals(
			$text,
			$instance->getContent()
		);

		$this->assertTrue( $instance->isVisible() );
	}

	public function testGetContentRoundTripForNonEmptyContent() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$this->application->getSettings()->set('smwgShowFactbox', SMW_FACTBOX_NONEMPTY );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$mockSemanticData = $this->mockbuilder->newObject( 'SemanticData', array(
			'getSubject'           => $subject,
			'hasVisibleProperties' => true,
			'getPropertyValues'    => array( $subject ),
			'getProperties'        => array( DIProperty::newFromUserLabel( 'SomeFancyProperty' ) )
		) );

		$parserOutput = $this->setupParserOutput( $mockSemanticData );

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'inContentLanguage' )
			->will( $this->returnSelf() );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->will( $this->returnValue( $message ) );

		$instance = new Factbox( $store, new ParserData( $subject->getTitle() , $parserOutput ), $messageBuilder );
		$result   = $instance->doBuild()->getContent();

		$this->assertInternalType(
			'string',
			$result
		);

		$this->assertContains(
			$subject->getDBkey(),
			 $result
		);

		$this->assertEquals(
			$subject->getTitle(),
			$instance->getTitle()
		);
	}

	public function testCreateTable() {

		$parserData = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'inContentLanguage' )
			->will( $this->returnSelf() );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->will( $this->returnValue( $message ) );

		$instance = new Factbox( $store, $parserData, $messageBuilder );

		$reflector = new ReflectionClass( '\SMW\Factbox' );
		$createTable  = $reflector->getMethod( 'createTable' );
		$createTable->setAccessible( true );

		$this->assertInternalType(
			'string',
			$createTable->invoke( $instance, $parserData->getSemanticData() )
		);
	}

	/**
	 * @dataProvider fetchContentDataProvider
	 */
	public function testFetchContent( $parserData ) {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Factbox( $store, $parserData, $messageBuilder );

		$reflector = new ReflectionClass( '\SMW\Factbox' );

		$fetchContent = $reflector->getMethod( 'fetchContent' );
		$fetchContent->setAccessible( true );

		$this->assertInternalType(
			'string',
			$fetchContent->invoke( $instance, SMW_FACTBOX_NONEMPTY )
		);

		$this->assertEmpty(
			$fetchContent->invoke( $instance, SMW_FACTBOX_HIDDEN )
		);
	}

	/**
	 * @dataProvider contentDataProvider
	 */
	public function testGetContentDataSimulation( $setup, $expected ) {

		$mockSemanticData = $this->mockbuilder->newObject( 'SemanticData', array(
			'hasVisibleSpecialProperties' => $setup['hasVisibleSpecialProperties'],
			'hasVisibleProperties'        => $setup['hasVisibleProperties'],
			'isEmpty'                     => $setup['isEmpty']
		) );

		$mockStore = $this->mockbuilder->newObject( 'Store', array(
			'getSemanticData' => $mockSemanticData,
		) );

		$mockParserData = $this->mockbuilder->newObject( 'ParserData', array(
			'getSubject'  => $this->mockbuilder->newObject( 'DIWikiPage' ),
			'getData'     => null
		) );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		// Build Factbox stub object to encapsulate the method
		// without the need for other dependencies to occur
		$factbox = $this->getMock( '\SMW\Factbox',
			array( 'createTable' ),
			array(
				$mockStore,
				$mockParserData,
				$messageBuilder
			)
		);

		$factbox->expects( $this->any() )
			->method( 'createTable' )
			->will( $this->returnValue( $setup['invokedContent'] ) );

		$reflector = new ReflectionClass( '\SMW\Factbox' );
		$fetchContent = $reflector->getMethod( 'fetchContent' );
		$fetchContent->setAccessible( true );

		$this->assertInternalType(
			'string',
			$fetchContent->invoke( $factbox )
		);

		$this->assertEquals(
			$expected,
			$fetchContent->invoke( $factbox, $setup['showFactbox'] )
		);
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

	public function testGetTableHeader() {

		$title = Title::newFromText( __METHOD__ );

		$parserData = new ParserData(
			$title,
			new ParserOutput()
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$message = $this->getMockBuilder( '\Message' )
			->disableOriginalConstructor()
			->getMock();

		$message->expects( $this->any() )
			->method( 'inContentLanguage' )
			->will( $this->returnSelf() );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$messageBuilder->expects( $this->any() )
			->method( 'getMessage' )
			->will( $this->returnValue( $message ) );

		$instance = new Factbox( $store, $parserData, $messageBuilder );

		$reflector = new ReflectionClass( '\SMW\Factbox' );

		$tableFormatter = $reflector->getProperty( 'tableFormatter' );
		$tableFormatter->setAccessible( true );
		$tableFormatter->setValue( $instance, new TableFormatter() );

		$getTableHeader = $reflector->getMethod( 'getTableHeader' );
		$getTableHeader->setAccessible( true );
		$getTableHeader->invoke( $instance, DIWikiPage::newFromTitle( $title ) );

		// "smwfactboxhead"/"smwrdflink" is used for doing a lazy check on
		// behalf of the invoked content
		$header = $tableFormatter->getValue( $instance )->getHeaderItems();

		$this->stringValidator->assertThatStringContains(
			array(
				'span class="smwrdflink"'
			),
			$header
		);
	}

	/**
	 * @dataProvider tableContentDataProvider
	 */
	public function testGetTableContent( $test, $expected ) {

		$title = Title::newFromText( __METHOD__ );

		$parserData = new ParserData(
			$title,
			new ParserOutput()
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new Factbox( $store, $parserData, $messageBuilder );

		$reflector = new ReflectionClass( '\SMW\Factbox' );

		$mockDIProperty = $this->mockbuilder->newObject( 'DIProperty', array(
			'isUserDefined' => $test['isUserDefined'],
			'isShown'       => $test['isShown'],
			'getLabel'      => 'Quuey'
		) );

		$mockSemanticData = $this->mockbuilder->newObject( 'SemanticData', array(
			'getPropertyValues' => array( DIWikiPage::newFromTitle( $title ) ),
			'getProperties'     => array( $mockDIProperty )
		) );

		$tableFormatter = $reflector->getProperty( 'tableFormatter' );
		$tableFormatter->setAccessible( true );
		$tableFormatter->setValue( $instance, new TableFormatter() );

		$getTableContent = $reflector->getMethod( 'getTableContent' );
		$getTableContent->setAccessible( true );
		$getTableContent->invoke( $instance, $mockSemanticData );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$tableFormatter->getValue( $instance )->getTable()
		);
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
			array( 'class="smwprops"' )
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
			array( 'class="smwspecs"' )
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

		$title = Title::newFromText( __METHOD__ );

		$provider = array();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$semanticData->expects( $this->any() )
			->method( 'isEmpty' )
			->will( $this->returnValue( false ) );

		$parserData = new ParserData(
			$title,
			new ParserOutput()
		);

		$parserData->setSemanticData( $semanticData );

		$provider[] = array( $parserData );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array( new DIProperty( '_SKEY') ) ) );

		$semanticData->expects( $this->any() )
			->method( 'isEmpty' )
			->will( $this->returnValue( false ) );

		$parserData = new ParserData(
			$title,
			new ParserOutput()
		);

		$parserData->setSemanticData( $semanticData );

		$provider[] = array( $parserData );

		return $provider;
	}

	protected function setupParserOutput( $semanticData ) {

		$parserOutput = new ParserOutput();

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', $semanticData );
		} else {
			$parserOutput->mSMWData = $semanticData;
		}

		return $parserOutput;
	}

}
