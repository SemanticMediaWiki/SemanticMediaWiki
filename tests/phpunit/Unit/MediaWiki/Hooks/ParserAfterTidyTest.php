<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Hooks\ParserAfterTidy;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\Utils\UtilityFactory;
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

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->parserFactory =  UtilityFactory::getInstance()->newParserFactory();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );

		$settings = array(
			'smwgDeclarationProperties' => array(),
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
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

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$text  = '';

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\ParserAfterTidy',
			new ParserAfterTidy( $parser, $text )
		);
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

		$this->applicationFactory->registerObject( 'Store', $parameters['store'] );

		$cache = $this->newMockCache(
			$parameters['title']->getArticleID(),
			$parameters['cache-contains'],
			$parameters['cache-fetch']
		);

		$this->applicationFactory->registerObject( 'Cache', $cache );

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

		$instance = new ParserAfterTidy( $parser, $text );

		$this->assertTrue(
			$instance->process()
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

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		$text   = '';
		$title  = Title::newFromText( __METHOD__ );

		$parser = $this->parserFactory->newFromTitle( $title );

		$parser->getOutput()->addCategory( 'Foo', 'Foo' );
		$parser->getOutput()->addCategory( 'Bar', 'Bar' );
		$parser->getOutput()->setProperty( 'smw-semanticdata-status', true );

		$instance = new ParserAfterTidy( $parser, $text );
		$this->assertTrue( $instance->process() );

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
			->getMockForAbstractClass();

		$store->expects( $this->atLeastOnce() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
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
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
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
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

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
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
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
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
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

		$store->expects( $this->once() )
			->method( 'updateData' );

		$title = MockTitle::buildMock( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->atLeastOnce() )
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
				'data-status' => false,
				'displaytitle' => 'Foo'
			)
		);

		return $provider;
	}

}
