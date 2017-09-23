<?php

namespace SMW\Tests\MediaWiki\Api\LookupCache;

use SMW\MediaWiki\Api\Browse\LookupCache;

/**
 * @covers \SMW\MediaWiki\Api\Browse\LookupCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class LookupCacheTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			LookupCache::class,
			new LookupCache( $cache, $listLookup )
		);
	}

	public function testLookupWithoutCache() {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$cache->expects( $this->atLeastOnce() )
			->method( 'save' );

		$listLookup = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->atLeastOnce() )
			->method( 'lookup' )
			->will( $this->returnValue( [] ) );

		$instance = new LookupCache(
			$cache,
			$listLookup
		);

		$parameters = [];

		$instance->lookup( 'Foo', $parameters );
	}

	public function testLookupWithCache() {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->will( $this->returnValue( [] ) );

		$cache->expects( $this->never() )
			->method( 'save' );

		$listLookup = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\ListLookup' )
			->disableOriginalConstructor()
			->getMock();

		$listLookup->expects( $this->never() )
			->method( 'lookup' );

		$instance = new LookupCache(
			$cache,
			$listLookup
		);

		$parameters = [];

		$instance->lookup( 'Foo', $parameters );
	}

}
