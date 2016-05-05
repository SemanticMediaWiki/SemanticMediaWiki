<?php

namespace SMW\Tests;

use SMW\InMemoryPoolCache;

/**
 * @covers \SMW\InMemoryPoolCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.3
 *
 * @author mwjames
 */
class InMemoryPoolCacheTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		InMemoryPoolCache::getInstance()->clear();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$cacheFactory = $this->getMockBuilder( '\SMW\CacheFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\InMemoryPoolCache',
			new InMemoryPoolCache( $cacheFactory )
		);

		$this->assertInstanceOf(
			'\SMW\InMemoryPoolCache',
			InMemoryPoolCache::getInstance()
		);
	}

	public function testPoolCache() {

		$instance = InMemoryPoolCache::getInstance();

		$this->assertInstanceOf(
			'\Onoi\Cache\Cache',
			$instance->getPoolCacheFor( 'Foo' )
		);

		$instance->getPoolCacheFor( 'Foo' )->save( 'Bar', 42 );

		$this->assertEquals(
			42,
			$instance->getPoolCacheFor( 'Foo' )->fetch( 'Bar' )
		);

		$this->assertNotEmpty(
			$instance->getStats()
		);

		$this->assertInternalType(
			'string',
			$instance->getFormattedStats()
		);

		$instance->resetPoolCacheFor( 'Foo' );

		$this->assertEmpty(
			$instance->getStats()
		);
	}

}
