<?php

namespace SMW\Utils;

use MediaWiki\MediaWikiServices;
use Wikimedia\Stats\StatsFactory;

/**
 * Periodically flushes MediaWiki's buffered stats samples during
 * long-running loops such as maintenance scripts, RDF exports and rebuild
 * jobs.
 *
 * Every metric emit buffers a sample in process memory. Web requests flush
 * the buffer when they end; a long-running process that iterates over a large
 * part of the wiki has no equivalent flush point (MediaWiki 1.43) or does
 * not reliably reach it (1.44+ flushes on output and replication waits,
 * which loops that report sparsely never call), so the buffer grows with
 * every processed entity until the process hits its memory limit. Draining
 * the buffer every few hundred entities keeps memory flat; with no stats
 * target configured the flush is a near-free no-op emit.
 *
 * @license GPL-2.0-or-later
 * @since 7.2.0
 */
class PeriodicStatsFlusher {

	private const FLUSH_INTERVAL = 100;

	private int $tickCount = 0;

	public function __construct( private readonly StatsFactory $statsFactory ) {
	}

	/**
	 * Convenience constructor for the maintenance scripts and other CLI
	 * entry points, resolving the shared StatsFactory from the global
	 * service container.
	 *
	 * @since 7.2.0
	 */
	public static function newFromGlobalState(): self {
		return new self( MediaWikiServices::getInstance()->getStatsFactory() );
	}

	/**
	 * Marks one loop iteration; flushes the stats buffer each time the
	 * flush interval completes.
	 *
	 * @since 7.2.0
	 */
	public function tick(): void {
		$this->tickCount++;

		if ( $this->tickCount % self::FLUSH_INTERVAL === 0 ) {
			$this->statsFactory->flush();
		}
	}

}
