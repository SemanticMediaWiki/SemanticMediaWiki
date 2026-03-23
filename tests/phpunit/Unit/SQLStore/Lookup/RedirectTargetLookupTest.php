<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\Lookup\RedirectTargetLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class RedirectTargetLookupTest extends TestCase {

	private $store;
	private $idCacheManager;
	private $cache;
	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->idCacheManager = $this->getMockBuilder( IdCacheManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( Cache::class )
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
			->willReturn( $rows );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$this->cache->expects( $this->exactly( 2 ) )
			->method( 'save' )
			->willReturnCallback( function ( $key, $value ) {
				static $calls = [];
				$calls[] = [ $key, $value ];
				if ( count( $calls ) === 1 ) {
					$this->assertEquals( 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6', $key );
					$this->assertEquals( 'Bar#0##', $value );
				} elseif ( count( $calls ) === 2 ) {
					$this->assertEquals( '7b6b944694382bfab461675f40a2bda7e71e68e3', $key );
					$this->assertEquals( 'Foo#0##', $value );
				}
			} );

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
			->willReturn( $rows );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$this->cache->expects( $this->exactly( 2 ) )
			->method( 'save' )
			->willReturnCallback( function ( $key, $value ) {
				static $calls = [];
				$calls[] = [ $key, $value ];
				if ( count( $calls ) === 1 ) {
					$this->assertEquals( 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6', $key );
					$this->assertEquals( 'Bar#0##', $value );
				} elseif ( count( $calls ) === 2 ) {
					$this->assertEquals( '7b6b944694382bfab461675f40a2bda7e71e68e3', $key );
					$this->assertEquals( 'Foo#0##', $value );
				}
			} );

		$instance = new RedirectTargetLookup(
			$this->store,
			$this->idCacheManager
		);

		$instance->prepareCache( [ 42 => WikiPage::newFromText( 'Foo' ) ] );
	}

	public function testFindRedirectSource() {
		$flag = RedirectTargetLookup::CACHE_ONLY;
		$target = WikiPage::newFromText( 'Foo' );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( IdCacheManager::REDIRECT_SOURCE )
			->willReturn( $this->cache );

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->with( 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6' )
			->willReturn( 'Bar#0##' );

		$instance = new RedirectTargetLookup(
			$this->store,
			$this->idCacheManager
		);

		$this->assertInstanceOf(
			WikiPage::class,
			$instance->findRedirectSource( $target, $flag )
		);
	}

}
