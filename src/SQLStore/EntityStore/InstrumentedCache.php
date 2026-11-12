<?php

namespace SMW\SQLStore\EntityStore;

use SMW\Cache\InMemoryLruCache;
use Wikimedia\Stats\Metrics\MetricInterface;
use Wikimedia\Stats\Metrics\NullMetric;
use Wikimedia\Stats\StatsFactory;

/**
 * Decorator that emits per-event hit/miss counters, and a current-size gauge
 * whenever the size changes, to a `StatsFactory` for an underlying in-process
 * LRU cache. Used to instrument the request-scoped pools backing entity ID
 * lookups so operators can see cache effectiveness in their existing metrics
 * tooling. With `$flushBufferedStats`, it flushes MediaWiki's buffered stats,
 * which sends those of every component, once a metric holds
 * `MAX_BUFFERED_SAMPLES` samples.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class InstrumentedCache {

	/**
	 * MediaWiki keeps each metric sample in memory until its stats are flushed,
	 * which a long-running process such as a job runner may never do.
	 */
	public const MAX_BUFFERED_SAMPLES = 10000;

	private ?int $reportedSize = null;

	public function __construct(
		private readonly InMemoryLruCache $inner,
		private readonly StatsFactory $statsFactory,
		private readonly string $pool,
		private readonly bool $flushBufferedStats,
	) {
	}

	public function fetch( $id ) {
		$result = $this->inner->fetch( $id );

		$this->countLookup( $result !== false ? 'inmemory_cache_hits_total' : 'inmemory_cache_misses_total' );

		return $result;
	}

	private function countLookup( string $counterName ): void {
		$counter = $this->statsFactory->getCounter( $counterName )
			->setLabel( 'pool', $this->pool );

		$counter->increment();
		$this->flushIfBufferIsFull( $counter );
	}

	/**
	 * @param MetricInterface|NullMetric $metric
	 */
	private function flushIfBufferIsFull( $metric ): void {
		if (
			$this->flushBufferedStats &&
			$metric instanceof MetricInterface &&
			$metric->getSampleCount() >= self::MAX_BUFFERED_SAMPLES
		) {
			$this->statsFactory->flush();
		}
	}

	public function contains( $id ) {
		return $this->inner->contains( $id );
	}

	public function save( $id, $data, $ttl = 0 ): void {
		$this->inner->save( $id, $data, $ttl );
		$this->updateSizeGauge();
	}

	public function delete( $id ) {
		$result = $this->inner->delete( $id );
		$this->updateSizeGauge();
		return $result;
	}

	public function getStats() {
		return $this->inner->getStats();
	}

	private function updateSizeGauge(): void {
		$size = $this->inner->getStats()['count'];

		if ( $size === $this->reportedSize ) {
			return;
		}

		$this->reportedSize = $size;

		$gauge = $this->statsFactory->getGauge( 'inmemory_cache_size' )
			->setLabel( 'pool', $this->pool );

		$gauge->set( $size );
		$this->flushIfBufferIsFull( $gauge );
	}

}
