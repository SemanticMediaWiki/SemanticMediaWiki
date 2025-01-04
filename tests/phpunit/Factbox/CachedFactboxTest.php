<?php

namespace SMW\Tests\Factbox;

use Language;
use ParserOutput;
use MediaWiki\MediaWikiServices;
use SMW\Factbox\FactboxText;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Factbox\CachedFactbox;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\EntityCache;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Factbox\CachedFactbox
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class CachedFactboxTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $memoryCache;
	private $entityCache;
	private $spyLogger;
	private FactboxText $factboxText;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->memoryCache = ApplicationFactory::getInstance()->newCacheFactory()->newFixedInMemoryCache();

		$this->testEnvironment->withConfiguration(
			[
				'smwgFactboxUseCache' => true,
				'smwgCacheType'       => 'hash'
			]
		);

		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'fetch', 'save', 'saveSub', 'fetchSub', 'associate' ] )
			->getMock();

		$this->testEnvironment->registerObject( 'EntityCache', $this->entityCache );

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
		$this->entityCache->expects( $this->any() )
			->method( 'fetch' )
			->willReturn( false );

		$this->testEnvironment->addConfiguration(
			'smwgNamespacesWithSemanticLinks',
			$parameters['smwgNamespacesWithSemanticLinks']
		);

		$this->testEnvironment->addConfiguration(
			'smwgShowFactbox',
			$parameters['smwgShowFactbox']
		);

		$this->testEnvironment->registerObject( 'Store', $parameters['store'] );

		$outputPage = $parameters['outputPage'];

		$instance = new CachedFactbox(
			new EntityCache( $this->memoryCache ),
			$this->factboxText
		);

		$instance->isEnabled( true );

		$instance->setShowFactbox(
			$parameters['smwgShowFactbox']
		);

		$instance->setLogger(
			$this->spyLogger
		);

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

	public function assertPreProcess( $expected, $result, $outputPage, $instance ) {
		if ( $expected['text'] ) {

			$this->assertContains(
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

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		# 0 Factbox build, being visible
		$title = MockTitle::buildMock( __METHOD__ . 'title-being-visible' );

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
			->willReturn( 10001 );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->willReturn( 10001 );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new \RequestContext() );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getRevisionId' )
			->willReturn( 10001 );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( $semanticData )
			],
			[
				'text'            => $subject->getDBKey(),
				'isCached'        => true
			]
		];

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

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getRevisionId' )
			->willReturn( 9001 );

		$context = new \RequestContext();
		$context->setRequest( new \FauxRequest( [ 'oldid' => 9001 ], true ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( $context );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
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

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new \RequestContext() );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => false ],
				'smwgShowFactbox' => SMW_FACTBOX_HIDDEN,
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

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new \RequestContext() );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getRevisionId' )
			->willReturn( 10004 );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'isEmpty' )
			->willReturn( true );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
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

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new \RequestContext() );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
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

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->willReturn( new \RequestContext() );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$provider[] = [
			[
				'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ],
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
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

	protected function makeParserOutput( $semanticData ) {
		$parserOutput = new ParserOutput();
		$parserOutput->setExtensionData( 'smwdata', $semanticData );
		return $parserOutput;
	}

}
