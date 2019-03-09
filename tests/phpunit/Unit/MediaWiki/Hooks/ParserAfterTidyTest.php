<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\ApplicationFactory;
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
class ParserAfterTidyTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $applicationFactory;
	private $parserFactory;
	private $spyLogger;
	private $testEnvironment;
	private $parser;
	private $namespaceExaminer;
	private $cache;

	protected function setUp() {
		parent::setUp();

		$settings = [
			'smwgChangePropagationWatchlist' => [],
			'smwgMainCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		];

		$this->testEnvironment = new TestEnvironment( $settings );
		$this->dataItemFactory = new DataItemFactory();

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

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ParserAfterTidy::class,
			new ParserAfterTidy( $this->parser, $this->namespaceExaminer, $this->cache )
		);
	}

	public function testIsReadOnly() {

		$this->parser->expects( $this->never() )
			->method( 'getTitle' );

		$instance = new ParserAfterTidy(
			$this->parser,
			$this->namespaceExaminer,
			$this->cache
		);

		$instance->isReadOnly( true );

		$text = '';
		$instance->process( $text );
	}

	public function testNotEnabledNamespace() {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( false ) );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		// Using this step to verify that the previous NS check
		// bailed out.
		$title->expects( $this->never() )
			->method( 'isSpecialPage' );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

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
			->with( $this->equalTo( $key ) )
			->will( $this->returnValue( $containsStatus ) );

		$cache->expects( $this->any() )
			->method( 'fetch' )
			->with( $this->equalTo( $key ) )
			->will( $this->returnValue( $fetchStatus ) );

		return $cache;
	}

	/**
	 * @dataProvider titleDataProvider
	 */
	public function testProcess( $parameters ) {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

		$this->testEnvironment->registerObject( 'Store', $parameters['store'] );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getRevision' )
			->will( $this->returnValue( isset( $parameters['revision'] ) ? $parameters['revision'] : null ) );

		$wikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $parameters['title'] ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->any() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$cache = $this->newMockCache(
			$parameters['title']->getArticleID(),
			$parameters['cache-contains'],
			$parameters['cache-fetch']
		);

		$parser = $this->parserFactory->newFromTitle( $parameters['title'] );

		$parser->getOutput()->setProperty(
			'smw-semanticdata-status',
			$parameters['data-status']
		);

		$parser->getOutput()->setProperty(
			'displaytitle',
			isset( $parameters['displaytitle'] ) ? $parameters['displaytitle'] : false
		);

		$text   = '';

		$instance = new ParserAfterTidy(
			$parser,
			$this->namespaceExaminer,
			$cache
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
			->will( $this->returnValue( [] ) );

		$parserOutput->expects( $this->any() )
			->method( 'getImages' )
			->will( $this->returnValue( [] ) );

		$this->cache->expects( $this->any() )
			->method( 'fetch' )
			->with( $this->stringContains( "smw:parseraftertidy" ) )
			->will( $this->returnValue( true ) );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

		$text = '';
		$title = Title::newFromText( __METHOD__ );

		$this->parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->parser->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( $parserOptions ) );

		$this->parser->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$instance = new ParserAfterTidy(
			$this->parser,
			$this->namespaceExaminer,
			$this->cache
		);

		$instance->process( $text );
	}

	public function testSemanticDataParserOuputUpdateIntegration() {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

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

		$parser->getOutput()->addCategory( 'Foo', 'Foo' );
		$parser->getOutput()->addCategory( 'Bar', 'Bar' );
		$parser->getOutput()->setProperty( 'smw-semanticdata-status', true );

		$instance = new ParserAfterTidy(
			$parser,
			$this->namespaceExaminer,
			$this->cache
		);

		$this->assertTrue(
			$instance->process( $text  )
		);

		$expected = [
			'propertyCount'  => 2,
			'propertyKeys'   => [ '_INST', '_SKEY' ],
			'propertyValues' => [ 'Foo', 'Bar', $title->getText() ],
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

		#0 Runs store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->will( $this->returnValue( false ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 5001 ) );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => true,
				'cache-fetch'    => true,
				'data-status' => true
			]
		];

		#1 No cache entry, no store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->will( $this->returnValue( false ) );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => false,
				'cache-fetch'    => false,
				'data-status' => true
			]
		];

		#2 SpecialPage, no store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->will( $this->returnValue( true ) );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => false,
				'cache-fetch'    => false,
				'data-status' => true
			]
		];

		#3 NS_FILE, store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'updateData' );

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->any() )
			->method( 'getFirstRevision' )
			->will( $this->returnValue( $revision ) );

		$title->expects( $this->any() )
			->method( 'getRestrictions' )
			->will( $this->returnValue( [] ) );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_FILE ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 3001 ) );


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

		#4, 1131, No store update when fetch return FALSE
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->will( $this->returnValue( false ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 5001 ) );

		$provider[] = [
			[
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => true,
				'cache-fetch'    => false,
				'data-status' => true
			]
		];

		#5, 1410 displaytitle
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 5001 ) );

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
