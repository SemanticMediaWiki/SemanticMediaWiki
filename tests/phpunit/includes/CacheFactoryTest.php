<?php

namespace SMW\Tests;

use SMW\CacheFactory;
use SMW\ApplicationFactory;

/**
 * @covers \SMW\CacheFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CacheFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\CacheFactory',
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

		$this->assertInternalType(
			'string',
			$instance->getCachePrefix()
		);
	}

	public function testCanConstructCacheOptions() {

		$instance = new CacheFactory( 'hash' );

		$cacheOptions = $instance->newCacheOptions( array(
			'useCache' => true,
			'ttl' => 0
		) );

		$this->assertTrue(
			$cacheOptions->useCache
		);
	}

	public function testIncompleteCacheOptionsThrowsException() {

		$instance = new CacheFactory( 'hash' );

		$this->setExpectedException( 'RuntimeException' );

		$cacheOptions = $instance->newCacheOptions( array(
			'useCache' => true
		) );
	}

	public function testCanConstructFixedInMemoryCache() {

		$instance = new CacheFactory( 'hash' );

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newFixedInMemoryCache()
		);
	}

	public function testCanConstructNullCache() {

		$instance = new CacheFactory( 'hash' );

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newNullCache()
		);
	}

	public function testCanConstructMediaWikiCompositeCache() {

		$instance = new CacheFactory( 'hash' );

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newMediaWikiCompositeCache( CACHE_NONE )
		);

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newMediaWikiCompositeCache( $instance->getMainCacheType() )
		);
	}

}
