<?php

namespace SMW\Tests\MediaWiki\Hooks;

use Language;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\OutputPageParserOutput;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\Hooks\OutputPageParserOutput
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class OutputPageParserOutputTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $outputPage;
	private $parserOutput;
	private $namespaceExaminer;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->applicationFactory = ApplicationFactory::getInstance();

		$this->testEnvironment->withConfiguration(
			[
				'smwgShowFactbox'      => SMW_FACTBOX_NONEMPTY,
				'smwgFactboxUseCache'  => true,
				'smwgMainCacheType'        => 'hash'
			]
		);

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			OutputPageParserOutput::class,
			new OutputPageParserOutput( $this->namespaceExaminer )
		);
	}

	/**
	 * @dataProvider outputDataProvider
	 */
	public function testProcess( $parameters, $expected ) {

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( $parameters['smwgNamespacesWithSemanticLinks'] ) );

		$entityCache = new \SMW\EntityCache(
			$this->applicationFactory->newCacheFactory()->newFixedInMemoryCache()
		);

		$this->testEnvironment->registerObject( 'EntityCache', $entityCache );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$outputPage   = $parameters['outputPage'];
		$parserOutput = $parameters['parserOutput'];

		$instance = new OutputPageParserOutput(
			$this->namespaceExaminer
		);

		$cachedFactbox = $this->applicationFactory->create( 'FactboxFactory' )->newCachedFactbox();

		$factboxFactory = $this->getMockBuilder( '\SMW\Factbox\FactboxFactory' )
			->disableOriginalConstructor()
			->setMethods( [ 'newCachedFactbox' ] )
			->getMock();

		$factboxFactory->expects( $this->any() )
			->method( 'newCachedFactbox' )
			->will( $this->returnValue( $cachedFactbox ) );

		$this->testEnvironment->registerObject( 'FactboxFactory', $factboxFactory );

		$this->assertEmpty(
			$cachedFactbox->retrieveContent( $outputPage )
		);

		$instance->process( $outputPage, $parserOutput );

		if ( $expected['text'] == '' ) {
			return $this->assertFalse( isset( $outputPage->mSMWFactboxText ) );
		}

		// For expected content continue to verify that the outputPage was amended and
		// that the content is also available via the CacheStore
		$text = $outputPage->mSMWFactboxText;

		$this->assertContains( $expected['text'], $text );

		$this->assertEquals(
			$text,
			$cachedFactbox->retrieveContent( $outputPage ),
			'Asserts that retrieveContent() returns an expected text'
		);

		// Deliberately clear the outputPage Property to retrieve
		// content from the CacheStore
		unset( $outputPage->mSMWFactboxText );

		$this->assertEquals(
			$text,
			$cachedFactbox->retrieveContent( $outputPage ),
			'Asserts that retrieveContent() is returning text from cache'
		);
	}

	public function outputDataProvider() {

		$language = Language::factory( 'en' );

		$title = MockTitle::buildMockForMainNamespace( __METHOD__ . 'mock-subject' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$subject = DIWikiPage::newFromTitle( $title );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'hasVisibleProperties' )
			->will( $this->returnValue( true ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ DIWikiPage::newFromTitle( $title ) ] ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ new DIProperty(  __METHOD__ . 'property' ) ] ) );

		#0 Simple factbox build, returning content
		$title = MockTitle::buildMock( __METHOD__ . 'title-with-content' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 9098 ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

		$outputPage->expects( $this->any() )
			->method( 'getLanguage' )
			->will( $this->returnValue( $language ) );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => true,
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			],
			[
				'text'         => $subject->getDBKey()
			]
		];

		#1 Disabled namespace, no return value expected
		$title = MockTitle::buildMock( __METHOD__ . 'title-ns-disabled' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 90000 ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => false,
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			],
			[
				'text'         => ''
			]
		];

		// #2 Specialpage, no return value expected
		$title = MockTitle::buildMock( __METHOD__ . 'mock-specialpage' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => true,
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			],
			[
				'text'         => ''
			]
		];

		// #3 Redirect, no return value expected
		$title = MockTitle::buildMock( __METHOD__ . 'mock-redirect' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$context = new \RequestContext( );
		$context->setRequest( new \FauxRequest() );

		$outputPage->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => true,
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			],
			[
				'text'         => ''
			]
		];

		// #4 Oldid
		$title = MockTitle::buildMockForMainNamespace( __METHOD__ . 'mock-oldid' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$context = new \RequestContext( );
		$context->setRequest( new \FauxRequest( [ 'oldid' => 9001 ], true ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		$outputPage->expects( $this->any() )
			->method( 'getLanguage' )
			->will( $this->returnValue( $language ) );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => true,
				'outputPage'   => $outputPage,
				'parserOutput' => $this->makeParserOutput( $semanticData ),
			],
			[
				'text'         => $subject->getDBKey()
			]
		];

		return $provider;
	}

	protected function makeParserOutput( $data ) {

		$parserOutput = new ParserOutput();

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', $data );
		} else {
			$parserOutput->mSMWData = $data;
		}

		return $parserOutput;
	}

}
