<?php

namespace SMW\Test;

use SMW\ArticlePurge;
use SMW\ExtensionContext;

use WikiPage;

/**
 * @covers \SMW\ArticlePurge
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ArticlePurgeTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ArticlePurge';
	}

	/**
	 * @since 1.9
	 *
	 * @return ArticlePurge
	 */
	private function newInstance( WikiPage $wikiPage = null, $settings = array() ) {

		if ( $wikiPage === null ) {
			$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage' );
		}

		$context = new ExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $this->newSettings( $settings ) );

		$instance = new ArticlePurge( $wikiPage );
		$instance->invokeContext( $context );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcess( $setup, $expected ) {

		$wikiPage = new WikiPage( $setup['title'] );
		$pageId   = $wikiPage->getTitle()->getArticleID();

		$settings = array(
			'smwgCacheType'                  => 'hash',
			'smwgAutoRefreshOnPurge'         => $setup['smwgAutoRefreshOnPurge'],
			'smwgFactboxCacheRefreshOnPurge' => $setup['smwgFactboxCacheRefreshOnPurge']
		);

		$instance = $this->newInstance( $wikiPage, $settings );
		$cache = $instance->withContext()->getDependencyBuilder()->newObject( 'CacheHandler' );

		$id = \SMW\FactboxCache::newCacheId( $pageId );
	//	$cache->setKey( $id )->set( true );

		// Pre-process check
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

	/**
	 * @return array
	 */
	public function titleDataProvider() {

		$provider = array();

		// #0 Id = cache
		$provider[] = array(
			array(
				'title'  => $this->newMockBuilder()->newObject( 'Title' ),
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

		// #1 Disabled setting
		$provider[] = array(
			array(
				'title'  => $this->newMockBuilder()->newObject( 'Title' ),
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
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getArticleID' => 0
		) );

		$provider[] = array(
			array(
				'title'  => $title,
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

		// #3 No Id
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getArticleID' => 0
		) );

		$provider[] = array(
			array(
				'title'  => $title,
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
