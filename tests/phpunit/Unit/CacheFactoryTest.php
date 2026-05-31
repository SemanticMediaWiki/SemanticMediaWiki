<?php

namespace SMW\Tests\Unit;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\CacheFactory;
use SMW\MediaWiki\Hooks\ArticlePurge;
use SMW\Query\Cache\QueryResultStore;

/**
 * @covers \SMW\CacheFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class CacheFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CacheFactory::class,
			new CacheFactory( 'hash' )
		);
	}

	public function testGetMainCacheType() {
		$instance = new CacheFactory( 'hash' );

		$this->assertEquals(
			'hash',
			$instance->getMainCacheType()
		);

		$instance = new CacheFactory( CACHE_NONE );

		$this->assertEquals(
			CACHE_NONE,
			$instance->getMainCacheType()
		);
	}

	public function testGetCachePrefix() {
		$instance = new CacheFactory( 'hash' );

		$this->assertIsString(

			$instance->getCachePrefix()
		);
	}

	public function testGetPurgeCacheKey() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->willReturn( 42 );

		$instance = new CacheFactory( 'hash' );

		$this->assertIsString(

			$instance->getPurgeCacheKey( $title )
		);

		$this->assertSame(
			smwfCacheKey( 'smw:arc', 42 ),
			$instance->getPurgeCacheKey( $title )
		);

		$this->assertSame(
			smwfCacheKey( ArticlePurge::CACHE_NAMESPACE, 42 ),
			$instance->getPurgeCacheKey( $title )
		);
	}

	public function testCanConstructCacheOptions() {
		$instance = new CacheFactory( 'hash' );

		$cacheOptions = $instance->newCacheOptions( [
			'useCache' => true,
			'ttl' => 0
		] );

		$this->assertTrue(
			$cacheOptions->useCache
		);
	}

	public function testIncompleteCacheOptionsThrowsException() {
		$instance = new CacheFactory( 'hash' );

		$this->expectException( 'RuntimeException' );

		$cacheOptions = $instance->newCacheOptions( [
			'useCache' => true
		] );
	}

	public function testCanConstructBlobStore() {
		$instance = new CacheFactory( 'hash' );

		$this->assertInstanceOf(
			QueryResultStore::class,
			$instance->newBlobStore( 'foo', CACHE_NONE )
		);
	}

}
