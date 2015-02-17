<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\ListLookupCache;

/**
 * @covers \SMW\SQLStore\ListLookupCache
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class ListLookupCacheTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$simpleListLookup = $this->getMockBuilder( '\SMW\SQLStore\SimpleListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\ListLookupCache',
			new ListLookupCache( $simpleListLookup, $cache, new \stdClass )
		);
	}

	public function testFetchResultListFromCache() {

		$expectedCachedItem = array(
			'time' => 42,
			'list' => serialize( array( 'Foo' ) )
		);

		$simpleListLookup = $this->getMockBuilder( '\SMW\SQLStore\SimpleListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$simpleListLookup->expects( $this->atLeastOnce() )
			->method( 'getLookupIdentifier' )
			->will( $this->returnValue( 'Bar' ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'contains' )
			->with(	$this->stringContains( 'cacheprefix-foobar:' ) )
			->will( $this->returnValue( true ) );

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $expectedCachedItem ) );

		$cacheOptions = new \stdClass;
		$cacheOptions->useCache = true;

		$instance = new ListLookupCache( $simpleListLookup, $cache, $cacheOptions );
		$instance->setCachePrefix( 'cacheprefix-foobar' );

		$this->assertEquals(
			array( 'Foo' ),
			$instance->fetchResultList()
		);

		$this->assertEquals(
			42,
			$instance->getTimestamp()
		);

		$this->assertEquals(
			'Bar',
			$instance->getLookupIdentifier()
		);

		$this->assertTrue(
			$instance->isCached()
		);
	}

	public function testRetrieveResultListFromInjectedListLookup() {

		$expectedCacheItem = array(
			'time' => 42,
			'list' => serialize( array( 'Foo' ) )
		);

		$simpleListLookup = $this->getMockBuilder( '\SMW\SQLStore\SimpleListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$simpleListLookup->expects( $this->once() )
			->method( 'fetchResultList' )
			->will( $this->returnValue( array( 'Foo' ) ) );

		$simpleListLookup->expects( $this->once() )
			->method( 'getTimestamp' )
			->will( $this->returnValue( 42 ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( 'lookup-cache' ),
				$this->anything( $expectedCacheItem ),
				$this->equalTo( 1001 ) );

		$cacheOptions = new \stdClass;
		$cacheOptions->useCache = false;
		$cacheOptions->ttl = 1001;

		$instance = new ListLookupCache( $simpleListLookup, $cache, $cacheOptions );

		$this->assertEquals(
			array( 'Foo' ),
			$instance->fetchResultList()
		);

		$this->assertEquals(
			42,
			$instance->getTimestamp()
		);

		$this->assertFalse(
			$instance->isCached()
		);
	}

}
