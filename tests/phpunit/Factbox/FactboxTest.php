<?php

namespace SMW\Tests\Factbox;

use ParserOutput;
use ReflectionClass;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Factbox\Factbox;
use SMW\Factbox\CheckMagicWords;
use SMW\ParserData;
use SMW\SemanticData;
use SMW\TableFormatter;
use SMW\Tests\TestEnvironment;
use Title;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Factbox\Factbox
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class FactboxTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $stringValidator;
	private $testEnvironment;
	private $displayTitleFinder;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();

		$this->testEnvironment->addConfiguration( 'smwgShowFactbox', SMW_FACTBOX_NONEMPTY );

		$this->displayTitleFinder = $this->getMockBuilder( '\SMW\DisplayTitleFinder' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testBuildHTML() {
		$checkMagicWords = new CheckMagicWords(
			[
				'smwgShowFactboxEdit' => SMW_FACTBOX_NONEMPTY,
				'showFactbox' => SMW_FACTBOX_NONEMPTY
			]
		);

		$parserData = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new Factbox(
			$store,
			$parserData,
			$this->displayTitleFinder
		);

		$instance->setCheckMagicWords(
			$checkMagicWords
		);

		$reflector = new ReflectionClass( '\SMW\Factbox\Factbox' );
		$buildHTML  = $reflector->getMethod( 'buildHTML' );
		$buildHTML->setAccessible( true );

		$this->assertIsString(
			$buildHTML->invoke( $instance, $parserData->getSemanticData() )
		);
	}

	public function testTabs() {
		$this->assertContains(
			'tab-facts-list',
			Factbox::tabs( 'Foo' )
		);

		$this->assertContains(
			'tab-facts-attachment',
			Factbox::tabs( 'Foo', 'Bar' )
		);

		$this->assertContains(
			'tab-facts-derived',
			Factbox::tabs( 'Foo', 'Bar', 'Foobar' )
		);
	}

	/**
	 * @dataProvider fetchContentDataProvider
	 */
	public function testFetchContent( $parserData ) {
		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new Factbox(
			$store,
			$parserData,
			$this->displayTitleFinder
		);

		$reflector = new ReflectionClass( '\SMW\Factbox\Factbox' );

		$fetchContent = $reflector->getMethod( 'fetchContent' );
		$fetchContent->setAccessible( true );

		$this->assertIsString(
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
		$checkMagicWords = new CheckMagicWords(
			[
				'smwgShowFactboxEdit' => SMW_FACTBOX_NONEMPTY,
				'showFactbox' => $setup['showFactbox']
			]
		);

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleSpecialProperties' )
			->willReturn( $setup['hasVisibleSpecialProperties'] );

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleProperties' )
			->willReturn( $setup['hasVisibleProperties'] );

		$semanticData->expects( $this->any() )
			->method( 'isEmpty' )
			->willReturn( $setup['isEmpty'] );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

		$parserData->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( null );

		// Build Factbox stub object to encapsulate the method
		// without the need for other dependencies to occur
		$factbox = $this->getMockBuilder( '\SMW\Factbox\Factbox' )
			->setConstructorArgs( [
				$store,
				$parserData,
				$this->displayTitleFinder
			] )
			->onlyMethods( [ 'buildHTML' ] )
			->getMock();

		$factbox->setCheckMagicWords(
			$checkMagicWords
		);

		$factbox->expects( $this->any() )
			->method( 'buildHTML' )
			->willReturn( $setup['invokedContent'] );

		$reflector = new ReflectionClass( '\SMW\Factbox\Factbox' );
		$fetchContent = $reflector->getMethod( 'fetchContent' );
		$fetchContent->setAccessible( true );

		$this->assertIsString(
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
		$provider = [];

		$provider[] = [
			[
				'hasVisibleSpecialProperties' => true,
				'hasVisibleProperties'        => true,
				'isEmpty'                     => false,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			],
			$text // expected return
		];

		$provider[] = [
			[
				'hasVisibleSpecialProperties' => true,
				'hasVisibleProperties'        => true,
				'isEmpty'                     => true,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			],
			$text // expected return
		];

		$provider[] = [
			[
				'hasVisibleSpecialProperties' => false,
				'hasVisibleProperties'        => true,
				'isEmpty'                     => false,
				'showFactbox'                 => SMW_FACTBOX_SPECIAL,
				'invokedContent'              => $text,
			],
			'' // expected return
		];

		$provider[] = [
			[
				'hasVisibleSpecialProperties' => false,
				'hasVisibleProperties'        => false,
				'isEmpty'                     => false,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			],
			'' // expected return
		];

		$provider[] = [
			[
				'hasVisibleSpecialProperties' => true,
				'hasVisibleProperties'        => false,
				'isEmpty'                     => false,
				'showFactbox'                 => SMW_FACTBOX_NONEMPTY,
				'invokedContent'              => $text,
			],
			'' // expected return
		];

		return $provider;
	}

	public function testGetTableHeader() {
		$checkMagicWords = new CheckMagicWords(
			[
				'smwgShowFactboxEdit' => SMW_FACTBOX_NONEMPTY,
				'showFactbox' => SMW_FACTBOX_NONEMPTY
			]
		);

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

		$instance = new Factbox(
			$store,
			$parserData,
			$this->displayTitleFinder
		);

		$instance->setCheckMagicWords(
			$checkMagicWords
		);

		$instance->doBuild();

		$this->stringValidator->assertThatStringContains(
			[
				'span class="rdflink"'
			],
			$instance->getContent()
		);
	}

	/**
	 * @dataProvider tableContentDataProvider
	 */
	public function testGetTableContent( $test, $expected ) {
		$checkMagicWords = new CheckMagicWords(
			[
				'smwgShowFactboxEdit' => SMW_FACTBOX_NONEMPTY,
				'showFactbox' => SMW_FACTBOX_NONEMPTY
			]
		);

		$title = Title::newFromText( __METHOD__ );

		$parserData = new ParserData(
			$title,
			new ParserOutput()
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'isUserDefined' )
			->willReturn( $test['isUserDefined'] );

		$property->expects( $this->any() )
			->method( 'findPropertyTypeID' )
			->willReturn( '_wpg' );

		$property->expects( $this->any() )
			->method( 'isShown' )
			->willReturn( $test['isShown'] );

		$property->expects( $this->any() )
			->method( 'getLabel' )
			->willReturn( 'Quuey' );

		$property->expects( $this->any() )
			->method( 'getDIType' )
			->willReturn( \SMWDataItem::TYPE_PROPERTY );

		$parserData->setSemanticData(
			new SemanticData( DIWikiPage::newFromTitle( $title ) )
		);

		$parserData->getSemanticData()->addPropertyObjectValue(
			$property,
			DIWikiPage::newFromTitle( $title )
		);

		$instance = new Factbox(
			$store,
			$parserData,
			$this->displayTitleFinder
		);

		$instance->setCheckMagicWords(
			$checkMagicWords
		);

		$instance->doBuild();

		$this->stringValidator->assertThatStringContains(
			$expected,
			$instance->getContent()
		);
	}

	public function tableContentDataProvider() {
		$provider = [];

		$provider[] = [
			[
				'isShown'       => true,
				'isUserDefined' => true,
			],
			[ 'class="smw-factbox-value"' ]
		];

		$provider[] = [
			[
				'isShown'       => false,
				'isUserDefined' => true,
			],
			''
		];

		$provider[] = [
			[
				'isShown'       => true,
				'isUserDefined' => false,
			],
			[ 'class="smw-factbox-value"' ]
		];

		$provider[] = [
			[
				'isShown'       => false,
				'isUserDefined' => false,
			],
			''
		];

		return $provider;
	}

	/**
	 * @return array
	 */
	public function fetchContentDataProvider() {
		$title = Title::newFromText( __METHOD__ );

		$provider = [];

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$semanticData->expects( $this->any() )
			->method( 'isEmpty' )
			->willReturn( false );

		$parserData = new ParserData(
			$title,
			new ParserOutput()
		);

		$parserData->setSemanticData( $semanticData );

		$provider[] = [ $parserData ];

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ new DIProperty( '_SKEY' ) ] );

		$semanticData->expects( $this->any() )
			->method( 'isEmpty' )
			->willReturn( false );

		$parserData = new ParserData(
			$title,
			new ParserOutput()
		);

		$parserData->setSemanticData( $semanticData );

		$provider[] = [ $parserData ];

		return $provider;
	}

	protected function setupParserOutput( $semanticData ) {
		$parserOutput = new ParserOutput();
		$parserOutput->setExtensionData( 'smwdata', $semanticData );
		return $parserOutput;
	}

}
