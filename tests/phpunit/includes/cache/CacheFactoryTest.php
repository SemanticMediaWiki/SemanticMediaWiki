<?php

namespace SMW\Tests\MediaWiki;

use SMW\Cache\CacheFactory;

/**
 * @covers \SMW\Cache\CacheFactory
 *
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
			'\SMW\Cache\CacheFactory',
			new CacheFactory()
		);
	}

	public function testGetMainCacheType() {

		$instance = new CacheFactory();

		$this->assertInternalType(
			'integer',
			$instance->getMainCacheType()
		);
	}

	public function testCanConstructFixedInMemoryCache() {

		$instance = new CacheFactory();

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newFixedInMemoryCache()
		);
	}

	public function testCanConstructCacheOptions() {

		$instance = new CacheFactory();

		$cacheOptions = $instance->newCacheOptions( array(
			'useCache' => true
		) );

		$this->assertTrue(
			$cacheOptions->useCache
		);
	}

	public function testCanConstructMediaWikiCompositeCache() {

		$instance = new CacheFactory();

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newMediaWikiCompositeCache( 'hash' )
		);

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->newMediaWikiCompositeCache( $instance->getMainCacheType() )
		);
	}

}
