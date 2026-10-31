<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\EventDispatcher;
use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;
use Wikimedia\ObjectCache\HashBagOStuff;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticlePurge
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ArticlePurgeTest extends TestCase {

	private $applicationFactory;
	private $testEnvironment;
	private $cache;
	private $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = [
			'smwgMainCacheType' => 'hash'
		];

		$this->testEnvironment = new TestEnvironment( $settings );

		$this->cache = new HashBagOStuff();

		$this->eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function tearDown(): void {
		$this->applicationFactory->clear();
		$this->testEnvironment->tearDown();

		parent::tearDown();
	}

	/**
	 * @dataProvider titleDataProvider
	 */
	public function testProcess( $setup, $expected ) {
		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->with( 'InvalidateEntityCache' );

		$wikiPage = new WikiPage( $setup['title'] );
		$pageId   = $wikiPage->getTitle()->getArticleID();

		$settings = $this->createMock( Settings::class );
		$settings->method( 'get' )
			->willReturnMap( [
				[ 'smwgAutoRefreshOnPurge', $setup['smwgAutoRefreshOnPurge'] ],
				[ 'smwgFactboxFeatures', [ 'purge-refresh' ] ],
				[ 'smwgQueryResultCacheRefreshOnPurge', $setup['smwgQueryResultCacheRefreshOnPurge'] ],
			] );

		$store = $this->createMock( Store::class );

		$instance = new ArticlePurge( $store, $this->cache, $settings, $this->eventDispatcher );

		$purgeCacheKey = smwfCacheKey( ArticlePurge::CACHE_NAMESPACE, $pageId );

		$this->assertEquals(
			$expected['autorefreshPreProcess'],
			$this->cache->get( $purgeCacheKey ),
			'Asserts the autorefresh cache status before processing'
		);

		$this->assertFalse(
			$this->cache->get( $purgeCacheKey ),
			'Asserts that before processing ...'
		);

		$result = $instance->onArticlePurge( $wikiPage );

		// Post-process check
		$this->assertTrue(
			$result
		);

		$this->assertEquals(
			$expected['autorefreshPostProcess'],
			$this->cache->get( $purgeCacheKey ),
			'Asserts the autorefresh cache status after processing'
		);
	}

	public function titleDataProvider() {
		$validIdTitle = MockTitle::buildMock( 'validIdTitle' );

		$validIdTitle->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 9999 );

		$validIdTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$validIdTitle->expects( $this->any() )
			->method( 'canExist' )
			->willReturn( true );

		# 0 Id = cache
		$provider[] = [
			[
				'title'  => $validIdTitle,
				'smwgAutoRefreshOnPurge'         => true,
				'smwgFactboxCacheRefreshOnPurge' => true,
				'smwgQueryResultCacheRefreshOnPurge' => false
			],
			[
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => true,
			]
		];

		# 1 Disabled setting
		$validIdTitle = MockTitle::buildMock( 'Disabled' );

		$validIdTitle->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 9099 );

		$validIdTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$validIdTitle->expects( $this->any() )
			->method( 'canExist' )
			->willReturn( true );

		$provider[] = [
			[
				'title'  => $validIdTitle,
				'smwgAutoRefreshOnPurge'         => false,
				'smwgFactboxCacheRefreshOnPurge' => false,
				'smwgQueryResultCacheRefreshOnPurge' => false
			],
			[
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => false,
			]
		];

		// #2 No Id
		$nullIdTitle = MockTitle::buildMock( 'NullId' );

		$nullIdTitle->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->willReturn( 0 );

		$nullIdTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$nullIdTitle->expects( $this->any() )
			->method( 'canExist' )
			->willReturn( true );

		$provider[] = [
			[
				'title'  => $nullIdTitle,
				'smwgAutoRefreshOnPurge'         => true,
				'smwgFactboxCacheRefreshOnPurge' => true,
				'smwgQueryResultCacheRefreshOnPurge' => false
			],
			[
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => false,
			]
		];

		# 3 No Id
		$provider[] = [
			[
				'title'  => $nullIdTitle,
				'smwgAutoRefreshOnPurge'         => true,
				'smwgFactboxCacheRefreshOnPurge' => false,
				'smwgQueryResultCacheRefreshOnPurge' => false
			],
			[
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => false,
			]
		];

		return $provider;
	}

}
