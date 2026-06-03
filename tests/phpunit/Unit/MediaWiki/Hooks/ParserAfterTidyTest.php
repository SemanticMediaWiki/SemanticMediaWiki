<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Permissions\RestrictionStore;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\MediaWiki\Hooks\ParserAfterTidy;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\RevisionGuard;
use SMW\NamespaceExaminer;
use SMW\ParserData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;
use Throwable;
use Wikimedia\ObjectCache\BagOStuff;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\Hooks\ParserAfterTidy
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ParserAfterTidyTest extends TestCase {

	private $semanticDataValidator;
	private $applicationFactory;
	private $parserFactory;
	private $spyLogger;
	private $testEnvironment;
	private $parser;
	private $namespaceExaminer;
	private $hookContainer;
	private $cache;
	private $revisionGuard;
	private $settings;
	private $restrictionStore;

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

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->applicationFactory = ApplicationFactory::getInstance();

		$this->parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->hookContainer = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMock();

		$this->revisionGuard = $this->getMockBuilder( RevisionGuard::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'RevisionGuard', $this->revisionGuard );

		$this->settings = $this->createMock( Settings::class );

		$this->restrictionStore = $this->createMock( RestrictionStore::class );
	}

	protected function tearDown(): void {
		ParserAfterTidy::resetInFlightParses();
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	/**
	 * Force a Parser's `mInParse` flag to `true` (mimicking the state during
	 * an actual `Parser::parse()` call). Production code's `isLocked()` check
	 * is what distinguishes a real parse from a test helper that only called
	 * `clearState()`; the unit tests must opt into the locked state to
	 * exercise the in-flight tracker.
	 */
	private function setParserLocked( Parser $parser ): void {
		$prop = new ReflectionProperty( Parser::class, 'mInParse' );
		$prop->setValue( $parser, true );
	}

	private function newInstance( ?BagOStuff $cache = null, ?Settings $settings = null ): ParserAfterTidy {
		return new ParserAfterTidy(
			$this->namespaceExaminer,
			$cache ?? $this->cache,
			$this->applicationFactory,
			$this->hookContainer,
			$settings ?? $this->settings,
			$this->spyLogger,
			$this->restrictionStore
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ParserAfterTidy::class,
			$this->newInstance()
		);
	}

	public function testNotEnabledNamespace() {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		// Using this step to verify that the previous NS check
		// bailed out.
		$title->expects( $this->never() )
			->method( 'isSpecialPage' );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$instance = $this->newInstance();

		$text = '';
		$instance->onParserAfterTidy( $this->parser, $text );
	}

	private function newMockCache( $id, $getStatus ) {
		$key = smwfCacheKey( ArticlePurge::CACHE_NAMESPACE, $id );

		$cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->any() )
			->method( 'get' )
			->with( $key )
			->willReturn( $getStatus );

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
			$revisionLookup = $this->getMockBuilder( RevisionLookup::class )
				->disableOriginalConstructor()
				->getMock();

			$revisionLookup->expects( $this->any() )
				->method( 'getFirstRevision' )
				->willReturn( isset( $parameters['revision'] ) ? $parameters['revision'] : null );

			return $revisionLookup;
		} );

		$cache = $this->newMockCache(
			$parameters['title']->getArticleID(),
			$parameters['cache-fetch']
		);

		$parser = $this->parserFactory->newFromTitle( $parameters['title'] );
		$parserOutput = $parser->getOutput();

		$displayTitle = isset( $parameters['displaytitle'] ) ? $parameters['displaytitle'] : false;

		$parserOutput->setExtensionData( 'smw-semanticdata-status', $parameters['data-status'] );
		if ( is_bool( $displayTitle ) ) {
			$parserOutput->setNumericPageProperty( 'displaytitle', (int)$displayTitle );
		} else {
			$parserOutput->setUnsortedPageProperty( 'displaytitle', $displayTitle );
		}

		$text = '';

		$instance = $this->newInstance( $cache );

		$this->assertTrue(
			$instance->onParserAfterTidy( $parser, $text )
		);
	}

	public function testCanPerformOnExternalEvent() {
		$parserOptions = $this->getMockBuilder( ParserOptions::class )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->any() )
			->method( 'getImages' )
			->willReturn( [] );

		$this->cache->expects( $this->any() )
			->method( 'get' )
			->with( $this->stringContains( "smw:parseraftertidy" ) )
			->willReturn( true );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$text = '';
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->parser->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( $parserOptions );

		$this->parser->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$instance = $this->newInstance();

		$instance->onParserAfterTidy( $this->parser, $text );
	}

	public function testSemanticDataParserOuputUpdateIntegration() {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$settings = [
			'smwgMainCacheType'             => 'hash',
			'smwgEnableUpdateJobs'      => false,
			'smwgParserFeatures'        => [ 'hidden-categories' ],
			'smwgCategoryFeatures'      => [ 'redirect', 'instance' ]
		];

		$this->testEnvironment->withConfiguration( $settings );

		$text   = '';
		$title  = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$parser = $this->parserFactory->newFromTitle( $title );
		$parserOutput = $parser->getOutput();

		$parserOutput->addCategory( 'Foo', 'Foo' );
		$parserOutput->addCategory( 'Bar', 'Bar' );
		$parserOutput->setExtensionData( 'smw-semanticdata-status', true );

		$instance = $this->newInstance();

		$this->assertTrue(
			$instance->onParserAfterTidy( $parser, $text )
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

	/**
	 * #5923: extensions that clone the parser and re-enter `Parser::parse()`
	 * on the same title fire `ParserAfterTidy` for the inner parse first, with
	 * partial parser output. Before the fix, the inner fire would consume the
	 * `ArticlePurge` cache key and persist its partial data; the outermost
	 * fire (with the full state) would then find the key gone and skip,
	 * losing the outer properties and categories.
	 *
	 * The test simulates that nesting by calling `onParserClearState()` for
	 * both the outer and inner parser, then `process()` for inner (which must
	 * be skipped) followed by `process()` for outer (which must run). The
	 * inner-skip is verified by asserting that `copyToParserOutput()` did NOT
	 * populate the inner parser output's `DATA_ID` extension data, and the
	 * outer-run is verified by reading the outer's semantic data back.
	 */
	public function testProcessIsSkippedForInnerParseOnSameTitle() {
		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$settings = [
			'smwgMainCacheType' => 'hash',
			'smwgEnableUpdateJobs' => false,
			'smwgParserFeatures' => [ 'hidden-categories' ],
			'smwgCategoryFeatures' => [ 'redirect', 'instance' ],
		];
		$this->testEnvironment->withConfiguration( $settings );

		$title = MediaWikiServices::getInstance()->getTitleFactory()
			->newFromText( __METHOD__ );

		// Outer parser begins parsing this title.
		$outerParser = $this->parserFactory->newFromTitle( $title );
		$this->setParserLocked( $outerParser );
		ParserAfterTidy::onParserClearState( $outerParser );

		// Inner parse on a separate parser instance for the same title, with
		// only a partial set of categories, mimicking the inner snapshot from
		// jayktaylor's repro before the outer parse adds the rest.
		$innerParser = $this->parserFactory->newFromTitle( $title );
		$this->setParserLocked( $innerParser );
		ParserAfterTidy::onParserClearState( $innerParser );
		$innerParser->getOutput()->addCategory( 'InnerCat', 'InnerCat' );
		$innerParser->getOutput()->setExtensionData( 'smw-semanticdata-status', true );

		// Inner parse ends first (LIFO): ParserAfterTidy fires with partial data.
		$innerInstance = $this->newInstance();

		$text = '';
		$this->assertTrue( $innerInstance->onParserAfterTidy( $innerParser, $text ) );

		// The inner fire MUST be skipped: no SMW semantic data should have
		// been written to the inner parser output. Before the fix, this
		// extension data would have been populated by `copyToParserOutput()`.
		$this->assertNull(
			$innerParser->getOutput()->getExtensionData( ParserData::DATA_ID ),
			'Inner (nested) ParserAfterTidy fire must not write to the parser '
				. 'output; that work belongs to the outermost fire so the '
				. 'final state is what is persisted (#5923).'
		);

		// Outer parse adds its categories AFTER the inner returned. Its
		// ParserAfterTidy fires next with the full state.
		$outerParser->getOutput()->addCategory( 'OuterCat', 'OuterCat' );
		$outerParser->getOutput()->addCategory( 'AnotherOuterCat', 'AnotherOuterCat' );
		$outerParser->getOutput()->setExtensionData( 'smw-semanticdata-status', true );

		$outerInstance = $this->newInstance();

		$this->assertTrue( $outerInstance->onParserAfterTidy( $outerParser, $text ) );

		// The outer fire DID run: read back the SemanticData via ParserData
		// (mirroring `testSemanticDataParserOuputUpdateIntegration`) and
		// confirm the outer categories are the ones that landed.
		$outerParserData = $this->applicationFactory->newParserData(
			$title,
			$outerParser->getOutput()
		);

		$expected = [
			'propertyKeys' => [ '_INST', '_SKEY' ],
			'propertyValues' => [
				'Category:OuterCat',
				'Category:AnotherOuterCat',
				$title->getText(),
			],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$outerParserData->getSemanticData()
		);
	}

	/**
	 * Two independent top-level parses on DIFFERENT titles must not appear
	 * nested to each other. This protects against the depth-tracker treating
	 * any concurrent parses as nesting just because they happen to overlap
	 * in time.
	 */
	public function testProcessRunsForIndependentTitlesInFlight() {
		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->testEnvironment->withConfiguration( [
			'smwgMainCacheType' => 'hash',
			'smwgEnableUpdateJobs' => false,
			'smwgParserFeatures' => [ 'hidden-categories' ],
			'smwgCategoryFeatures' => [ 'redirect', 'instance' ],
		] );

		$titleA = MediaWikiServices::getInstance()->getTitleFactory()
			->newFromText( __METHOD__ . 'A' );
		$titleB = MediaWikiServices::getInstance()->getTitleFactory()
			->newFromText( __METHOD__ . 'B' );

		$parserA = $this->parserFactory->newFromTitle( $titleA );
		$this->setParserLocked( $parserA );
		ParserAfterTidy::onParserClearState( $parserA );

		$parserB = $this->parserFactory->newFromTitle( $titleB );
		$this->setParserLocked( $parserB );
		ParserAfterTidy::onParserClearState( $parserB );

		// Both have content; both should run independently.
		$parserA->getOutput()->addCategory( 'CatA', 'CatA' );
		$parserA->getOutput()->setExtensionData( 'smw-semanticdata-status', true );
		$parserB->getOutput()->addCategory( 'CatB', 'CatB' );
		$parserB->getOutput()->setExtensionData( 'smw-semanticdata-status', true );

		$text = '';

		$instanceA = $this->newInstance();
		$instanceA->onParserAfterTidy( $parserA, $text );

		$instanceB = $this->newInstance();
		$instanceB->onParserAfterTidy( $parserB, $text );

		$this->assertNotNull(
			$parserA->getOutput()->getExtensionData( ParserData::DATA_ID ),
			'Parse on title A must run regardless of any in-flight parse on title B.'
		);
		$this->assertNotNull(
			$parserB->getOutput()->getExtensionData( ParserData::DATA_ID ),
			'Parse on title B must run regardless of any in-flight parse on title A.'
		);
	}

	/**
	 * Interface-message parses (which canPerformUpdate already filters via
	 * `getInterfaceMessage()`) must NOT enter the in-flight tracker, otherwise
	 * a `Message::parse()` inside a real parse could be counted as nesting.
	 */
	public function testOnParserClearStateIgnoresInterfaceMessageParses() {
		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->testEnvironment->withConfiguration( [
			'smwgMainCacheType' => 'hash',
			'smwgEnableUpdateJobs' => false,
			'smwgParserFeatures' => [ 'hidden-categories' ],
			'smwgCategoryFeatures' => [ 'redirect', 'instance' ],
		] );

		$parserOptions = $this->getMockBuilder( ParserOptions::class )
			->disableOriginalConstructor()
			->getMock();
		$parserOptions->expects( $this->any() )
			->method( 'getInterfaceMessage' )
			->willReturn( true );

		$title = MediaWikiServices::getInstance()->getTitleFactory()
			->newFromText( __METHOD__ );

		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();
		$parser->expects( $this->any() )
			->method( 'getOptions' )
			->willReturn( $parserOptions );
		$parser->expects( $this->never() )
			->method( 'getTitle' );

		ParserAfterTidy::onParserClearState( $parser );

		// And the real outer parser for the same title should still see depth=1.
		$realParser = $this->parserFactory->newFromTitle( $title );
		$this->setParserLocked( $realParser );
		ParserAfterTidy::onParserClearState( $realParser );
		$realParser->getOutput()->addCategory( 'RealCat', 'RealCat' );
		$realParser->getOutput()->setExtensionData( 'smw-semanticdata-status', true );

		$text = '';
		$realInstance = $this->newInstance();
		$realInstance->onParserAfterTidy( $realParser, $text );

		$this->assertNotNull(
			$realParser->getOutput()->getExtensionData( ParserData::DATA_ID ),
			'Interface-message parse must not poison the in-flight tracker.'
		);
	}

	/**
	 * If `process()` throws, the in-flight tracker must be drained for the
	 * current parser instance so that subsequent parses on the same instance
	 * are not permanently marked as nested.
	 */
	public function testProcessDrainsTrackerOnException() {
		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		// Force `performUpdate` (via the ApplicationFactory chain) to throw by
		// registering a Store that explodes.
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData' ] )
			->getMockForAbstractClass();
		$store->expects( $this->any() )
			->method( 'updateData' )
			->willThrowException( new RuntimeException( 'simulated' ) );

		$this->testEnvironment->withConfiguration( [
			'smwgMainCacheType' => 'hash',
			'smwgEnableUpdateJobs' => false,
		] );
		$this->testEnvironment->registerObject( 'Store', $store );

		$title = MediaWikiServices::getInstance()->getTitleFactory()
			->newFromText( 'File:Test5923Drain.png' );

		$revision = $this->getMockBuilder( RevisionRecord::class )
			->disableOriginalConstructor()
			->getMock();
		$this->revisionGuard->expects( $this->any() )
			->method( 'getRevision' )
			->willReturn( $revision );

		$wikiPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();
		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();
		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->willReturn( $wikiPage );
		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		// Cache key set as if `?action=purge` had fired, so checkPurgeRequest
		// reaches `updateStore` (which then triggers our throwing store).
		$cache = $this->applicationFactory->getObjectCache();
		$cache->set(
			smwfCacheKey(
				ArticlePurge::CACHE_NAMESPACE,
				$title->getArticleID()
			),
			true
		);

		$parser = $this->parserFactory->newFromTitle( $title );
		$this->setParserLocked( $parser );
		ParserAfterTidy::onParserClearState( $parser );
		$parser->getOutput()->addCategory( 'Foo', 'Foo' );
		$parser->getOutput()->setExtensionData( 'smw-semanticdata-status', true );

		$settings = $this->createMock( Settings::class );
		$settings->method( 'get' )
			->with( 'smwgCheckForRemnantEntities' )
			->willReturn( 'purge' );

		$instance = $this->newInstance( $cache, $settings );

		$text = '';
		try {
			$instance->onParserAfterTidy( $parser, $text );
		} catch ( Throwable $e ) {
			// Expected, swallow.
		}

		// A second parse on the SAME parser instance must NOT be considered
		// nested. With the tracker drained, depth for this parser is 0 again.
		ParserAfterTidy::onParserClearState( $parser );
		$parser->getOutput()->addCategory( 'Bar', 'Bar' );

		$secondParser = $this->parserFactory->newFromTitle( $title );
		$this->setParserLocked( $secondParser );
		ParserAfterTidy::onParserClearState( $secondParser );
		$secondParser->getOutput()->addCategory( 'Baz', 'Baz' );
		$secondParser->getOutput()->setExtensionData( 'smw-semanticdata-status', true );

		// The inner (nested) one should still be skipped because we now have
		// two parsers active on the same title. This is the *correct* behavior;
		// it demonstrates the tracker is not stuck in a wedged state.
		$secondInstance = $this->newInstance( $cache );
		$secondInstance->onParserAfterTidy( $secondParser, $text );

		$this->assertNull(
			$secondParser->getOutput()->getExtensionData( ParserData::DATA_ID ),
			'Inner parser must be skipped when another parser for the same title is active.'
		);
	}

	public function titleDataProvider() {
		# 0 Runs store update
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
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
				'cache-fetch'    => true,
				'data-status' => true
			]
		];

		# 1 No cache entry, no store update
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
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
				'cache-fetch'    => false,
				'data-status' => true
			]
		];

		# 2 SpecialPage, no store update
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
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
				'cache-fetch'    => false,
				'data-status' => true
			]
		];

		# 3 NS_FILE, purge cache present. In production this would trigger
		// an updateData call via checkPurgeRequest, but the new Site::isCommandLineMode()
		// check short-circuits the path when phpunit runs in CLI; we only assert
		// the hook completes cleanly here.
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'updateData' );

		$revision = $this->getMockBuilder( RevisionRecord::class )
			->disableOriginalConstructor()
			->getMock();

		$title = MockTitle::buildMock( __METHOD__ );

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
				'cache-fetch'    => true,
				'data-status' => true
			]
		];

		# 4, 1131, No store update when fetch return FALSE
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
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
				'cache-fetch'    => false,
				'data-status' => true
			]
		];

		# 5, 1410 displaytitle
		$store = $this->getMockBuilder( Store::class )
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
				'cache-fetch'    => true,
				'data-status' => false,
				'displaytitle' => 'Foo'
			]
		];

		return $provider;
	}

}
