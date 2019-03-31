<?php

namespace SMW\Tests\Factbox;

use Language;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Factbox\CachedFactbox;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\EntityCache;

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
class CachedFactboxTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $memoryCache;
	private $entityCache;
	private $spyLogger;

	protected function setUp() {
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
			->setMethods( ['fetch', 'save', 'saveSub', 'fetchSub', 'associate' ] )
			->getMock();

		$this->testEnvironment->registerObject( 'EntityCache', $this->entityCache );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CachedFactbox::class,
			new CachedFactbox( $this->entityCache )
		);
	}

	/**
	 * @dataProvider outputDataProvider
	 */
	public function testProcessAndRetrieveContent( $parameters, $expected ) {

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
			new EntityCache( $this->memoryCache )
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

		$result = $outputPage->mSMWFactboxText;

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

			// Deliberately clear the outputPage property to force
			// content to be retrieved from the cache
			unset( $outputPage->mSMWFactboxText );

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
			$result === $outputPage->mSMWFactboxText,
			'Asserts that content from the outputpage property and retrieveContent() is equal'
		);

		if ( isset( $expected['isCached'] ) &&  $expected['isCached'] ) {

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

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		#0 Factbox build, being visible
		$title = MockTitle::buildMock( __METHOD__ . 'title-being-visible' );

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
			->will( $this->returnValue( 10001 ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 10001 ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

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

		#1 Factbox build, being visible, using WebRequest oldid
		$title = MockTitle::buildMock( __METHOD__ . 'title-with-oldid' );

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
			->will( $this->returnValue( 10002 ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 10002 ) );

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

		#2 Factbox is expected not to be visible
		$title = MockTitle::buildMock( __METHOD__ . 'title-ns-disabled' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 10003 ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 10003 ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

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

		#3 No semantic data
		$title = MockTitle::buildMock( __METHOD__ . 'title-empty-semanticdata' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getPageLanguage' )
			->will( $this->returnValue( $language ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 10004 ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 10004 ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'isEmpty' )
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

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
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

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
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( false ) );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( new \RequestContext() ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $semanticData ) );

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

		if ( method_exists( $parserOutput, 'setExtensionData' ) ) {
			$parserOutput->setExtensionData( 'smwdata', $semanticData );
		} else {
			$parserOutput->mSMWData = $semanticData;
		}

		return $parserOutput;

	}

}
