<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Request\FauxRequest;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Factbox\FactboxFactory;
use SMW\Factbox\FactboxText;
use SMW\MediaWiki\Hooks\OutputPageParserOutput;
use SMW\MediaWiki\IndicatorRegistry;
use SMW\MediaWiki\IndicatorRegistryFactory;
use SMW\MediaWiki\PermissionManager;
use SMW\MediaWiki\PostProcHandlerFactory;
use SMW\NamespaceExaminer;
use SMW\Parser\InTextAnnotationParser;
use SMW\Parser\InTextAnnotationParserFactory;
use SMW\PostProcHandler;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\Hooks\OutputPageParserOutput
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class OutputPageParserOutputTest extends TestCase {

	private $testEnvironment;
	private $applicationFactory;
	private $namespaceExaminer;
	private $userOptionsLookup;
	private FactboxText $factboxText;
	private FactboxFactory $factboxFactory;
	private $permissionManager;
	private $indicatorRegistry;
	private $indicatorRegistryFactory;
	private $postProcHandler;
	private $postProcHandlerFactory;
	private $inTextAnnotationParser;
	private $inTextAnnotationParserFactory;
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->applicationFactory = ApplicationFactory::getInstance();

		$this->testEnvironment->withConfiguration(
			[
				'smwgShowFactbox'   => 'nonempty',
				'smwgMainCacheType' => 'hash'
			]
		);

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$this->userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$this->factboxText = $this->applicationFactory->getFactboxText();
		$this->factboxFactory = $this->createMock( FactboxFactory::class );

		$this->permissionManager = $this->createMock( PermissionManager::class );

		$this->indicatorRegistry = $this->createMock( IndicatorRegistry::class );
		$this->indicatorRegistryFactory = $this->createMock( IndicatorRegistryFactory::class );
		$this->indicatorRegistryFactory->method( 'newFor' )->willReturn( $this->indicatorRegistry );

		$this->postProcHandler = $this->createMock( PostProcHandler::class );
		$this->postProcHandler->method( 'getHtml' )->willReturn( '' );
		$this->postProcHandlerFactory = $this->createMock( PostProcHandlerFactory::class );
		$this->postProcHandlerFactory->method( 'newFor' )->willReturn( $this->postProcHandler );

		$this->inTextAnnotationParser = $this->createMock( InTextAnnotationParser::class );
		$this->inTextAnnotationParserFactory = $this->createMock( InTextAnnotationParserFactory::class );
		$this->inTextAnnotationParserFactory->method( 'newFor' )->willReturn( $this->inTextAnnotationParser );

		$this->logger = $this->createMock( LoggerInterface::class );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance(): OutputPageParserOutput {
		return new OutputPageParserOutput(
			$this->namespaceExaminer,
			$this->factboxText,
			$this->factboxFactory,
			$this->userOptionsLookup,
			$this->permissionManager,
			$this->indicatorRegistryFactory,
			$this->postProcHandlerFactory,
			$this->inTextAnnotationParserFactory,
			$this->logger
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			OutputPageParserOutput::class,
			$this->newInstance()
		);
	}

	public function testProcessReturnsEarlyForSpecialPage() {
		$title = MockTitle::buildMock( __METHOD__ . 'mock-specialpage' );
		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->namespaceExaminer->expects( $this->never() )
			->method( 'isSemanticEnabled' );

		$parserOutput = new ParserOutput();
		$this->newInstance()->onOutputPageParserOutput( $outputPage, $parserOutput );

		$this->assertFalse( $this->factboxText->hasText() );
	}

	public function testProcessReturnsEarlyForRedirect() {
		$title = MockTitle::buildMock( __METHOD__ . 'mock-redirect' );
		$title->expects( $this->atLeastOnce() )
			->method( 'isRedirect' )
			->willReturn( true );

		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->namespaceExaminer->expects( $this->never() )
			->method( 'isSemanticEnabled' );

		$parserOutput = new ParserOutput();
		$this->newInstance()->onOutputPageParserOutput( $outputPage, $parserOutput );

		$this->assertFalse( $this->factboxText->hasText() );
	}

	public function testProcessReturnsEarlyForDisabledNamespace() {
		$title = MockTitle::buildMock( __METHOD__ . 'title-ns-disabled' );
		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$parserOutput = new ParserOutput();
		$this->newInstance()->onOutputPageParserOutput( $outputPage, $parserOutput );

		$this->assertFalse( $this->factboxText->hasText() );
	}

	public function testProcessForSimpleFactboxBuild() {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		$language = $languageFactory->getLanguage( 'en' );

		$title = $this->makeTitleForFactboxBuild( __METHOD__, $language );
		$outputPage = $this->makeOutputPageWithContext( $title, $language, new RequestContext() );

		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( true );

		$this->factboxFactory->method( 'newCachedFactbox' )
			->willReturn( $this->applicationFactory->create( 'FactboxFactory' )->newCachedFactbox() );

		$parserOutput = $this->makeParserOutput( $this->newSemanticDataFor( $title ) );

		$this->newInstance()->onOutputPageParserOutput( $outputPage, $parserOutput );

		// The Factbox is built and stored in `FactboxText`.
		$subject = WikiPage::newFromTitle( $title );
		$this->assertStringContainsString( $subject->getDBKey(), $this->factboxText->getText() );
	}

	public function testProcessForOldid() {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		$language = $languageFactory->getLanguage( 'en' );

		$title = $this->makeTitleForFactboxBuild( __METHOD__, $language );

		$context = new RequestContext();
		$context->setRequest( new FauxRequest( [ 'oldid' => 9001 ], true ) );
		$outputPage = $this->makeOutputPageWithContext( $title, $language, $context );

		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( true );

		$this->factboxFactory->method( 'newCachedFactbox' )
			->willReturn( $this->applicationFactory->create( 'FactboxFactory' )->newCachedFactbox() );

		// The oldid branch must funnel the cached ParserOutput through
		// SMW's in-text annotation parser to recover annotations from the
		// historical revision.
		$this->inTextAnnotationParserFactory->expects( $this->once() )
			->method( 'newFor' )
			->willReturn( $this->inTextAnnotationParser );
		$this->inTextAnnotationParser->expects( $this->once() )
			->method( 'parse' )
			->with( 'test' );

		$parserOutput = $this->makeParserOutput( $this->newSemanticDataFor( $title ) );

		$this->newInstance()->onOutputPageParserOutput( $outputPage, $parserOutput );
	}

	private function makeTitleForFactboxBuild( string $name, $language ) {
		$title = MockTitle::buildMock( $name );
		$title->expects( $this->atLeastOnce() )->method( 'exists' )->willReturn( true );
		$title->expects( $this->atLeastOnce() )->method( 'getNamespace' )->willReturn( NS_MAIN );
		$title->expects( $this->atLeastOnce() )->method( 'getPageLanguage' )->willReturn( $language );
		$title->expects( $this->atLeastOnce() )->method( 'getArticleID' )->willReturn( 9098 );
		return $title;
	}

	private function makeOutputPageWithContext( $title, $language, $context ): OutputPage {
		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->method( 'getTitle' )->willReturn( $title );
		$outputPage->method( 'getContext' )->willReturn( $context );
		$outputPage->method( 'getLanguage' )->willReturn( $language );
		$outputPage->method( 'getUser' )->willReturn( $this->createMock( User::class ) );
		return $outputPage;
	}

	private function newSemanticDataFor( $title ): SemanticData {
		$subject = WikiPage::newFromTitle( $title );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->method( 'getSubject' )->willReturn( $subject );
		$semanticData->method( 'hasVisibleProperties' )->willReturn( true );
		$semanticData->method( 'getSubSemanticData' )->willReturn( [] );
		$semanticData->method( 'getPropertyValues' )->willReturn( [ WikiPage::newFromTitle( $title ) ] );
		$semanticData->method( 'getProperties' )->willReturn( [ new Property( __METHOD__ . 'property' ) ] );

		return $semanticData;
	}

	private function makeParserOutput( SemanticData $data ): ParserOutput {
		$parserOutput = new ParserOutput();
		$parserOutput->setExtensionData( 'smwdata', $data );
		$parserOutput->setContentHolderText( 'test' );
		return $parserOutput;
	}

}
