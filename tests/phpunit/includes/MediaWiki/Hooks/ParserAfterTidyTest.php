<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Utils\Validators\SemanticDataValidator;
use SMW\Tests\Utils\ParserFactory;
use SMW\Tests\Utils\Mock\MockTitle;

use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\MediaWiki\Hooks\ParserAfterTidy;

use SMW\ApplicationFactory;
use SMW\Settings;

use Title;

/**
 * @covers \SMW\MediaWiki\Hooks\ParserAfterTidy
 *
 *
 * @group SMW
 * @group SMWExtension
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

		$this->semanticDataValidator = new SemanticDataValidator();
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->parserFactory = new ParserFactory();
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

	private function newMockCacheHandler( $id, $status ) {

		$cacheHandler = $this->getMockBuilder( 'SMW\CacheHandler' )
			->disableOriginalConstructor()
			->getMock();

		$cacheHandler->expects( $this->any() )
			->method( 'setKey' )
			->with( $this->equalTo( ArticlePurge::newCacheId( $id ) ) );

		$cacheHandler->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnValue( $status ) );

		return $cacheHandler;
	}

	/**
	 * @dataProvider titleDataProvider
	 */
	public function testProcess( $parameters ) {

		$settings = Settings::newFromArray( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false
		) );

		$this->applicationFactory->registerObject( 'Settings', $settings );
		$this->applicationFactory->registerObject( 'Store', $parameters['store'] );

		$this->applicationFactory->registerObject(
			'CacheHandler',
			$this->newMockCacheHandler( $parameters['title']->getArticleID(), $parameters['cache'] )
		);

		$parser = $this->parserFactory->newFromTitle( $parameters['title'] );

		$parser->getOutput()->setProperty(
			'smw-semanticdata-status',
			$parameters['data-status']
		);

		$text   = '';

		$instance = new ParserAfterTidy( $parser, $text );

		$this->assertTrue( $instance->process() );
	}

	public function testSemanticDataParserOuputUpdateIntegration() {

		$settings = Settings::newFromArray( array(
			'smwgCacheType'             => 'hash',
			'smwgEnableUpdateJobs'      => false,
			'smwgUseCategoryHierarchy'  => false,
			'smwgCategoriesAsInstances' => true,
			'smwgShowHiddenCategories'  => true
		) );

		$this->applicationFactory->registerObject( 'Settings', $settings );

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
				'cache'    => true,
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
				'cache'    => false,
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
				'cache'    => false,
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
				'cache'    => true,
				'data-status' => true
			)
		);

		return $provider;
	}

}
