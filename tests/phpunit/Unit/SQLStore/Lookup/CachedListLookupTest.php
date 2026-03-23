<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\SQLStore\Lookup\CachedListLookup;
use SMW\SQLStore\Lookup\ListLookup;
use stdClass;

/**
 * @covers \SMW\SQLStore\Lookup\CachedListLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   2.2
 *
 * @author mwjames
 */
class CachedListLookupTest extends TestCase {

	public function testCanConstruct() {
		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			CachedListLookup::class,
			new CachedListLookup( $listLookup, $cache, new stdClass )
		);
	}

	public function testfetchListFromCache() {
		$expectedCachedItem = [
			'time' => 42,
			'list' => [ 'Foo' ]
		];

		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->atLeastOnce() )
			->method( 'getHash' )
			->willReturn( 'Bar#123' );

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'contains' )
			->with(	$this->stringContains( 'cacheprefix-foobar:smw:store:lookup:' ) )
			->willReturn( true );

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( serialize( $expectedCachedItem ) );

		$cacheOptions = new stdClass;
		$cacheOptions->useCache = true;

		$instance = new CachedListLookup( $listLookup, $cache, $cacheOptions );
		$instance->setCachePrefix( 'cacheprefix-foobar' );

		$this->assertEquals(
			[ 'Foo' ],
			$instance->fetchList()
		);

		$this->assertEquals(
			42,
			$instance->getTimestamp()
		);

		$this->assertEquals(
			'Bar#123',
			$instance->getHash()
		);

		$this->assertTrue(
			$instance->isFromCache()
		);
	}

	public function testRetrieveResultListFromInjectedListLookup() {
		$expectedCacheItem = [
			'time' => 42,
			'list' => [ 'Foo' ]
		];

		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->once() )
			->method( 'fetchList' )
			->willReturn( [ 'Foo' ] );

		$listLookup->expects( $this->atLeastOnce() )
			->method( 'getTimestamp' )
			->willReturn( 42 );

		$listLookup->expects( $this->any() )
			->method( 'getHash' )
			->willReturn( 'Foo#123' );

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->any() )
			->method( 'fetch' )
			->willReturn( false );

		$cache->expects( $this->atLeastOnce() )
			->method( 'save' )
			->with(
				$this->stringContains( 'smw:store:lookup' ),
				$this->anything(),
				1001 );

		$cacheOptions = new stdClass;
		$cacheOptions->useCache = false;
		$cacheOptions->ttl = 1001;

		$instance = new CachedListLookup( $listLookup, $cache, $cacheOptions );

		$this->assertEquals(
			[ 'Foo' ],
			$instance->fetchList()
		);

		$this->assertEquals(
			42,
			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isFromCache()
		);
	}

	public function testDeleteCache() {
		$listLookup = $this->getMockBuilder( ListLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->once() )
			->method( 'getHash' )
			->willReturn( 'Foo#123' );

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( serialize( [ 'smw:store:lookup:6283479db90b04ad3a6db333a3c89766' => true ] ) );

		$cache->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with(
				$this->stringContains( 'smw:store:lookup' ) );

		$cacheOptions = new stdClass;

		$instance = new CachedListLookup( $listLookup, $cache, $cacheOptions );
		$instance->deleteCache();
	}

}
