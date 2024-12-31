<?php

namespace SMW\Tests\MediaWiki\Hooks;

use Language;
use ParserOutput;
use MediaWiki\MediaWikiServices;
use SMW\Factbox\FactboxText;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\OutputPageParserOutput;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\PHPUnitCompat;

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
class OutputPageParserOutputTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $applicationFactory;
	private $outputPage;
	private $parserOutput;
	private $namespaceExaminer;
	private $permissionExaminer;
	private FactboxText $factboxText;

	protected function setUp(): void {
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

		$this->permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Permission\PermissionExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->factboxText = $this->applicationFactory->getFactboxText();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			OutputPageParserOutput::class,
			new OutputPageParserOutput( $this->namespaceExaminer, $this->permissionExaminer, $this->factboxText )
		);
	}

	/**
	 * @dataProvider outputDataProvider
	 */
	public function testProcess( $parameters, $expected ) {
		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( $parameters['smwgNamespacesWithSemanticLinks'] );

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
			$this->namespaceExaminer,
			$this->permissionExaminer,
			$this->factboxText
		);

		$cachedFactbox = $this->applicationFactory->create( 'FactboxFactory' )->newCachedFactbox();

		$factboxFactory = $this->getMockBuilder( '\SMW\Factbox\FactboxFactory' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'newCachedFactbox' ] )
			->getMock();

		$factboxFactory->expects( $this->any() )
			->method( 'newCachedFactbox' )
			->willReturn( $cachedFactbox );

		$this->testEnvironment->registerObject( 'FactboxFactory', $factboxFactory );

		$this->assertEmpty(
			$cachedFactbox->retrieveContent( $outputPage )
		);

		$instance->process( $outputPage, $parserOutput );

		if ( $expected['text'] == '' ) {
			$this->assertFalse( $this->factboxText->hasText() );
			return;
		}

		// For expected content continue to verify that the outputPage was amended and
		// that the content is also available via the CacheStore
		$text = $this->factboxText->getText();

		$this->assertContains( $expected['text'], $text );

		$this->assertEquals(
			$text,
			$cachedFactbox->retrieveContent( $outputPage ),
			'Asserts that retrieveContent() returns an expected text'
		);

		// Deliberately clear the text to retrieve content from the CacheStore
		$this->factboxText->clear();

		$this->assertEquals(
			$text,
			$cachedFactbox->retrieveContent( $outputPage ),
			'Asserts that retrieveContent() is returning text from cache'
		);
	}

	public function outputDataProvider() {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		$language = $languageFactory->getLanguage( 'en' );

		$title = MockTitle::buildMockForMainNamespace( __METHOD__ . 'mock-subject' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$subject = DIWikiPage::newFromTitle( $title );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'hasVisibleProperties' )
			->willReturn( true );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->willReturn( [ DIWikiPage::newFromTitle( $title ) ] );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getProperties' )
			->willReturn( [ new DIProperty( __METHOD__ . 'property' ) ] );

		# 0 Simple factbox build, returning content
		$title = MockTitle::buildMock( __METHOD__ . 'title-with-content' );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->willReturn( $language );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 9098 );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new \RequestContext() );

		$outputPage->expects( $this->any() )
			->method( 'getLanguage' )
			->willReturn( $language );

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

		# 1 Disabled namespace, no return value expected
		$title = MockTitle::buildMock( __METHOD__ . 'title-ns-disabled' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->willReturn( $language );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 90000 );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

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
			->willReturn( $language );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

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
			->willReturn( $language );

		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->willReturn( true );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$context = new \RequestContext();
		$context->setRequest( new \FauxRequest() );

		$outputPage->expects( $this->any() )
			->method( 'getContext' )
			->willReturn( $context );

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
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->willReturn( $language );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$context = new \RequestContext();
		$context->setRequest( new \FauxRequest( [ 'oldid' => 9001 ], true ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( $context );

		$outputPage->expects( $this->any() )
			->method( 'getLanguage' )
			->willReturn( $language );

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
		$parserOutput->setExtensionData( 'smwdata', $data );
		$parserOutput->setText( 'test' );
		return $parserOutput;
	}

}
