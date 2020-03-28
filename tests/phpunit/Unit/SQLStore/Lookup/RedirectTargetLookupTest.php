<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\DIWikiPage;
use SMW\SQLStore\Lookup\RedirectTargetLookup;

/**
 * @covers \SMW\SQLStore\Lookup\RedirectTargetLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class RedirectTargetLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $idCacheManager;
	private $cache;
	private $connection;

	protected function setUp() : void {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->connection ) );

		$this->idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RedirectTargetLookup::class,
			new RedirectTargetLookup( $this->store, $this->idCacheManager )
		);
	}

	public function testPrepareCache_FromHash() {

		$rows = [
			(object)[ 's_title' => 'Bar', 's_namespace' => 0, 'o_id' => 42 ]
		];

		$this->connection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $rows ) );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnValue( $this->cache ) );

		$this->cache->expects( $this->at( 0 ) )
			->method( 'save' )
			->with(
				$this->equalTo( 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6' ),
				$this->equalTo( 'Bar#0##' ) );

		$this->cache->expects( $this->at( 1 ) )
			->method( 'save' )
			->with(
				$this->equalTo( '7b6b944694382bfab461675f40a2bda7e71e68e3' ),
				$this->equalTo( 'Foo#0##' ) );

		$instance = new RedirectTargetLookup(
			$this->store,
			$this->idCacheManager
		);

		$instance->prepareCache( [ 42 => 'Foo#0##' ] );
	}

	public function testPrepareCache_FromInstance() {

		$rows = [
			(object)[ 's_title' => 'Bar', 's_namespace' => 0, 'o_id' => 42 ]
		];

		$this->connection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( $rows ) );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnValue( $this->cache ) );

		$this->cache->expects( $this->at( 0 ) )
			->method( 'save' )
			->with(
				$this->equalTo( 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6' ),
				$this->equalTo( 'Bar#0##' ) );

		$this->cache->expects( $this->at( 1 ) )
			->method( 'save' )
			->with(
				$this->equalTo( '7b6b944694382bfab461675f40a2bda7e71e68e3' ),
				$this->equalTo( 'Foo#0##' ) );

		$instance = new RedirectTargetLookup(
			$this->store,
			$this->idCacheManager
		);

		$instance->prepareCache( [ 42 => DIWikiPage::newFromText( 'Foo' ) ] );
	}

	public function testFindRedirectSource() {

		$flag = RedirectTargetLookup::CACHE_ONLY;
		$target = DIWikiPage::newFromText( 'Foo' );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( $this->equalTo( \SMW\SQLStore\EntityStore\IdCacheManager::REDIRECT_SOURCE ) )
			->will( $this->returnValue( $this->cache ) );

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->with( $this->equalTo( 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6' ) )
			->will( $this->returnValue( 'Bar#0##' ) );

		$instance = new RedirectTargetLookup(
			$this->store,
			$this->idCacheManager
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->findRedirectSource( $target, $flag )
		);
	}

}
