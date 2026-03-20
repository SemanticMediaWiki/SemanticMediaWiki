<?php

namespace SMW\Tests\Unit;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\CacheFactory;
use SMW\InMemoryPoolCache;

/**
 * @covers \SMW\InMemoryPoolCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since  2.3
 *
 * @author mwjames
 */
class InMemoryPoolCacheTest extends TestCase {

	protected function tearDown(): void {
		InMemoryPoolCache::getInstance()->clear();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$cacheFactory = $this->getMockBuilder( CacheFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			InMemoryPoolCache::class,
			new InMemoryPoolCache( $cacheFactory )
		);

		$this->assertInstanceOf(
			InMemoryPoolCache::class,
			InMemoryPoolCache::getInstance()
		);
	}

	public function testPoolCache() {
		$instance = InMemoryPoolCache::getInstance();

		$this->assertInstanceOf(
			Cache::class,
			$instance->getPoolCacheById( 'Foo' )
		);

		$instance->getPoolCacheById( 'Foo' )->save( 'Bar', 42 );

		$this->assertEquals(
			42,
			$instance->getPoolCacheById( 'Foo' )->fetch( 'Bar' )
		);

		$instance->resetPoolCacheById( 'Foo' );
	}

	public function testGetStats() {
		$instance = InMemoryPoolCache::getInstance();

		$instance->getPoolCacheById( 'Foo' )->save( 'Bar', 42 );

		$this->assertNotEmpty(
			$instance->getStats()
		);

		$this->assertIsString(

			$instance->getStats( InMemoryPoolCache::FORMAT_PLAIN )
		);

		$this->assertStringContainsString(
			'ul',
			$instance->getStats( InMemoryPoolCache::FORMAT_HTML )
		);

		$this->assertIsString(

			$instance->getStats( InMemoryPoolCache::FORMAT_JSON )
		);

		$instance->resetPoolCacheById( 'Foo' );
	}

}
