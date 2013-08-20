<?php

namespace SMW\Test;

use SMW\SimpleDependencyBuilder;
use SMW\ArticlePurge;
use SMW\CacheHandler;

use WikiPage;

/**
 * Tests for the ArticlePurge class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ArticlePurge
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ArticlePurgeTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ArticlePurge';
	}

	/**
	 * Helper method that returns a ArticlePurge object
	 *
	 * @since 1.9
	 *
	 * @return ArticlePurge
	 */
	private function getInstance( WikiPage $wikiPage = null ) {
		$wikiPage = $wikiPage === null ? $this->newMockObject()->getMockWikiPage() : $wikiPage;
		return new ArticlePurge( $wikiPage );
	}

	/**
	 * @test ArticlePurge::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test ArticlePurge::process
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testProcess( $setup, $expected ) {

		$wikiPage = new WikiPage( $setup['title'] );
		$pageId   = $wikiPage->getTitle()->getArticleID();

		$settings = $this->newSettings( array(
			'smwgCacheType'          => 'hash',
			'smwgAutoRefreshOnPurge' => $setup['smwgAutoRefreshOnPurge']
		) );

		$dependencyBuilder = new SimpleDependencyBuilder();
		$dependencyBuilder->getContainer()->registerObject( 'Settings', $settings );
		$dependencyBuilder->getContainer()->registerObject(
			'CacheHandler',
			CacheHandler::newFromId( $settings->get( 'smwgCacheType' ) )
		);

		$instance = $this->getInstance( $wikiPage );
		$instance->setDependencyBuilder( $dependencyBuilder );
		$cache = $dependencyBuilder->newObject( 'CacheHandler' );

		$this->assertFalse( $cache->key( 'autorefresh', $pageId )->get() );
		$result = $instance->process();

		$this->assertTrue( $result );
		$this->assertEquals( $expected['result'], $cache->key( 'autorefresh', $pageId )->get() );

	}

	/**
	 * @return array
	 */
	public function titleDataProvider() {

		$provider = array();

		// #0 Id = cache
		$provider[] = array(
			array(
				'title'  => $this->newMockObject()->getMockTitle(),
				'smwgAutoRefreshOnPurge' => true
			),
			array(
				'result' => true
			)
		);

		// #1 Disabled setting
		$provider[] = array(
			array(
				'title'  => $this->newMockObject()->getMockTitle(),
				'smwgAutoRefreshOnPurge' => false
			),
			array(
				'result' => false
			)
		);

		// #2 No Id
		$title = $this->newMockObject( array(
			'getArticleID' => 0
		) )->getMockTitle();

		$provider[] = array(
			array(
				'title'  => $title,
				'smwgAutoRefreshOnPurge' => true
			),
			array(
				'result' => false
			)
		);

		return $provider;
	}

}
