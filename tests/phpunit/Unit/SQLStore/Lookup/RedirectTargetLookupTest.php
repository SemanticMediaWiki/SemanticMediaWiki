<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\Cache\InMemoryLruCache;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\IdCacheManager;
use SMW\SQLStore\Lookup\RedirectTargetLookup;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

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

	use MockSelectQueryBuilderTrait;

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

		// A real in-process cache rather than a mock: the methods under test
		// round-trip through it, so behaviour is asserted on the cached state.
		$this->cache = new InMemoryLruCache();
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
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( $rows ) );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$instance = new RedirectTargetLookup(
			$this->store,
			$this->idCacheManager
		);

		$instance->prepareCache( [ 42 => 'Foo#0##' ] );

		// Two saves: target->source and source->target, both landing in the
		// shared cache returned by IdCacheManager::get().
		$this->assertSame( 2, $this->cache->getStats()['count'] );
		$this->assertSame(
			'Bar#0##',
			$this->cache->fetch( sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ) )
		);
		$this->assertSame(
			'Foo#0##',
			$this->cache->fetch( sha1( json_encode( [ 'Bar', 0, '', '' ] ), true ) )
		);
	}

	public function testPrepareCache_FromInstance() {
		$rows = [
			(object)[ 's_title' => 'Bar', 's_namespace' => 0, 'o_id' => 42 ]
		];

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( $rows ) );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$instance = new RedirectTargetLookup(
			$this->store,
			$this->idCacheManager
		);

		$instance->prepareCache( [ 42 => WikiPage::newFromText( 'Foo' ) ] );

		// Two saves: target->source and source->target, both landing in the
		// shared cache returned by IdCacheManager::get().
		$this->assertSame( 2, $this->cache->getStats()['count'] );
		$this->assertSame(
			'Bar#0##',
			$this->cache->fetch( sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ) )
		);
		$this->assertSame(
			'Foo#0##',
			$this->cache->fetch( sha1( json_encode( [ 'Bar', 0, '', '' ] ), true ) )
		);
	}

	public function testFindRedirectSource() {
		$flag = RedirectTargetLookup::CACHE_ONLY;
		$target = WikiPage::newFromText( 'Foo' );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->with( IdCacheManager::REDIRECT_SOURCE )
			->willReturn( $this->cache );

		// A pre-populated cache entry drives the CACHE_ONLY hit path.
		$this->cache->save( sha1( json_encode( [ 'Foo', 0, '', '' ] ), true ), 'Bar#0##' );

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
