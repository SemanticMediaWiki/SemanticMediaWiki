<?php

namespace SMW\Tests\Factbox;

use SMW\Tests\Utils\Mock\MockTitle;

use SMW\Factbox\CachedFactbox;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;

use Language;
use ParserOutput;

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

	private $applicationFactory;
	private $memoryCache;
	private $cacheOptions;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->memoryCache = $this->applicationFactory->newCacheFactory()->newFixedInMemoryCache();

		$this->cacheOptions = $this->applicationFactory->newCacheFactory()->newCacheOptions( array(
			'useCache' => true,
			'ttl' => 0
		) );

		$settings = array(
			'smwgFactboxUseCache' => true,
			'smwgCacheType'       => 'hash',
			'smwgLinksInValues'   => false,
			'smwgInlineErrors'    => true
		);

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Factbox\CachedFactbox',
			new CachedFactbox( $cache, new \stdClass )
		);
	}

	/**
	 * @dataProvider outputDataProvider
	 */
	public function testProcessAndRetrieveContent( $parameters, $expected ) {

		$this->applicationFactory->getSettings()->set(
			'smwgNamespacesWithSemanticLinks',
			$parameters['smwgNamespacesWithSemanticLinks']
		);

		$this->applicationFactory->getSettings()->set(
			'smwgShowFactbox',
			$parameters['smwgShowFactbox']
		);

		$this->applicationFactory->registerObject( 'Store', $parameters['store'] );

		$outputPage = $parameters['outputPage'];

		$instance = new CachedFactbox( $this->memoryCache, $this->cacheOptions );

		$this->assertEmpty(
			$instance->retrieveContent( $outputPage )
		);

		$instance->prepareFactboxContent(
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
		$instance->prepareFactboxContent(
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

		if ( $expected['text'] ) {

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
			->will( $this->returnValue( array( DIWikiPage::newFromTitle( $title ) ) ) );

		$semanticData->expects( $this->atLeastOnce() )
			->method( 'getProperties' )
			->will( $this->returnValue( array( new DIProperty(  __METHOD__ . 'property' ) ) ) );

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

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( $semanticData )
			),
			array(
				'text'            => $subject->getDBKey()
			)
		);

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
		$context->setRequest( new \FauxRequest( array( 'oldid' => 9001 ), true ) );

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( $semanticData )
			),
			array(
				'text'            => $subject->getDBKey()
			)
		);

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

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => false ),
				'smwgShowFactbox' => SMW_FACTBOX_HIDDEN,
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( $semanticData )
			),
			array(
				'text'            => null
			)
		);

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

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( null ),
			),
			array(
				'text'            => null
			)
		);

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

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( null ),
			),
			array(
				'text'            => ''
			)
		);

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

		$provider[] = array(
			array(
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true ),
				'smwgShowFactbox' => SMW_FACTBOX_NONEMPTY,
				'outputPage'      => $outputPage,
				'store'           => $store,
				'parserOutput'    => $this->makeParserOutput( null ),
			),
			array(
				'text'            => ''
			)
		);

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
