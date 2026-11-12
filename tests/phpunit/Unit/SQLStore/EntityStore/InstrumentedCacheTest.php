<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\Cache\InMemoryLruCache;
use SMW\SQLStore\EntityStore\InstrumentedCache;
use Wikimedia\Stats\Sample;
use Wikimedia\Stats\StatsFactory;

/**
 * @covers \SMW\SQLStore\EntityStore\InstrumentedCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class InstrumentedCacheTest extends TestCase {

	private StatsFactory $statsFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->statsFactory = StatsFactory::newNull();
	}

	public function testReportsTheSizeUnderItsPool() {
		$this->newCache( 10 )->save( 'a', 1 );

		$this->assertSame(
			[ 'test' ],
			$this->statsFactory->getGauge( 'inmemory_cache_size' )->getSamples()[0]->getLabelValues()
		);
	}

	public function testSavingAnAlreadyCachedKeyReportsNoNewSize() {
		$cache = $this->newCache( 10 );

		$cache->save( 'a', 1 );
		$cache->save( 'a', 2 );

		$this->assertSame( [ 1.0 ], $this->reportedSizes() );
	}

	public function testSavingIntoAFullCacheReportsNoNewSize() {
		$cache = $this->newCache( 2 );

		$cache->save( 'a', 1 );
		$cache->save( 'b', 2 );
		$cache->save( 'c', 3 );

		$this->assertSame( [ 1.0, 2.0 ], $this->reportedSizes() );
	}

	public function testDeletingAKeyReportsTheSmallerSize() {
		$cache = $this->newCache( 10 );

		$cache->save( 'a', 1 );
		$cache->save( 'b', 2 );
		$cache->delete( 'a' );

		$this->assertSame( [ 1.0, 2.0, 1.0 ], $this->reportedSizes() );
	}

	public function testDeletingAnAbsentKeyReportsNoNewSize() {
		$cache = $this->newCache( 10 );

		$cache->save( 'a', 1 );
		$cache->delete( 'b' );

		$this->assertSame( [ 1.0 ], $this->reportedSizes() );
	}

	public function testKeepsCountedLookupsBufferedBelowTheLimit() {
		$this->fetchMissingKeys( InstrumentedCache::MAX_BUFFERED_SAMPLES - 1 );

		$this->assertSame( InstrumentedCache::MAX_BUFFERED_SAMPLES - 1, $this->bufferedMisses() );
	}

	public function testFlushesBufferedStatsOnceACounterReachesTheLimit() {
		$this->fetchMissingKeys( InstrumentedCache::MAX_BUFFERED_SAMPLES );

		$this->assertSame( 0, $this->bufferedMisses() );
	}

	public function testKeepsBufferedStatsWhenNotFlushingThem() {
		$this->fetchMissingKeys( InstrumentedCache::MAX_BUFFERED_SAMPLES, flushBufferedStats: false );

		$this->assertSame( InstrumentedCache::MAX_BUFFERED_SAMPLES, $this->bufferedMisses() );
	}

	public function testFlushesBufferedStatsOnceTheSizeGaugeReachesTheLimit() {
		$cache = $this->newCache( InstrumentedCache::MAX_BUFFERED_SAMPLES );

		for ( $i = 0; $i < InstrumentedCache::MAX_BUFFERED_SAMPLES; $i++ ) {
			$cache->save( "key$i", $i );
		}

		$this->assertSame( [], $this->reportedSizes() );
	}

	private function fetchMissingKeys( int $times, bool $flushBufferedStats = true ): void {
		$cache = $this->newCache( 10, $flushBufferedStats );

		for ( $i = 0; $i < $times; $i++ ) {
			$cache->fetch( 'missing' );
		}
	}

	private function bufferedMisses(): int {
		return $this->statsFactory->getCounter( 'inmemory_cache_misses_total' )->getSampleCount();
	}

	private function newCache( int $capacity, bool $flushBufferedStats = true ): InstrumentedCache {
		return new InstrumentedCache( new InMemoryLruCache( $capacity ), $this->statsFactory, 'test', $flushBufferedStats );
	}

	/**
	 * @return float[]
	 */
	private function reportedSizes(): array {
		return array_map(
			static fn ( Sample $sample ) => $sample->getValue(),
			$this->statsFactory->getGauge( 'inmemory_cache_size' )->getSamples()
		);
	}

}
