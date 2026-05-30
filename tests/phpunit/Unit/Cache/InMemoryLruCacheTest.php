<?php

namespace SMW\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use SMW\Cache\InMemoryLruCache;

/**
 * @covers \SMW\Cache\InMemoryLruCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class InMemoryLruCacheTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			InMemoryLruCache::class,
			new InMemoryLruCache()
		);
	}

	/**
	 * The former Onoi cache cast its size argument to int and tolerated a 0
	 * (or empty-string) size from config without erroring; the adapter keeps
	 * that tolerance over MapCacheLRU, which requires a positive capacity.
	 *
	 * @dataProvider degenerateSizeProvider
	 */
	public function testConstructsWithDegenerateOrStringSize( $size ) {
		$instance = new InMemoryLruCache( $size );

		$instance->save( 'k', 'v' );

		$this->assertInstanceOf( InMemoryLruCache::class, $instance );
	}

	public function degenerateSizeProvider() {
		return [
			'empty string' => [ '' ],
			'numeric string' => [ '1000' ],
			'zero' => [ 0 ],
			'negative' => [ -5 ],
		];
	}

	public function testSaveFetchContainsDelete() {
		$instance = new InMemoryLruCache();

		$this->assertFalse( $instance->contains( 'k' ) );
		$this->assertFalse( $instance->fetch( 'k' ) );

		$instance->save( 'k', 'v' );

		$this->assertTrue( $instance->contains( 'k' ) );
		$this->assertSame( 'v', $instance->fetch( 'k' ) );

		$this->assertTrue( $instance->delete( 'k' ) );

		$this->assertFalse( $instance->contains( 'k' ) );
		$this->assertFalse( $instance->fetch( 'k' ) );
	}

	public function testFetchReturnsFalseSentinelOnMiss() {
		$instance = new InMemoryLruCache();

		// The false sentinel is what the negative-cache consumers rely on.
		$this->assertFalse( $instance->fetch( 'absent' ) );
	}

	public function testDeleteReturnsFalseWhenAbsent() {
		$instance = new InMemoryLruCache();

		$this->assertFalse( $instance->delete( 'absent' ) );
	}

	public function testSaveAcceptsAndIgnoresTtlArgument() {
		$instance = new InMemoryLruCache();

		$instance->save( 'k', 'v', 3600 );

		$this->assertSame( 'v', $instance->fetch( 'k' ) );
	}

	public function testStoresFalsyValuesDistinctFromMiss() {
		$instance = new InMemoryLruCache();

		// A stored 0 is a hit; the value round-trips even though it is falsy.
		$instance->save( 'zero', 0 );

		$this->assertTrue( $instance->contains( 'zero' ) );
		$this->assertSame( 0, $instance->fetch( 'zero' ) );
	}

	public function testGetStatsShapeAndCounters() {
		$instance = new InMemoryLruCache( 10 );

		$instance->save( 'k', 'v' );
		$instance->fetch( 'k' );
		$instance->fetch( 'miss' );
		// Overwriting an existing key is still counted as an insert.
		$instance->save( 'k', 'v2' );
		$instance->delete( 'k' );
		// Deleting an absent key does not increment the delete counter.
		$instance->delete( 'k' );

		$this->assertSame(
			[
				'inserts' => 2,
				'deletes' => 1,
				'max' => 10,
				'count' => 0,
				'hits' => 1,
				'misses' => 1,
			],
			$instance->getStats()
		);
	}

	public function testEvictsLeastRecentlyUsedBeyondCapacity() {
		$instance = new InMemoryLruCache( 2 );

		$instance->save( 'a', '1' );
		$instance->save( 'b', '2' );
		$instance->save( 'c', '3' );

		// Capacity is 2, so the oldest entry is evicted and the count is capped.
		$this->assertFalse( $instance->contains( 'a' ) );
		$this->assertTrue( $instance->contains( 'b' ) );
		$this->assertTrue( $instance->contains( 'c' ) );
		$this->assertSame( 2, $instance->getStats()['count'] );
		$this->assertSame( 2, $instance->getStats()['max'] );
	}

}
