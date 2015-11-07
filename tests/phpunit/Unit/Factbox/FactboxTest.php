<?php

namespace SMW\Tests\Factbox;

use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\Utils\Mock\MockObjectBuilder;
use SMW\Tests\Utils\Mock\CoreMockObjectRepository;
use SMW\Tests\Utils\Mock\MediaWikiMockObjectRepository;

use SMW\ApplicationFactory;
use SMW\TableFormatter;
use SMW\ParserData;
use SMW\Factbox\Factbox;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;

use ReflectionClass;
use ParserOutput;
use Title;

/**
 * @covers \SMW\Factbox\Factbox
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FactboxTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;
	private $applicationFactory;
	private $mockbuilder;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();

		// This needs to be fixed but not now
		$this->mockbuilder = new MockObjectBuilder();
		$this->mockbuilder->registerRepository( new CoreMockObjectRepository() );
		$this->mockbuilder->registerRepository( new MediaWikiMockObjectRepository() );

		$this->applicationFactory->getSettings()->set( 'smwgShowFactbox', SMW_FACTBOX_NONEMPTY );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

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
			'\SMW\Factbox\Factbox',
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
		$instance = $this->getMock( '\SMW\Factbox\Factbox',
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

		$this->applicationFactory->getSettings()->set('smwgShowFactbox', SMW_FACTBOX_NONEMPTY );

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

		$instance = new Factbox( $store, new ParserData( $subject->getTitle(), $parserOutput ), $messageBuilder );
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

		$reflector = new ReflectionClass( '\SMW\Factbox\Factbox' );
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

		$reflector = new ReflectionClass( '\SMW\Factbox\Factbox' );

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
			'getSemanticData'     => null
		) );

		$messageBuilder = $this->getMockBuilder( '\SMW\MediaWiki\MessageBuilder' )
			->disableOriginalConstructor()
			->getMock();

		// Build Factbox stub object to encapsulate the method
		// without the need for other dependencies to occur
		$factbox = $this->getMock( '\SMW\Factbox\Factbox',
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

		$reflector = new ReflectionClass( '\SMW\Factbox\Factbox' );
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

		$parserData->setSemanticData( new SemanticData( DIWikiPage::newFromTitle( $title ) ) );
		$parserData->getSemanticData()->addPropertyObjectValue(
			new DIProperty( 'Foo' ),
			DIWikiPage::newFromTitle( $title )
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

		$this->stringValidator->assertThatStringContains(
			array(
				'div class="smwrdflink"'
			),
			$instance->doBuild()->getContent()
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

		$mockDIProperty = $this->mockbuilder->newObject( 'DIProperty', array(
			'isUserDefined' => $test['isUserDefined'],
			'isShown'       => $test['isShown'],
			'getLabel'      => 'Quuey'
		) );

		$parserData->setSemanticData( new SemanticData( DIWikiPage::newFromTitle( $title ) ) );
		$parserData->getSemanticData()->addPropertyObjectValue(
			$mockDIProperty,
			DIWikiPage::newFromTitle( $title )
		);

		$instance = new Factbox( $store, $parserData, $messageBuilder );

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->doBuild()->getContent()
		);
	}

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
