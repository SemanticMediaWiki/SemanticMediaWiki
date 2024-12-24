<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DataItemFactory;
use SMW\MediaWiki\Hooks\ParserAfterTidy;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;
use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\ParserAfterTidy
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserAfterTidyTest extends \PHPUnit\Framework\TestCase {

	private $semanticDataValidator;
	private $applicationFactory;
	private $parserFactory;
	private $spyLogger;
	private $testEnvironment;
	private $parser;
	private $namespaceExaminer;
	private $hookDispatcher;
	private $cache;
	private $revisionGuard;

	protected function setUp(): void {
		parent::setUp();

		$settings = [
			'smwgChangePropagationWatchlist' => [],
			'smwgMainCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		];

		$this->testEnvironment = new TestEnvironment( $settings );

		$this->spyLogger = $this->testEnvironment->getUtilityFactory()->newSpyLogger();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
		$this->parserFactory = $this->testEnvironment->getUtilityFactory()->newParserFactory();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->applicationFactory = ApplicationFactory::getInstance();

		$this->parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->hookDispatcher = $this->getMockBuilder( '\SMW\MediaWiki\HookDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'RevisionGuard', $this->revisionGuard );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ParserAfterTidy::class,
			new ParserAfterTidy( $this->parser, $this->namespaceExaminer, $this->cache )
		);
	}

	public function testIsNotReady_DoNothing() {
		$this->parser->expects( $this->never() )
			->method( 'getTitle' );

		$instance = new ParserAfterTidy(
			$this->parser,
			$this->namespaceExaminer,
			$this->cache
		);

		$instance->setLogger( $this->spyLogger );

		$instance->isReady( false );

		$text = '';
		$instance->process( $text );
	}

	public function testNotEnabledNamespace() {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		// Using this step to verify that the previous NS check
		// bailed out.
		$title->expects( $this->never() )
			->method( 'isSpecialPage' );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$instance = new ParserAfterTidy(
			$this->parser,
			$this->namespaceExaminer,
			$this->cache
		);

		$text = '';
		$instance->process( $text );
	}

	private function newMockCache( $id, $containsStatus, $fetchStatus ) {
		$key = $this->applicationFactory->newCacheFactory()->getPurgeCacheKey( $id );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->any() )
			->method( 'contains' )
			->with( $key )
			->willReturn( $containsStatus );

		$cache->expects( $this->any() )
			->method( 'fetch' )
			->with( $key )
			->willReturn( $fetchStatus );

		return $cache;
	}

	/**
	 * @dataProvider titleDataProvider
	 */
	public function testProcess( $parameters ) {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->testEnvironment->registerObject( 'Store', $parameters['store'] );

		$this->revisionGuard->expects( $this->any() )
			->method( 'getRevision' )
			->willReturn( isset( $parameters['revision'] ) ? $parameters['revision'] : null );

		$this->testEnvironment->redefineMediaWikiService( 'RevisionLookup', function () use ( $parameters ) {
			$revisionLookup = $this->getMockBuilder( '\MediaWiki\Revision\RevisionLookup' )
				->disableOriginalConstructor()
				->getMock();

			$revisionLookup->expects( $this->any() )
				->method( 'getFirstRevision' )
				->willReturn( isset( $parameters['revision'] ) ? $parameters['revision'] : null );

			return $revisionLookup;
		} );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $parameters['title'] );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$cache = $this->newMockCache(
			$parameters['title']->getArticleID(),
			$parameters['cache-contains'],
			$parameters['cache-fetch']
		);

		$parser = $this->parserFactory->newFromTitle( $parameters['title'] );
		$parserOutput = $parser->getOutput();

		$displayTitle = isset( $parameters['displaytitle'] ) ? $parameters['displaytitle'] : false;

		$parserOutput->setExtensionData( 'smw-semanticdata-status', $parameters['data-status'] );
		$parserOutput->setPageProperty( 'displaytitle', $displayTitle );

		$text   = '';

		$instance = new ParserAfterTidy(
			$parser,
			$this->namespaceExaminer,
			$cache
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$this->assertTrue(
			$instance->process( $text )
		);
	}

	public function testCanPerformOnExternalEvent() {
		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->any() )
			->method( 'getCategoryLinks' )
			->willReturn( [] );

		$parserOutput->expects( $this->any() )
			->method( 'getCategories' )
			->willReturn( [] );

		$parserOutput->expects( $this->any() )
			->method( 'getImages' )
			->willReturn( [] );

		$this->cache->expects( $this->any() )
			->method( 'fetch' )
			->with( $this->stringContains( "smw:parseraftertidy" ) )
			->willReturn( true );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$text = '';
		$title = Title::newFromText( __METHOD__ );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->parser->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( $parserOptions );

		$this->parser->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$instance = new ParserAfterTidy(
			$this->parser,
			$this->namespaceExaminer,
			$this->cache
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$instance->process( $text );
	}

	public function testSemanticDataParserOuputUpdateIntegration() {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$settings = [
			'smwgMainCacheType'             => 'hash',
			'smwgEnableUpdateJobs'      => false,
			'smwgParserFeatures'        => SMW_PARSER_HID_CATS,
			'smwgCategoryFeatures'      => SMW_CAT_REDIRECT | SMW_CAT_INSTANCE
		];

		$this->testEnvironment->withConfiguration( $settings );

		$text   = '';
		$title  = Title::newFromText( __METHOD__ );

		$parser = $this->parserFactory->newFromTitle( $title );
		$parserOutput = $parser->getOutput();

		$parserOutput->addCategory( 'Foo', 'Foo' );
		$parserOutput->addCategory( 'Bar', 'Bar' );
		$parserOutput->setExtensionData( 'smw-semanticdata-status', true );

		$instance = new ParserAfterTidy(
			$parser,
			$this->namespaceExaminer,
			$this->cache
		);

		$instance->setHookDispatcher(
			$this->hookDispatcher
		);

		$this->assertTrue(
			$instance->process( $text )
		);

		$expected = [
			'propertyCount'  => 2,
			'propertyKeys'   => [ '_INST', '_SKEY' ],
			'propertyValues' => [ 'Category:Foo', 'Category:Bar', $title->getText() ],
		];

		$parserData = $this->applicationFactory->newParserData(
			$title,
			$parser->getOutput()
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function titleDataProvider() {
		# 0 Runs store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->willReturn( false );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 5001 );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => true,
				'cache-fetch'    => true,
				'data-status' => true
			]
		];

		# 1 No cache entry, no store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->willReturn( false );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => false,
				'cache-fetch'    => false,
				'data-status' => true
			]
		];

		# 2 SpecialPage, no store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => false,
				'cache-fetch'    => false,
				'data-status' => true
			]
		];

		# 3 NS_FILE, store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'updateData' );

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->any() )
			->method( 'getRestrictions' )
			->willReturn( [] );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->willReturn( true );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_FILE );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 3001 );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'revision' => $revision,
				'cache-contains' => true,
				'cache-fetch'    => true,
				'data-status' => true
			]
		];

		# 4, 1131, No store update when fetch return FALSE
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->willReturn( false );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 5001 );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => true,
				'cache-fetch'    => false,
				'data-status' => true
			]
		];

		# 5, 1410 displaytitle
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 5001 );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => true,
				'cache-fetch'    => true,
				'data-status' => false,
				'displaytitle' => 'Foo'
			]
		];

		return $provider;
	}

}
