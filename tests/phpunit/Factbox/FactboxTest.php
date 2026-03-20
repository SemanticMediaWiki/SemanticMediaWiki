<?php

namespace SMW\Tests\Factbox;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DisplayTitleFinder;
use SMW\Factbox\CheckMagicWords;
use SMW\Factbox\Factbox;
use SMW\ParserData;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Factbox\Factbox
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class FactboxTest extends TestCase {

	private $stringValidator;
	private $testEnvironment;
	private $displayTitleFinder;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->stringValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();

		$this->testEnvironment->addConfiguration( 'smwgShowFactbox', SMW_FACTBOX_NONEMPTY );

		$this->displayTitleFinder = $this->getMockBuilder( DisplayTitleFinder::class )
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

		$store = $this->getMockBuilder( Store::class )
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

		$reflector = new ReflectionClass( Factbox::class );
		$buildHTML  = $reflector->getMethod( 'buildHTML' );

		$this->assertIsString(
			$buildHTML->invoke( $instance, $parserData->getSemanticData() )
		);
	}

	public function testTabs() {
		$this->assertStringContainsString(
			'tab-facts-list',
			Factbox::tabs( 'Foo' )
		);

		$this->assertStringContainsString(
			'tab-facts-attachment',
			Factbox::tabs( 'Foo', 'Bar' )
		);

		$this->assertStringContainsString(
			'tab-facts-derived',
			Factbox::tabs( 'Foo', 'Bar', 'Foobar' )
		);
	}

	/**
	 * @dataProvider fetchContentDataProvider
	 */
	public function testFetchContent( $parserData ) {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new Factbox(
			$store,
			$parserData,
			$this->displayTitleFinder
		);

		$reflector = new ReflectionClass( Factbox::class );

		$fetchContent = $reflector->getMethod( 'fetchContent' );

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

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$parserData->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( null );

		// Build Factbox stub object to encapsulate the method
		// without the need for other dependencies to occur
		$factbox = $this->getMockBuilder( Factbox::class )
			->setConstructorArgs( [
				$store,
				$parserData,
				$this->displayTitleFinder
			] )
			->setMethods( [ 'buildHTML' ] )
			->getMock();

		$factbox->setCheckMagicWords(
			$checkMagicWords
		);

		$factbox->expects( $this->any() )
			->method( 'buildHTML' )
			->willReturn( $setup['invokedContent'] );

		$reflector = new ReflectionClass( Factbox::class );
		$fetchContent = $reflector->getMethod( 'fetchContent' );

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

		$parserData->setSemanticData( new SemanticData( WikiPage::newFromTitle( $title ) ) );
		$parserData->getSemanticData()->addPropertyObjectValue(
			new Property( 'Foo' ),
			WikiPage::newFromTitle( $title )
		);

		$store = $this->getMockBuilder( Store::class )
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

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$property = $this->getMockBuilder( Property::class )
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
			->willReturn( DataItem::TYPE_PROPERTY );

		$parserData->setSemanticData(
			new SemanticData( WikiPage::newFromTitle( $title ) )
		);

		$parserData->getSemanticData()->addPropertyObjectValue(
			$property,
			WikiPage::newFromTitle( $title )
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

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ new Property( '_SKEY' ) ] );

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
