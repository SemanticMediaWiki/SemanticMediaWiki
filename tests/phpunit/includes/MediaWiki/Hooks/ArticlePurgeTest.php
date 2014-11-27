<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Utils\Mock\MockTitle;

use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\Settings;
use SMW\FactboxCache;
use SMW\ApplicationFactory;

use WikiPage;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticlePurge
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
class ArticlePurgeTest extends \PHPUnit_Framework_TestCase {

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

		ApplicationFactory::getInstance()->registerObject( 'Settings', Settings::newFromArray( array(
			'smwgCacheType'                  => 'hash',
			'smwgAutoRefreshOnPurge'         => $setup['smwgAutoRefreshOnPurge'],
			'smwgFactboxCacheRefreshOnPurge' => $setup['smwgFactboxCacheRefreshOnPurge']
		) ) );

		$instance = new ArticlePurge( $wikiPage );
		$cache = ApplicationFactory::getInstance()->getCache();

		$id = FactboxCache::newCacheId( $pageId );
	//	$cache->setKey( $id )->set( true );

		$this->assertEquals(
			$expected['autorefreshPreProcess'],
			$cache->setKey( $instance->newCacheId( $pageId ) )->get(),
			'Asserts the autorefresh cache status before processing'
		);

		// Travis 210.5, 305.3
		$travis = $cache->setKey( $id )->get();
		$travisText = json_encode( $travis );
		$this->assertEquals(
			$expected['factboxPreProcess'],
			$travis,
			"Asserts the factbox cache status before processing, {$travisText}"
		);

		$this->assertFalse(
			$cache->setKey( $instance->newCacheId( $pageId ) )->get(),
			'Asserts that before processing ...'
		);

		$result = $instance->process();

		// Post-process check
		$this->assertTrue(
			$result,
			'Asserts that process() always returns true'
		);

		$this->assertEquals(
			$expected['autorefreshPostProcess'],
			$cache->setKey( $instance->newCacheId( $pageId ) )->get(),
			'Asserts the autorefresh cache status after processing'
		);

		$this->assertEquals(
			$expected['factboxPostProcess'],
			$cache->setCacheEnabled( true )->setKey( $id )->get(),
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
				'smwgFactboxCacheRefreshOnPurge' => true
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
				'smwgFactboxCacheRefreshOnPurge' => false
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
				'smwgFactboxCacheRefreshOnPurge' => true
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
				'smwgFactboxCacheRefreshOnPurge' => false
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
