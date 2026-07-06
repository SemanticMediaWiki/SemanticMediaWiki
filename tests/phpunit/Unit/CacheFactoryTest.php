<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\CacheFactory;
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

	public function testCanConstructQueryResultStore() {
		$instance = new CacheFactory( 'hash' );

		$this->assertInstanceOf(
			QueryResultStore::class,
			$instance->newQueryResultStore( 'foo', CACHE_NONE )
		);
	}

}
