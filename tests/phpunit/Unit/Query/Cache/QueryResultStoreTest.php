<?php

namespace SMW\Tests\Unit\Query\Cache;

use MapCacheLRU;
use PHPUnit\Framework\TestCase;
use SMW\Query\Cache\QueryResultContainer;
use SMW\Query\Cache\QueryResultStore;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * @covers \SMW\Query\Cache\QueryResultStore
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class QueryResultStoreTest extends TestCase {

	private function newStore( ?HashBagOStuff $cache = null ): QueryResultStore {
		$store = new QueryResultStore( 'smw:query:store', $cache ?? new HashBagOStuff(), new MapCacheLRU( 500 ) );
		$store->setNamespacePrefix( 'mw' );

		return $store;
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryResultStore::class,
			$this->newStore()
		);
	}

	public function testReadMissReturnsEmptyContainer() {
		$container = $this->newStore()->read( 'absent' );

		$this->assertInstanceOf( QueryResultContainer::class, $container );
		$this->assertFalse( $container->get( 'results' ) );
	}

	public function testSaveReadRoundTrip() {
		$store = $this->newStore();

		$container = $store->read( 'q1' );
		$container->set( 'results', [ 'Foo#0' ] );
		$container->set( 'count', 1 );
		$store->save( $container );

		$read = $store->read( 'q1' );

		$this->assertSame( [ 'Foo#0' ], $read->get( 'results' ) );
		$this->assertSame( 1, $read->get( 'count' ) );
	}

	public function testReadFromBackendUnserializes() {
		$cache = new HashBagOStuff();

		// Writer store saves; reader store shares the backend but has its own
		// (empty) fast tier, so the read goes through the serialize round-trip.
		$writer = $this->newStore( $cache );
		$container = $writer->read( 'q1' );
		$container->set( 'results', [ 'Bar#0' ] );
		$writer->save( $container );

		$reader = $this->newStore( $cache );

		$this->assertSame( [ 'Bar#0' ], $reader->read( 'q1' )->get( 'results' ) );
	}

	public function testExists() {
		$store = $this->newStore();

		$this->assertFalse( $store->exists( 'q1' ) );

		$container = $store->read( 'q1' );
		$container->set( 'results', [ 'Foo#0' ] );
		$store->save( $container );

		$this->assertTrue( $store->exists( 'q1' ) );
	}

	public function testKeyFormatIsFrozen() {
		$cache = new HashBagOStuff();
		$store = $this->newStore( $cache );

		$container = $store->read( 'abc' );
		$container->set( 'results', [ 'Foo#0' ] );
		$store->save( $container );

		// {namespacePrefix}:{namespace}:{id}
		$this->assertNotFalse(
			$cache->get( 'mw:smw:query:store:abc' ),
			'the durable entry must be stored under the frozen key format'
		);
	}

	public function testDeleteCascadesLinkedList() {
		$store = $this->newStore();

		// An embedded query 'dep' is linked to its subject container 'main'.
		$main = $store->read( 'main' );
		$main->set( 'results', [ 'M' ] );
		$main->addToLinkedList( 'dep' );
		$store->save( $main );

		$dep = $store->read( 'dep' );
		$dep->set( 'results', [ 'D' ] );
		$store->save( $dep );

		$this->assertTrue( $store->exists( 'main' ) );
		$this->assertTrue( $store->exists( 'dep' ) );

		$store->delete( 'main' );

		// Both the anchor and every linked entry are purged.
		$this->assertFalse( $store->exists( 'main' ) );
		$this->assertFalse( $store->exists( 'dep' ) );
	}

	public function testDeleteEvictsLinkedEntryFromFastTier() {
		// One store instance keeps its request-scoped fast tier across the calls.
		$store = $this->newStore();

		$main = $store->read( 'main' );
		$main->addToLinkedList( 'dep' );
		$store->save( $main );

		$dep = $store->read( 'dep' );
		$dep->set( 'results', [ 'D' ] );
		$store->save( $dep );

		// Promote 'dep' into the fast tier, mirroring an embedded query that was
		// already rendered earlier in the same request.
		$this->assertSame( [ 'D' ], $store->read( 'dep' )->get( 'results' ) );

		// Deleting the anchor must cascade-evict 'dep' from the fast tier too,
		// not only the durable backend, or this later read returns stale data.
		$store->delete( 'main' );

		$this->assertFalse(
			$store->read( 'dep' )->get( 'results' ),
			'a linked entry must be evicted from the fast tier on cascade delete'
		);
	}

	public function testCanUseReflectsUsageState() {
		$store = $this->newStore();
		$this->assertTrue( $store->canUse() );

		$store->setUsageState( false );
		$this->assertFalse( $store->canUse() );
	}

}
