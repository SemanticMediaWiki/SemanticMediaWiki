<?php

namespace SMW\Tests\Unit\Factbox;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Request\FauxRequest;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Factbox\CachedFactbox;
use SMW\Factbox\FactboxText;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\EntityCacheFactory;
use SMW\Tests\Utils\Mock\MockTitle;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \SMW\Factbox\CachedFactbox
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class CachedFactboxTest extends TestCase {

	private $testEnvironment;
	private $memoryCache;
	private $entityCache;
	private $spyLogger;
	private FactboxText $factboxText;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->memoryCache = new HashBagOStuff();

		$this->testEnvironment->withConfiguration(
			[
				'smwgMainCacheType' => 'hash'
			]
		);

		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->entityCache = EntityCacheFactory::newEntityCache( $this->memoryCache );

		$this->factboxText = ApplicationFactory::getInstance()->getFactboxText();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CachedFactbox::class,
			new CachedFactbox( $this->entityCache, $this->factboxText )
		);
	}

	/**
	 * @dataProvider outputDataProvider
	 */
	public function testProcessAndRetrieveContent( $parameters, $expected ) {
		$outputPage = $parameters['outputPage'];
		$instance = $this->newCachedFactbox( $parameters );

		$this->assertEmpty(
			$instance->retrieveContent( $outputPage )
		);

		$instance->prepare(
			$outputPage,
			$parameters['parserOutput']
		);

		$result = $this->factboxText->getText();

		$this->assertPreProcess(
			$expected,
			$result,
			$outputPage,
			$instance
		);

		// Re-run on the same instance
		$instance->prepare(
			$outputPage,
			$parameters['parserOutput']
		);

		$this->assertPostProcess(
			$expected,
			$result,
			$outputPage,
			$instance
		);
	}

	public function testFactboxOfARegularViewReachesReaders() {
		[ $view, $expected ] = $this->newVisibleFactboxCase();
		$this->newCachedFactbox( $view )->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertStringContainsString( $expected['text'], $this->retrieveForReader( 10001 ) );
	}

	/**
	 * @dataProvider uncachedRequestProvider
	 */
	public function testFactboxOfAPreviewOrEditViewDoesNotReachReaders( array $request ) {
		[ $view ] = $this->newVisibleFactboxCase( 10001, $request );
		$this->newCachedFactbox( $view )->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertSame( '', $this->retrieveForReader( 10001 ) );
	}

	public function uncachedRequestProvider(): iterable {
		yield 'preview' => [ [ 'wpPreview' => true ] ];
		yield 'edit' => [ [ 'action' => 'edit' ] ];
	}

	public function testFactboxBuiltWithCachingDisabledDoesNotReachReaders() {
		[ $view ] = $this->newVisibleFactboxCase();
		$instance = $this->newCachedFactbox( $view );
		$instance->isEnabled( false );

		$instance->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertSame( '', $this->retrieveForReader( 10001 ) );
	}

	public function testFactboxOfAnotherRevisionDoesNotReachReaders() {
		[ $view ] = $this->newVisibleFactboxCase( 10001 );
		$this->newCachedFactbox( $view )->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertSame( '', $this->retrieveForReader( 20002 ) );
	}

	public function testRebuildsCachedContentThatNoLongerVerifies() {
		[ $view, $expected ] = $this->newVisibleFactboxCase();
		$instance = $this->newCachedFactbox( $view );
		$instance->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->testEnvironment->addConfiguration( 'wgSecretKey', 'a different secret' );
		$instance->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertStringContainsString(
			$expected['text'],
			$instance->retrieveContent( $view['outputPage'] )
		);
	}

	public function testContentThatNoLongerVerifiesIsCachedAgainByTheNextView() {
		[ $view, $expected ] = $this->newVisibleFactboxCase();
		$instance = $this->newCachedFactbox( $view );
		$instance->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->testEnvironment->addConfiguration( 'wgSecretKey', 'a different secret' );
		$instance->prepare( $view['outputPage'], $view['parserOutput'] );
		$instance->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertStringContainsString( $expected['text'], $this->retrieveForReader( 10001 ) );
	}

	public function testPreviewShowsTheFactbox() {
		[ $view, $expected ] = $this->newVisibleFactboxCase( 10001, [ 'wpPreview' => true ] );
		$instance = $this->newCachedFactbox( $view );

		$instance->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertStringContainsString( $expected['text'], $instance->retrieveContent( $view['outputPage'] ) );
	}

	public function testFactboxWithoutARevisionDoesNotReachReaders() {
		[ $view ] = $this->newVisibleFactboxCase( 0 );
		$this->newCachedFactbox( $view )->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertSame( '', $this->retrieveForReader( 0 ) );
	}

	public function testFactboxInAnotherLanguageDoesNotReachReaders() {
		[ $view ] = $this->newVisibleFactboxCase();
		$view['outputPage']->getContext()->setLanguage( 'de' );
		$this->newCachedFactbox( $view )->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertSame( '', $this->retrieveForReader( 10001 ) );
	}

	public function testFactboxOfAnotherFeatureSetDoesNotReachReaders() {
		[ $view ] = $this->newVisibleFactboxCase();
		$instance = $this->newCachedFactbox( $view );
		$instance->setFeatureSet( 1 );
		$instance->prepare( $view['outputPage'], $view['parserOutput'] );

		$this->assertSame( '', $this->retrieveForReader( 10001 ) );
	}

	public function testReaderWithCachingDisabledGetsNoCachedFactbox() {
		[ $view ] = $this->newVisibleFactboxCase();
		$this->newCachedFactbox( $view )->prepare( $view['outputPage'], $view['parserOutput'] );
		$this->factboxText->clear();

		[ $reader ] = $this->newVisibleFactboxCase();
		$instance = $this->newInstance();
		$instance->isEnabled( false );

		$this->assertSame( '', $instance->retrieveContent( $reader['outputPage'] ) );
	}

	private function newCachedFactbox( array $parameters ): CachedFactbox {
		$this->testEnvironment->addConfiguration( 'smwgNamespacesWithSemanticLinks', $parameters['smwgNamespacesWithSemanticLinks'] );
		$this->testEnvironment->addConfiguration( 'smwgShowFactbox', $parameters['smwgShowFactbox'] );
		$this->testEnvironment->registerObject( 'Store', $parameters['store'] );

		return $this->newInstance();
	}

	private function newInstance(): CachedFactbox {
		$showFactbox = (int)ApplicationFactory::getInstance()->getSettings()->get( 'smwgShowFactbox' );

		$instance = new CachedFactbox( $this->entityCache, $this->factboxText );
		$instance->isEnabled( true );
		$instance->setShowFactbox( $showFactbox );
		$instance->setShowFactboxEdit( $showFactbox );
		$instance->setLogger( $this->spyLogger );

		return $instance;
	}

	/**
	 * Another request for the same page, which finds the factbox only in the cache
	 */
	private function retrieveForReader( int $revisionId ): string {
		$this->factboxText->clear();
		[ $view ] = $this->newVisibleFactboxCase( $revisionId );

		return $this->newInstance()->retrieveContent( $view['outputPage'] );
	}

	public function assertPreProcess( $expected, $result, $outputPage, $instance ) {
		if ( $expected['text'] ) {

			$this->assertStringContainsString(
				$expected['text'],
				$result,
				'Asserts that content was altered as expected'
			);

			// Deliberately clear the text to force content to be retrieved from the cache
			$this->factboxText->clear();

			$this->assertTrue(
				$result === $instance->retrieveContent( $outputPage ),
				'Asserts that cached content was retrievable'
			);

		} else {

			$this->assertNull(
				$result,
				'Asserts that the result is null'
			);
		}
	}

	public function assertPostProcess( $expected, $result, $outputPage, $instance ) {
		$this->assertEquals(
			$result,
			$instance->retrieveContent( $outputPage ),
			'Asserts that content is being fetched from cache'
		);

		$this->assertTrue(
			$result === $this->factboxText->getText(),
			'Asserts that content from the FactboxText text and retrieveContent() is equal'
		);

		if ( isset( $expected['isCached'] ) && $expected['isCached'] ) {

			$this->assertTrue(
				$instance->isCached(),
				'Asserts that isCached() returns true'
			);

		} else {

			$this->assertFalse(
				$instance->isCached(),
				'Asserts that isCached() returns false'
			);
		}
	}

	public function outputDataProvider() {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		$language = $languageFactory->getLanguage( 'en' );

		[ $subject, $semanticData ] = $this->newVisibleSemanticData();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$provider[] = $this->newVisibleFactboxCase();

		# 1 Factbox build, being visible, using WebRequest oldid
		$title = MockTitle::buildMock( __METHOD__ . 'title-with-oldid' );

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
			->willReturn( 10002 );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->willReturn( 10002 );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getRevisionId' )
			->willReturn( 9001 );

		$context = new RequestContext();
		$context->setRequest( new FauxRequest( [ 'oldid' => 9001 ], true ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( $context );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => 'nonempty',
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( $semanticData )
			],
			[
				'text'            => $subject->getDBKey(),
				'isCached'        => true
			]
		];

		# 2 Factbox is expected not to be visible
		$title = MockTitle::buildMock( __METHOD__ . 'title-ns-disabled' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->willReturn( $language );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 10003 );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->willReturn( 10003 );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new RequestContext() );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => false ],
				'smwgShowFactbox' => 'hidden',
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( $semanticData )
			],
			[
				'text'            => null
			]
		];

		# 3 No semantic data
		$title = MockTitle::buildMock( __METHOD__ . 'title-empty-semanticdata' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->willReturn( $language );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 10004 );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->willReturn( 10004 );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new RequestContext() );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getRevisionId' )
			->willReturn( 10004 );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'isEmpty' )
			->willReturn( true );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => 'nonempty',
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( null ),
			],
			[
				'text'            => null
			]
		];

		// #4 SpecialPage
		$title = MockTitle::buildMock( __METHOD__ . 'title-specialpage' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new RequestContext() );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => 'nonempty',
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( null ),
			],
			[
				'text'            => ''
			]
		];

		// #5 does not exist
		$title = MockTitle::buildMock( __METHOD__ . 'title-not-exists' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( false );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new RequestContext() );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => 'nonempty',
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( null ),
			],
			[
				'text'            => ''
			]
		];

		return $provider;
	}

	private function newVisibleFactboxCase( int $revisionId = 10001, array $request = [] ): array {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		$language = $languageFactory->getLanguage( 'en' );

		[ $subject, $semanticData ] = $this->newVisibleSemanticData();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$title = MockTitle::buildMock( __METHOD__ . 'title-being-visible' );

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'getPageLanguage' )
			->willReturn( $language );

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->willReturn( 10001 );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->willReturn( 10001 );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $request ) );

		$outputPage->expects( $this->any() )
			->method( 'getContext' )
			->willReturn( $context );

		$outputPage->expects( $this->any() )
			->method( 'getRevisionId' )
			->willReturn( $revisionId );

		return [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => 'nonempty',
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( $semanticData )
			],
			[
				'text'            => $subject->getDBKey(),
				'isCached'        => true
			]
		];
	}

	private function newVisibleSemanticData(): array {
		$title = MockTitle::buildMockForMainNamespace( __METHOD__ . 'mock-subject' );

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$subject = WikiPage::newFromTitle( $title );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->any() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$semanticData->expects( $this->any() )
			->method( 'hasVisibleProperties' )
			->willReturn( true );

		$semanticData->expects( $this->any() )
			->method( 'getSubSemanticData' )
			->willReturn( [] );

		$semanticData->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ WikiPage::newFromTitle( $title ) ] );

		$semanticData->expects( $this->any() )
			->method( 'getProperties' )
			->willReturn( [ new Property( __METHOD__ . 'property' ) ] );

		return [ $subject, $semanticData ];
	}

	protected function makeParserOutput( $semanticData ) {
		$parserOutput = new ParserOutput();
		$parserOutput->setExtensionData( 'smwdata', $semanticData );
		return $parserOutput;
	}

}
