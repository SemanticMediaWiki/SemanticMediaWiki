<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Hooks\ParserAfterTidy;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\TestEnvironment;
use SMW\DataItemFactory;
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

	protected function setUp() {
		parent::setUp();

		$settings = array(
			'smwgDeclarationProperties' => array(),
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		);

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
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\ParserAfterTidy',
			new ParserAfterTidy( $parser )
		);
	}

	public function testIsReadOnly() {

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->never() )
			->method( 'getTitle' );

		$instance = new ParserAfterTidy( $parser );
		$instance->isReadOnly( true );

		$text = '';
		$instance->process( $text );
	}

	public function testNotEnabledNamespace() {

		$namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( false ) );

		$this->testEnvironment->registerObject( 'NamespaceExaminer', $namespaceExaminer );

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

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$instance = new ParserAfterTidy( $parser );

		$text = '';
		$instance->process( $text );
	}

	private function newMockCache( $id, $containsStatus, $fetchStatus ) {

		$key = $this->applicationFactory->newCacheFactory()->getPurgeCacheKey( $id );

		$cache = $this->getMockBuilder( 'Onoi\Cache\Cache' )
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

		$this->testEnvironment->registerObject( 'Store', $parameters['store'] );

		$cache = $this->newMockCache(
			$parameters['title']->getArticleID(),
			$parameters['cache-contains'],
			$parameters['cache-fetch']
		);

		$this->testEnvironment->registerObject( 'Cache', $cache );

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

		$instance = new ParserAfterTidy( $parser );

		$this->assertTrue(
			$instance->process( $text )
		);
	}

	public function testSemanticDataParserOuputUpdateIntegration() {

		$settings = array(
			'smwgCacheType'             => 'hash',
			'smwgEnableUpdateJobs'      => false,
			'smwgUseCategoryHierarchy'  => false,
			'smwgCategoriesAsInstances' => true,
			'smwgShowHiddenCategories'  => true
		);

		$this->testEnvironment->withConfiguration( $settings );

		$text   = '';
		$title  = Title::newFromText( __METHOD__ );

		$parser = $this->parserFactory->newFromTitle( $title );

		$parser->getOutput()->addCategory( 'Foo', 'Foo' );
		$parser->getOutput()->addCategory( 'Bar', 'Bar' );
		$parser->getOutput()->setProperty( 'smw-semanticdata-status', true );

		$instance = new ParserAfterTidy( $parser );

		$this->assertTrue(
			$instance->process( $text  )
		);

		$expected = array(
			'propertyCount'  => 2,
			'propertyKeys'   => array( '_INST', '_SKEY' ),
			'propertyValues' => array( 'Foo', 'Bar', $title->getText() ),
		);

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
			->setMethods( array( 'updateData' ) )
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

		$provider[] = array(
			array(
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => true,
				'cache-fetch'    => true,
				'data-status' => true
			)
		);

		#1 No cache entry, no store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'updateData' ) )
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

		$provider[] = array(
			array(
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => false,
				'cache-fetch'    => false,
				'data-status' => true
			)
		);

		#2 SpecialPage, no store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'updateData' ) )
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

		$provider[] = array(
			array(
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => false,
				'cache-fetch'    => false,
				'data-status' => true
			)
		);

		#3 NS_FILE, no store update
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'updateData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->any() )
			->method( 'inNamespace' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_FILE ) );

		$provider[] = array(
			array(
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => true,
				'cache-fetch'    => true,
				'data-status' => true
			)
		);

		#4, 1131, No store update when fetch return FALSE
		$store = $this->getMockBuilder( 'SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'updateData' ) )
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

		$provider[] = array(
			array(
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => true,
				'cache-fetch'    => false,
				'data-status' => true
			)
		);

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

		$provider[] = array(
			array(
				'store'    => $store,
				'title'    => $title,
				'cache-contains' => true,
				'cache-fetch'    => true,
				'data-status' => false,
				'displaytitle' => 'Foo'
			)
		);

		return $provider;
	}

}
