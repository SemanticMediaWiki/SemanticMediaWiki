<?php

namespace SMW\Tests;

use SMW\InMemoryPoolCache;

/**
 * @covers \SMW\InMemoryPoolCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   1.2
 *
 * @author mwjames
 */
class InMemoryPoolCacheTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\InMemoryPoolCache',
			new InMemoryPoolCache()
		);

		$this->assertInstanceOf(
			'\SMW\InMemoryPoolCache',
			InMemoryPoolCache::getInstance()
		);

		InMemoryPoolCache::getInstance()->clear();
	}

	public function testPoolCache() {

		$instance = new InMemoryPoolCache();

		$this->assertInstanceOf(
			'\Onoi\Cache\Cache',
			$instance->getPoolCacheFor( 'Foo' )
		);

		$instance->getPoolCacheFor( 'Foo' )->save( 'Bar', 42 );

		$this->assertEquals(
			42,
			$instance->getPoolCacheFor( 'Foo' )->fetch( 'Bar' )
		);

		$instance->resetPoolCacheFor( 'Foo' );

		$this->assertEmpty(
			$instance->getStats( 'Foo' )
		);
	}

}
