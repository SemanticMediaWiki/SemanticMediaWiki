<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use Onoi\Cache\Cache;
use Onoi\Cache\FixedInMemoryLruCache;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\SQLStore\EntityStore\IdCacheManager;

/**
 * @covers \SMW\SQLStore\EntityStore\IdCacheManager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.0
 *
 * @author mwjames
 */
class IdCacheManagerTest extends TestCase {

	private $caches;

	protected function setUp(): void {
		$this->caches = [
			'entity.id' => new FixedInMemoryLruCache(),
			'entity.sort' => new FixedInMemoryLruCache(),
			'entity.lookup' => new FixedInMemoryLruCache(),
			'propertytable.hash' => new FixedInMemoryLruCache()
		];
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			IdCacheManager::class,
			new IdCacheManager( $this->caches )
		);
	}

	public function testComputeSha1() {
		$result = IdCacheManager::computeSha1( [] );

		$this->assertIsString( $result );
		$this->assertSame( 20, strlen( $result ), 'SHA-1 raw binary should be 20 bytes' );
	}

	public function testGet() {
		$instance = new IdCacheManager( $this->caches );

		$this->assertInstanceOf(
			FixedInMemoryLruCache::class,
			$instance->get( 'entity.sort' )
		);
	}

	public function testGetThrowsException() {
		$instance = new IdCacheManager( $this->caches );

		$this->expectException( '\RuntimeException' );
		$instance->get( 'foo' );
	}

	public function testGetId() {
		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', 42, 'bar' );

		$this->assertEquals(
			42,
			$instance->getId( new WikiPage( 'foo', NS_MAIN ) )
		);

		$this->assertEquals(
			42,
			$instance->getId( [ 'foo', 0, '', '' ] )
		);

		$this->assertFalse(
						$instance->getId( [ 'foo', '0', '', '' ] )
		);

		$this->assertEquals(
			42,
			$instance->getId( $instance->computeSha1( [ 'foo', 0, '', '' ] ) )
		);
	}

	public function testGetSort() {
		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', 42, 'bar' );

		$this->assertEquals(
			'bar',
			$instance->getSort( $instance->computeSha1( [ 'foo', 0, '', '' ] ) )
		);

		$this->assertEquals(
			'bar',
			$instance->getSort( [ 'foo', 0, '', '' ] )
		);
	}

	public function testDeleteCache() {
		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', '42', 'bar' );

		$this->assertEquals(
			42,
			$instance->getId( [ 'foo', 0, '', '' ] )
		);

		$instance->deleteCache( 'foo', 0, '', '' );

		$this->assertFalse(
						$instance->getId( [ 'foo', '0', '', '' ] )
		);

		$this->assertFalse(
						$instance->getSort( [ 'foo', 0, '', '' ] )
		);
	}

	public function testHasCache() {
		$instance = new IdCacheManager( $this->caches );

		$instance->setCache( 'foo', 0, '', '', '42', 'bar' );

		$this->assertFalse(
						$instance->hasCache( [ 'foo', 0, '', '' ] )
		);

		$this->assertTrue(
						$instance->hasCache( $instance->computeSha1( [ 'foo', 0, '', '' ] ) )
		);
	}

	public function testDeleteCacheById() {
		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'delete' )
			->with( IdCacheManager::computeSha1( [ 'foo', 0, '', '' ] ) );

		$this->caches['entity.id'] = $cache;

		$instance = new IdCacheManager( $this->caches );
		$instance->setCache( 'foo', 0, '', '', '42', 'bar' );

		$instance->deleteCacheById( 42 );
	}

	public function testSetCacheOnTitleWithSpace_ThrowsException() {
		$instance = new IdCacheManager( $this->caches );

		$this->expectException( '\RuntimeException' );
		$instance->setCache( 'foo bar', '', '', '', '', '' );
	}

	public function testSetCacheOnTitleAsArray_ThrowsException() {
		$instance = new IdCacheManager( $this->caches );

		$this->expectException( '\RuntimeException' );
		$instance->setCache( [ 'foo bar' ], '', '', '', '', '' );
	}

}
