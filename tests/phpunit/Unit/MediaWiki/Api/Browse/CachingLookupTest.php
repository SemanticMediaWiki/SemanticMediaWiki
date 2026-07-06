<?php

namespace SMW\Tests\Unit\MediaWiki\Api\Browse;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Browse\CachingLookup;
use SMW\MediaWiki\Api\Browse\Lookup;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * @covers \SMW\MediaWiki\Api\Browse\CachingLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CachingLookupTest extends TestCase {

	public function testCanConstruct() {
		$cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMock();

		$lookup = $this->getMockBuilder( Lookup::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			CachingLookup::class,
			new CachingLookup( $cache, $lookup )
		);
	}

	public function testLookupWithoutCache() {
		$cacheTTL = 42;

		$cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->atLeastOnce() )
			->method( 'get' )
			->willReturn( false );

		$cache->expects( $this->atLeastOnce() )
			->method( 'set' )
			->with(
				$this->anything(),
				$this->anything(),
				$cacheTTL );

		$lookup = $this->getMockBuilder( Lookup::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getVersion', 'lookup' ] )
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
		$cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->atLeastOnce() )
			->method( 'get' )
			->willReturn( [] );

		$cache->expects( $this->never() )
			->method( 'set' );

		$lookup = $this->getMockBuilder( Lookup::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getVersion', 'lookup' ] )
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
		$cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->never() )
			->method( 'get' );

		$cache->expects( $this->never() )
			->method( 'set' );

		$lookup = $this->getMockBuilder( Lookup::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getVersion', 'lookup' ] )
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
