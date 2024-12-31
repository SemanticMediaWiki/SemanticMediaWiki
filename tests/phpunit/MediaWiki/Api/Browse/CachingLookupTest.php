<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\MediaWiki\Api\Browse\CachingLookup;

/**
 * @covers \SMW\MediaWiki\Api\Browse\CachingLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CachingLookupTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$lookup = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\Lookup' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			CachingLookup::class,
			new CachingLookup( $cache, $lookup )
		);
	}

	public function testLookupWithoutCache() {
		$cacheTTL = 42;

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->willReturn( false );

		$cache->expects( $this->atLeastOnce() )
			->method( 'save' )
			->with(
				$this->anything(),
				$this->anything(),
				$cacheTTL );

		$lookup = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\Lookup' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getVersion', 'lookup' ] )
			->getMockForAbstractClass();

		$lookup->expects( $this->atLeastOnce() )
			->method( 'lookup' )
			->willReturn( [] );

		$instance = new CachingLookup(
			$cache,
			$lookup
		);

		$instance->setCacheTTL( $cacheTTL );

		$parameters = [];

		$instance->lookup( $parameters );
	}

	public function testLookupWithCache() {
		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->willReturn( [] );

		$cache->expects( $this->never() )
			->method( 'save' );

		$lookup = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\Lookup' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getVersion', 'lookup' ] )
			->getMockForAbstractClass();

		$lookup->expects( $this->never() )
			->method( 'lookup' );

		$instance = new CachingLookup(
			$cache,
			$lookup
		);

		$parameters = [];

		$instance->lookup( $parameters );
	}

	public function testLookupWithCacheBeingDisabled() {
		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->never() )
			->method( 'fetch' );

		$cache->expects( $this->never() )
			->method( 'save' );

		$lookup = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\Lookup' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getVersion', 'lookup' ] )
			->getMockForAbstractClass();

		$lookup->expects( $this->atLeastOnce() )
			->method( 'lookup' )
			->willReturn( [] );

		$instance = new CachingLookup(
			$cache,
			$lookup
		);

		$instance->setCacheTTL( false );

		$parameters = [];

		$instance->lookup( $parameters );
	}

}
