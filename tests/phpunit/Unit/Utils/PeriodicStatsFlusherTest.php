<?php

namespace SMW\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SMW\Utils\PeriodicStatsFlusher;
use Wikimedia\Stats\Emitters\NullEmitter;
use Wikimedia\Stats\StatsCache;
use Wikimedia\Stats\StatsFactory;

/**
 * @covers \SMW\Utils\PeriodicStatsFlusher
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.2.0
 */
class PeriodicStatsFlusherTest extends TestCase {

	public function testDoesNotFlushBeforeIntervalCompletes(): void {
		$statsFactory = $this->newStatsFactorySpy();
		$flusher = new PeriodicStatsFlusher( $statsFactory );

		$this->tickTimes( $flusher, 99 );

		$this->assertSame( 0, $statsFactory->flushCount );
	}

	public function testFlushesOnEachCompletedInterval(): void {
		$statsFactory = $this->newStatsFactorySpy();
		$flusher = new PeriodicStatsFlusher( $statsFactory );

		$this->tickTimes( $flusher, 250 );

		$this->assertSame( 2, $statsFactory->flushCount );
	}

	private function tickTimes( PeriodicStatsFlusher $flusher, int $times ): void {
		for ( $i = 0; $i < $times; $i++ ) {
			$flusher->tick();
		}
	}

	private function newStatsFactorySpy() {
		return new class( new StatsCache(), new NullEmitter(), new NullLogger() ) extends StatsFactory {
			public int $flushCount = 0;

			public function flush(): void {
				$this->flushCount++;
			}
		};
	}

}
