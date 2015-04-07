<?php

namespace SMW\Tests\SQLStore\ListLookup;

use SMW\SQLStore\ListLookup\CachedListLookup;

/**
 * @covers \SMW\SQLStore\ListLookup\CachedListLookup
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class CachedListLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\ListLookup\CachedListLookup',
			new CachedListLookup( $listLookup, $cache, new \stdClass )
		);
	}

	public function testfetchListFromCache() {

		$expectedCachedItem = array(
			'time' => 42,
			'list' => serialize( array( 'Foo' ) )
		);

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->atLeastOnce() )
			->method( 'getLookupIdentifier' )
			->will( $this->returnValue( 'Bar' ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'contains' )
			->with(	$this->stringContains( 'cacheprefix-foobar:smw:listlookup-cache:' ) )
			->will( $this->returnValue( true ) );

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( $expectedCachedItem ) );

		$cacheOptions = new \stdClass;
		$cacheOptions->useCache = true;

		$instance = new CachedListLookup( $listLookup, $cache, $cacheOptions );
		$instance->setCachePrefix( 'cacheprefix-foobar' );

		$this->assertEquals(
			array( 'Foo' ),
			$instance->fetchList()
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

		$listLookup = $this->getMockBuilder( '\SMW\SQLStore\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->once() )
			->method( 'fetchList' )
			->will( $this->returnValue( array( 'Foo' ) ) );

		$listLookup->expects( $this->once() )
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

		$instance = new CachedListLookup( $listLookup, $cache, $cacheOptions );

		$this->assertEquals(
			array( 'Foo' ),
			$instance->fetchList()
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
