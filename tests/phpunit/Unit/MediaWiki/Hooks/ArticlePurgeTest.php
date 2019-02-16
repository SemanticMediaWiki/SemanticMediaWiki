<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\Factbox\FactboxCache;
use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticlePurge
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ArticlePurgeTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $testEnvironment;
	private $cache;
	private $eventDispatcher;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = [
			'smwgFactboxUseCache' => true,
			'smwgMainCacheType'       => 'hash'
		];

		$this->testEnvironment = new TestEnvironment( $settings );

		$this->cache = $this->applicationFactory->newCacheFactory()->newFixedInMemoryCache();
		$this->applicationFactory->registerObject( 'Cache', $this->cache );

		$this->eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function tearDown() {
		$this->applicationFactory->clear();
		$this->testEnvironment->tearDown();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Hooks\ArticlePurge',
			new ArticlePurge( $wikiPage )
		);
	}

	/**
	 * @dataProvider titleDataProvider
	 */
	public function testProcess( $setup, $expected ) {

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->with( $this->equalTo( 'InvalidateEntityCache' ) );

		$wikiPage = new WikiPage( $setup['title'] );
		$pageId   = $wikiPage->getTitle()->getArticleID();

		$this->testEnvironment->addConfiguration(
			'smwgAutoRefreshOnPurge',
			$setup['smwgAutoRefreshOnPurge']
		);

		$this->testEnvironment->addConfiguration(
			'smwgFactboxFeatures',
			SMW_FACTBOX_PURGE_REFRESH
		);

		$this->testEnvironment->addConfiguration(
			'smwgQueryResultCacheRefreshOnPurge',
			$setup['smwgQueryResultCacheRefreshOnPurge']
		);

		$instance = new ArticlePurge();

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$cacheFactory = $this->applicationFactory->newCacheFactory();
		$factboxCacheKey = \SMW\Factbox\CachedFactbox::makeCacheKey( $pageId );
		$purgeCacheKey = $cacheFactory->getPurgeCacheKey( $pageId );

		$this->assertEquals(
			$expected['autorefreshPreProcess'],
			$this->cache->fetch( $purgeCacheKey ),
			'Asserts the autorefresh cache status before processing'
		);

		// Travis 210.5, 305.3
		$travis = $this->cache->fetch( $factboxCacheKey );
		$travisText = json_encode( $travis );
		$this->assertEquals(
			$expected['factboxPreProcess'],
			$travis,
			"Asserts the factbox cache status before processing, {$travisText}"
		);

		$this->assertFalse(
			$this->cache->fetch( $purgeCacheKey ),
			'Asserts that before processing ...'
		);

		$result = $instance->process( $wikiPage );

		// Post-process check
		$this->assertTrue(
			$result
		);

		$this->assertEquals(
			$expected['autorefreshPostProcess'],
			$this->cache->fetch( $purgeCacheKey ),
			'Asserts the autorefresh cache status after processing'
		);

		$this->assertEquals(
			$expected['factboxPostProcess'],
			$this->cache->fetch( $factboxCacheKey ),
			'Asserts the factbox cache status after processing'
		);
	}

	public function titleDataProvider() {

		$validIdTitle =  MockTitle::buildMock( 'validIdTitle' );

		$validIdTitle->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 9999 ) );

		$validIdTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		#0 Id = cache
		$provider[] = [
			[
				'title'  => $validIdTitle,
				'smwgAutoRefreshOnPurge'         => true,
				'smwgFactboxCacheRefreshOnPurge' => true,
				'smwgQueryResultCacheRefreshOnPurge' => false
			],
			[
				'factboxPreProcess'      => false,
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => true,
				'factboxPostProcess'     => false,
			]
		];

		#1 Disabled setting
		$validIdTitle =  MockTitle::buildMock( 'Disabled' );

		$validIdTitle->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 9099 ) );

		$validIdTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$provider[] = [
			[
				'title'  => $validIdTitle,
				'smwgAutoRefreshOnPurge'         => false,
				'smwgFactboxCacheRefreshOnPurge' => false,
				'smwgQueryResultCacheRefreshOnPurge' => false
			],
			[
				'factboxPreProcess'      => false,
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => false,
				'factboxPostProcess'     => false,
			]
		];

		// #2 No Id
		$nullIdTitle =  MockTitle::buildMock( 'NullId' );

		$nullIdTitle->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 0 ) );

		$nullIdTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$provider[] = [
			[
				'title'  => $nullIdTitle,
				'smwgAutoRefreshOnPurge'         => true,
				'smwgFactboxCacheRefreshOnPurge' => true,
				'smwgQueryResultCacheRefreshOnPurge' => false
			],
			[
				'factboxPreProcess'      => false,
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => false,
				'factboxPostProcess'     => false,
			]
		];

		#3 No Id
		$provider[] = [
			[
				'title'  => $nullIdTitle,
				'smwgAutoRefreshOnPurge'         => true,
				'smwgFactboxCacheRefreshOnPurge' => false,
				'smwgQueryResultCacheRefreshOnPurge' => false
			],
			[
				'factboxPreProcess'      => false,
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => false,
				'factboxPostProcess'     => false,
			]
		];

		return $provider;
	}

}
