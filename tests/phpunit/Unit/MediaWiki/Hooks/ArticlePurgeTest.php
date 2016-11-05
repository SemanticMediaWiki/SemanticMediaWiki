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

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$settings = array(
			'smwgFactboxUseCache' => true,
			'smwgCacheType'       => 'hash',
			'smwgLinksInValues'   => false,
			'smwgInlineErrors'    => true
		);

		$this->testEnvironment = new TestEnvironment( $settings );

		$this->cache = $this->applicationFactory->newCacheFactory()->newFixedInMemoryCache();
		$this->applicationFactory->registerObject( 'Cache', $this->cache );
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

		$wikiPage = new WikiPage( $setup['title'] );
		$pageId   = $wikiPage->getTitle()->getArticleID();

		$this->testEnvironment->addConfiguration(
			'smwgAutoRefreshOnPurge',
			$setup['smwgAutoRefreshOnPurge']
		);

		$this->testEnvironment->addConfiguration(
			'smwgFactboxCacheRefreshOnPurge',
			$setup['smwgFactboxCacheRefreshOnPurge']
		);

		$this->testEnvironment->addConfiguration(
			'smwgQueryResultCacheRefreshOnPurge',
			$setup['smwgQueryResultCacheRefreshOnPurge']
		);

		$instance = new ArticlePurge();

		$cacheFactory = $this->applicationFactory->newCacheFactory();
		$factboxCacheKey = $cacheFactory->getFactboxCacheKey( $pageId );
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

		#0 Id = cache
		$provider[] = array(
			array(
				'title'  => $validIdTitle,
				'smwgAutoRefreshOnPurge'         => true,
				'smwgFactboxCacheRefreshOnPurge' => true,
				'smwgQueryResultCacheRefreshOnPurge' => false
			),
			array(
				'factboxPreProcess'      => false,
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => true,
				'factboxPostProcess'     => false,
			)
		);

		#1 Disabled setting
		$validIdTitle =  MockTitle::buildMock( 'Disabled' );

		$validIdTitle->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 9099 ) );

		$provider[] = array(
			array(
				'title'  => $validIdTitle,
				'smwgAutoRefreshOnPurge'         => false,
				'smwgFactboxCacheRefreshOnPurge' => false,
				'smwgQueryResultCacheRefreshOnPurge' => false
			),
			array(
				'factboxPreProcess'      => false,
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => false,
				'factboxPostProcess'     => false,
			)
		);

		// #2 No Id
		$nullIdTitle =  MockTitle::buildMock( 'NullId' );

		$nullIdTitle->expects( $this->atLeastOnce() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 0 ) );

		$provider[] = array(
			array(
				'title'  => $nullIdTitle,
				'smwgAutoRefreshOnPurge'         => true,
				'smwgFactboxCacheRefreshOnPurge' => true,
				'smwgQueryResultCacheRefreshOnPurge' => false
			),
			array(
				'factboxPreProcess'      => false,
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => false,
				'factboxPostProcess'     => false,
			)
		);

		#3 No Id
		$provider[] = array(
			array(
				'title'  => $nullIdTitle,
				'smwgAutoRefreshOnPurge'         => true,
				'smwgFactboxCacheRefreshOnPurge' => false,
				'smwgQueryResultCacheRefreshOnPurge' => false
			),
			array(
				'factboxPreProcess'      => false,
				'autorefreshPreProcess'  => false,
				'autorefreshPostProcess' => false,
				'factboxPostProcess'     => false,
			)
		);

		return $provider;
	}

}
