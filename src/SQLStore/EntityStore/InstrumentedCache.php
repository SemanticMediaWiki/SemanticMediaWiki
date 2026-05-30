<?php

namespace SMW\SQLStore\EntityStore;

use SMW\Cache\InMemoryLruCache;
use Wikimedia\Stats\StatsFactory;

/**
 * Decorator that emits per-event hit/miss counters and a current-size gauge to
 * a `StatsFactory` for an underlying in-process LRU cache. Used to instrument
 * the request-scoped pools backing entity ID lookups so operators can see
 * cache effectiveness in their existing metrics tooling.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class InstrumentedCache {

	public function __construct(
		private readonly InMemoryLruCache $inner,
		private readonly StatsFactory $statsFactory,
		private readonly string $pool,
	) {
	}

	public function fetch( $id ) {
		$result = $this->inner->fetch( $id );

		if ( $result !== false ) {
			$this->statsFactory->getCounter( 'inmemory_cache_hits_total' )
				->setLabel( 'pool', $this->pool )
				->increment();
		} else {
			$this->statsFactory->getCounter( 'inmemory_cache_misses_total' )
				->setLabel( 'pool', $this->pool )
				->increment();
		}

		return $result;
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
		$stats = $this->inner->getStats();
		$count = (int)( $stats['count'] ?? 0 );

		$this->statsFactory->getGauge( 'inmemory_cache_size' )
			->setLabel( 'pool', $this->pool )
			->set( $count );
	}

}
