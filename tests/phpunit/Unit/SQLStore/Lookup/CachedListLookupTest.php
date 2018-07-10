<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\CachedListLookup;

/**
 * @covers \SMW\SQLStore\Lookup\CachedListLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class CachedListLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\CachedListLookup',
			new CachedListLookup( $listLookup, $cache, new \stdClass )
		);
	}

	public function testfetchListFromCache() {

		$expectedCachedItem = [
			'time' => 42,
			'list' => [ 'Foo' ]
		];

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->atLeastOnce() )
			->method( 'getHash' )
			->will( $this->returnValue( 'Bar#123' ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'contains' )
			->with(	$this->stringContains( 'cacheprefix-foobar:smw:store:lookup:' ) )
			->will( $this->returnValue( true ) );

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( serialize( $expectedCachedItem ) ) );

		$cacheOptions = new \stdClass;
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

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->once() )
			->method( 'fetchList' )
			->will( $this->returnValue( [ 'Foo' ] ) );

		$listLookup->expects( $this->once() )
			->method( 'getTimestamp' )
			->will( $this->returnValue( 42 ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->at( 1 ) )
			->method( 'save' )
			->with(
				$this->stringContains( 'smw:store:lookup' ),
				$this->anything( serialize( $expectedCacheItem ) ),
				$this->equalTo( 1001 ) );

		$cacheOptions = new \stdClass;
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

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->once() )
			->method( 'getHash' )
			->will( $this->returnValue( 'Foo#123' ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( serialize( [ 'smw:store:lookup:6283479db90b04ad3a6db333a3c89766' => true ] ) ) );

		$cache->expects( $this->atLeastOnce() )
			->method( 'delete' )
			->with(
				$this->stringContains( 'smw:store:lookup' ) );

		$cacheOptions = new \stdClass;

		$instance = new CachedListLookup( $listLookup, $cache, $cacheOptions );
		$instance->deleteCache();
	}

}
